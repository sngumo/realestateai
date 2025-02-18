<?php
namespace Jenga\App\Console\Commands;

/**
 * The command creates a basic element in the Jenga project
 *
 * @author sngumo
 */

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Style\SymfonyStyle;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Elements\XmlElements;

class CreateElement extends Command {
    
    public $mouldpath;
    public $file;

    protected function configure() {
        
        $this->setName('create:element')
                ->setDescription('Creates a basic element in the Jenga project')
                ->addArgument('element_name',  InputArgument::REQUIRED)
                ->addOption('table', null, InputOption::VALUE_REQUIRED,
                        'Specify the default table to be connected to the element')
                ->addOption('path', null, 
                        InputOption::VALUE_OPTIONAL, 
                        'Specify the folder path where the element would be created. '
                        . 'Start with forward slash from the project folder onwards e.g. /foldername')
                ->addOption('strict', null, InputOption::VALUE_NONE,'If strict is used, only the core MVC elements will be created');
                
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        //get element name
        $element = str_replace(' ', '', $input->getArgument('element_name'));
        
        //get path
        $path = $input->getOption('path');
        $io = new SymfonyStyle($input, $output);
        
        //get element table
        $table = $input->getOption('table');
        if(is_null($table)){
            $io->error('The element table must be specified using --table');
            exit;
        }
        
        //create the mould path
        $this->mouldpath = ABSOLUTE_PATH .DS. 'app' .DS. 'build' .DS. 'moulds' .DS. 'element';
        $this->file = App::get(File::class);
            
        $xml = [];

        $foldername = strtolower($element);
        $xml['element'] = $foldername;
        
        //process element path
        if(!is_null($path)){
            
            if(strpos($path, '/') !== FALSE){
                
                $path = ltrim($path,'/');
                $foldername = str_replace('/', DS, $path);
            }
            else if(strpos($path, '\\') !== FALSE){
                
                $path = ltrim($path,'\\');
                $foldername = str_replace('\\', DS, $path);
            }
        }
        
        $xml['path'] = $foldername;
        
        //reorient the map path shown in the map.xml
        if(strstr($foldername,'\\') !== FALSE){
            $mappath = str_replace('\\', '/', $foldername);
        }
        else{
            $mappath = $foldername;
        }
        
        $xml['mappath'] = $mappath;

        //create the element
        $io->title('Creating '.ucfirst($element).' Element');

        //if is not directory create one
        if(!is_dir(ABSOLUTE_PROJECT_PATH .DS. $foldername)){
            mkdir(ABSOLUTE_PROJECT_PATH .DS. $foldername, 0755, TRUE);
        }

        if(file_exists(ABSOLUTE_PROJECT_PATH .DS. $foldername)){

            $xml['element_path'] = $foldername;
            $rootfolder = ABSOLUTE_PROJECT_PATH .DS. $foldername;
            
            //create schema folder
            if(mkdir($rootfolder .DS. 'schema' .DS. 'tools', 0755, TRUE)){

                $schemadata = $this->_createSchemaClass($rootfolder .DS. 'schema', $element, $table);
                $io->write(' [..] '.ucfirst($element).' schema created ', TRUE);

                $xml['schema_folder'] = 'schema';
            }
            
            //create models folder
            if(mkdir($rootfolder .DS. 'models',0755)){    

                $this->_createModelClass($rootfolder .DS. 'models', $element, $schemadata);
                $io->write(' [..] '.ucfirst($element).' model created ', TRUE);

                $xml['model_folder'] = 'models';
            }

            //create controllers folder
            if(mkdir($rootfolder .DS. 'controllers',0755)){

                $this->_createControllerClass($rootfolder .DS. 'controllers', $element);
                $io->write(' [..] '.ucfirst($element).' controller created ', TRUE);

                $xml['controller_folder'] = 'controllers';
            }

            //create views folder
            if(mkdir($rootfolder .DS. 'views',0755)){

                $this->_createViewClass($rootfolder .DS. 'views', $element);
                $io->write(' [..] '.ucfirst($element).' view created ', TRUE);

                $xml['views_folder'] = 'views';
            }

            //create panels inside public folder
            $mkdir = true;
            if(is_null(Project::getTemplates())){
                $mkdir = $this->_createPublicFolder($element);
            }
            
            if($mkdir){
                
                //get the panels mould path
                $panel = $this->file->get($this->mouldpath .DS. 'panel.mld');
                
                //replace the element with the element name
                $panel = str_replace('{{{element}}}', ucfirst($element), $panel);
                
                $this->file->put(ABSOLUTE_PATH .DS. 'public' .DS. 'panels' .DS. strtolower($element) .DS. strtolower($element).'.php', $panel, TRUE);
                $io->write(' [..] '.ucfirst($element).' panel created ', TRUE);
                $io->newLine();
            }

            //insert the new element into maps.xml
            $this->_addNewElement($xml);
        }
        else{
            $io->warning('The [ '.ucfirst($element).' ] element folder not created');
        }
                
    }
    
    /**
     * Create the model class
     * @param type $modelpath
     */
    private function _createModelClass($modelpath, $element, $schemadata){
        
        $model = $this->file->get($this->mouldpath .DS. 'model.mld');
        
        $modelnamespace = 'Jenga\\MyProject\\'.ucfirst($element).'\\Models';
        $modelclass = ucfirst($element).'Model';
        
        $mdl = str_replace('{{{mdl_namespace}}}', $modelnamespace, $model);
        $mdl2 = str_replace('{{{mdl_classname}}}', $modelclass, $mdl);
        
        //schema info
        $mdl3 = str_replace('{{{schm_classname}}}', $schemadata['class'], $mdl2);
        $mdl4 = str_replace('{{{schm_namespace}}}', $schemadata['namespace'], $mdl3);
        
        return $this->file->put($modelpath .DS. $modelclass.'.php', $mdl4);
    }
    
    /**
     * Create the controller class
     * @param type $controllerpath
     */
    private function _createControllerClass($controllerpath, $element){
        
        $controller = $this->file->get($this->mouldpath .DS. 'controller.mld');
        
        $controllernamespace = 'Jenga\\MyProject\\'.ucfirst($element).'\\Controllers';
        $controllerclass = ucfirst($element).'Controller';
        
        $ctrl = str_replace('{{{ctrl_namespace}}}', $controllernamespace, $controller);
        $ctrldata = str_replace('{{{ctrl_classname}}}', $controllerclass, $ctrl);
        
        //use model
        $modelclass = ucfirst($element).'Model';
        $usemodel = 'Jenga\\MyProject\\'.ucfirst($element).'\\Models\\'.$modelclass;
        
        
        //add the model packages
        $ctrldata = str_replace('{{{full_model_name}}}', $usemodel, $ctrldata);
        $ctrldata = str_replace('{{{model_class_name}}}', $modelclass, $ctrldata);
        
        //use view
        $viewclass = ucfirst($element).'View';
        $useview = 'Jenga\\MyProject\\'.ucfirst($element).'\\Views\\'.$viewclass;
        
        //add the view
        $ctrldata = str_replace('{{{full_view_name}}}', $useview, $ctrldata);
        $ctrldata = str_replace('{{{view_class_name}}}', $viewclass, $ctrldata);
        
        return $this->file->put($controllerpath .DS. $controllerclass.'.php', $ctrldata);
    }
    
    /**
     * Create the view class
     * @param type $viewpath
     */
    private function _createViewClass($viewpath, $element){
        
        $view = $this->file->get($this->mouldpath .DS. 'view.mld');
        
        $viewnamespace = 'Jenga\\MyProject\\'.ucfirst($element).'\\Views';
        $viewclass = ucfirst($element).'View';
        
        $viewstr = str_replace('{{{view_namespace}}}', $viewnamespace, $view);
        $viewdata = str_replace('{{{view_classname}}}', $viewclass, $viewstr);
        
        return $this->file->put($viewpath .DS. $viewclass.'.php', $viewdata);
    }
    
    /**
     * Create the schema class
     * @param type $schemapath
     */
    private function _createSchemaClass($schemapath, $element, $table = null){
        
        //configure schema
        $schema = $this->file->get($this->mouldpath .DS. 'schema.mld');
        
        $schemanamespace = 'Jenga\\MyProject\\'.ucfirst($element).'\\Schema';
        $schemaclass = ucfirst($element).'Schema';
        
        $schemastr = str_replace('{{{schm_namespace}}}', $schemanamespace, $schema);
        $schemadata = str_replace('{{{schm_classname}}}', $schemaclass, $schemastr);
        
        //store table
        if(!is_null($table)){
            $schemadata = str_replace ('{{{schm_table}}}', $table, $schemadata);
        }
        
        //configure schema writer
        $writer = $this->file->get($this->mouldpath .DS. 'schemawriter.mld');
        $writerstr = str_replace('{{{schm_namespace}}}', $schemanamespace, $writer);
        $writerdata = str_replace('{{{schm_classname}}}', $schemaclass, $writerstr);
        $writerclass = $schemaclass.'Writer';
        $writerpath = $schemapath .DS. 'tools';
        
        //store schema data
        $schemaexp['namespace'] = $schemanamespace;
        $schemaexp['class'] = $schemaclass;
        
        //store the schema and writer
        $this->file->put($schemapath .DS. $schemaclass.'.php', $schemadata);
        $this->file->put($writerpath .DS. $writerclass.'.php', $writerdata);
        
        return $schemaexp;
    }
    
    /**
     * Adds the new element into the map.xml file
     * 
     * @param type $xml
     */
    private function _addNewElement($xml){
        
        $xmldoc = App::get(XmlElements::class);
        
        if($xmldoc->loadXMLFile('map.xml', PROJECT_PATH, TRUE)){
            
            //create folder list
            $folder['model'] = $xml['model_folder'];
            $folder['controller'] = $xml['controller_folder'];
            $folder['view'] = $xml['views_folder'];
            $folder['schema'] = $xml['schema_folder'];
            
            //create element attributes
            $attrs['name'] = $xml['element'];
            $attrs['path'] = $xml['mappath'];
            $attrs['acl'] = 0;
            $attrs['visibility'] = 'public';
            
            $xmldoc->addElement($xml['element'], $folder, $attrs);
        }
    }
    
    /**
     * Creates a full public/templates folder
     */
    private function _createPublicFolder($element) {
        
        //create the templates folder
        $tmp = mkdir(ABSOLUTE_PATH .DS. 'public' .DS. 'panels' .DS. $element, 0755, TRUE);
        
        if($tmp){
            
            //create the templates element and add to maps.xml           
            $xmldoc = App::get(XmlElements::class);

            if($xmldoc->loadXMLFile('map.xml', PROJECT_PATH, TRUE)){
                
                //add the template attributes
                $attrs['name'] = "public";
                $attrs['function'] = "templates";
                $attrs['path'] = "templates";
                $attrs['scope'] = "system";
                $attrs["visibility"] = "private";
                
                //create the element
                $xmldoc->createTemplateElement($attrs);
            }
        }
        
        return TRUE;
    }
}
