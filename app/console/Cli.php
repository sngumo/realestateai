<?php
namespace Jenga\App\Console;
/**
 * This is the main CLI console handler
 */
use Jenga\App\Core\App;
use Jenga\App\Request\Url;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Core\ElementsLoader;

use Symfony\Component\Console\Application;

class Cli extends App {
    
    public function boot(){
        
        $project = new Project();
        $project->build();
        
        $elements = Project::elements();
        
        //project autoload
        $loader = new ElementsLoader(self::$elements);
        spl_autoload_register(array($loader, 'autoLoadElements'));
    }
    
    /**
     * Scans the selected folder and 
     * 
     * @param type $dir
     * @return type
     */
    public function scandirs($dir){
            
        $listDir = array();
        if($handler = @opendir($dir)) {
            while (($sub = readdir($handler)) !== FALSE) {
                if ($sub != "." && $sub != ".." && $sub != "Thumb.db") {
                    if(is_file($dir.DS.$sub)) {
                        $listDir[] = $dir.DS.$sub;
                    }
                    elseif(is_dir($dir.DS.$sub)){
                        $listDir[$sub] = $this->scandirs($dir.DS.$sub);
                    }
                }
            }   
            closedir($handler);
        }
        return $listDir;  
    }
    
    public function loadCommands() {
        
        $dirs = $this->scandirs(ROOT.DS.'app'.DS.'console'.DS.'commands');
        
        foreach($dirs as $name => $folder){
            if(is_array($folder)){
                foreach($folder as $file){                    
                    $cmd = basename($file,'.php');
                }
                $commands[] = $cmd;
            }
            else{
                $commands[] = basename($folder,".php");
            }
        }
        
        return $commands;
    }
    
    /**
     * Executes the commands loaded into the framework
     * 
     * @param type $commands
     * @param type $execute
     * @return Application
     */
    public function run($commands){
        
        $application = new Application();
        
        foreach($commands as $command){
            $namespaced_command = 'Jenga\App\Console\Commands\\'.$command;
            $application->add(new $namespaced_command ());
        }
        
        return $application->run();
    }
}