<?php
/**
 * Handles the loading of project elements and embedded classes
 */
namespace Jenga\App\Project\Core;

use Jenga\App\Core\File;
use Jenga\App\Project\Core\Project;

class ElementsLoader{
    
    public $exceptions = array('Router','Partials','Elements');
    public static $elements;
    
    public function __construct($elements) {        
        self::$elements = $elements;
    }
    
    /**
    * Processes the autoload for the project elements
    * 
    * @param type $element
    */
   public function autoLoadElements($element){ 
       
        $section = explode('\\', $element); 
        $class = end($section);

        //check if the class is in the exceptions array
        if(!in_array($class, $this->exceptions) && count($section) > 1){
            
            if($section[1] == 'MyProject'){
               
                $filepath = null;
                if(!empty(self::$elements[\strtolower($section[2])][\strtolower($section[3])][$section[4] ?? ''])){
                    $filepath = self::$elements[ strtolower($section[2])][ strtolower($section[3]) ][ $section[4] ]['path'];
                }

                if(is_readable(PROJECT_PATH .DS. $filepath) 
                        && !is_null($filepath)){

                    require_once PROJECT_PATH .DS. $filepath; 
                }
                elseif(!is_null(self::$elements[strtolower($section[2])])){

                    /**
                     * This section allows for loading of any non MVC classes stored in any element
                     * Follow PSR-4 format
                     */
                    $path = self::$elements[strtolower($section[2])]['path'];

                    if(strpos($path, '/') != FALSE){
                        $path_split = explode('/', $path);
                        $path = join(DS, $path_split);
                    }

                    $path_slices = array_slice($section, 3);

                    //get the class
                    $class = ucfirst(array_pop($path_slices));

                    //change all folders names to lowercase for Linux - case sensitive environments
                    $pathparts = array_map('strtolower', $path_slices);

                    $fullpath = PROJECT_PATH .DS. $path .DS. join(DS, $pathparts) .DS. $class;

                    require $fullpath.'.php';
                }
                elseif(is_null(self::$elements[strtolower($section[2])]) 
                        || !array_key_exists($class, self::$elements[strtolower($section[2])])){

                    /**
                     * process any class files which maybe included directly in the projects section
                     * still implements PSR-4 after the Jenga\MyProject\
                     **/

                    if(count($section) > '2') {

                        unset($section[0],$section[1]);
                        $class = ucfirst(array_pop($section));     

                        //change all folders names to lowercase for Linux - case sensitive environments
                        $section = array_map('strtolower', $section);                    
                        $path = join(DS, $section);

                        if(file_exists(PROJECT_PATH .DS. $path .DS. $class .'.php'))
                            require PROJECT_PATH .DS. $path .DS. $class .'.php';  
                        else{

                            //assume the class is nested
                            $elements = Project::elements();
                            $element = $elements[$section[2]];

                            $elmpath = str_replace('/', DS, $element['path']);
                            $section = array_replace($section, [2 => $elmpath]);

                            $path = join(DS, $section);

                            if(file_exists(PROJECT_PATH .DS. $path .DS. $class .'.php'))
                                require PROJECT_PATH .DS. $path .DS. $class .'.php';
                        }
                    }
                    elseif(count($section) == '2'){
                        require PROJECT_PATH .DS. $class . '.php';
                    }     
                }
                else{

                    //clear the XML file cached in memory 
                    clearstatcache();

                    //rebuild the project elements if project elements not found
                    $this->build();

                    //overwrite the old elements with the new reinserted elements
                    self::$elements = unserialize(File::get(APP_PROJECT .DS. 'elements.php'));

                    //start the process again
                    $filepath = self::$elements[ strtolower($section[2]) ][ strtolower($section[3]) ][ $section[4] ]['path'];

                    if(is_readable(PROJECT_PATH .DS. $filepath) && !is_null($filepath)){

                        require_once PROJECT_PATH .DS. $filepath; 
                    }
                    elseif(array_key_exists('autoload', self::$elements[ $section[2] ])){

                        $elementpath = self::$elements[ $section[2] ]['path'];
                        $autoloadpath = self::$elements[ $section[2] ]['autoload'];

                        foreach($autoloadpath as $folder){

                            if(File::exists(PROJECT_PATH .DS. $elementpath .DS. $folder .DS. $class.'.php'))
                                require_once PROJECT_PATH .DS. $elementpath .DS. $folder .DS. $class.'.php';
                        }
                    }
                    else{

                        App::critical_error('Class: '.$element.' doesnt exist within your '.  ucfirst($section[2]) .' element');
                    }
                }
            }
        }
        elseif(in_array($class, $this->exceptions)){ 
            
            //load it from the project folder in the App section
            if(File::exists(APP_PROJECT .DS. 'core' .DS. $class.'.php')){
                require_once APP_PROJECT .DS. 'core' .DS. $class.'.php';
            }   
        }
    }
}
