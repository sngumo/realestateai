<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Style\SymfonyStyle;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Project\Core\Project;

/**
 * This class create the model component within an element
 *
 * @author stanley
 */
class CreateElementModel extends Command{
    
    protected $mouldpath = ABSOLUTE_PATH .DS. 'app' .DS. 'build' .DS. 'moulds' .DS. 'element';
    
    protected function configure() {
        
        $this->setName('create:element:model')
                ->setDescription('Creates the model component within an element')
                ->addArgument('element_model',  InputArgument::REQUIRED,'Specify the model using format <element>/<model_class_name>')
                ->addOption('schema', null, InputOption::VALUE_REQUIRED,
                        'Specify the default table schema to be connected to the model');
                
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $io = new SymfonyStyle($input, $output);
        
        //get element table
        $schema = $input->getOption('schema');
        if(is_null($schema)){
            $io->error('The model schema must be specified using --schema');
            exit;
        }
        
        $elmodel = $input->getArgument('element_model');
            
        if(strpos($elmodel, '/') === FALSE){
            $element_name = strtolower($elmodel);
            $model_name = ucfirst($elmodel).'Model';
        }
        else{
            $element_model = explode('/',$elmodel);
            
            $element_name = strtolower($element_model[0]);
            $model_name = ucfirst($element_model[1]);
        }
        
        $element = Project::elements()[$element_name];
        $path = ABSOLUTE_PROJECT_PATH .DS. $element['path'] .DS. 'models';
        
        //create model class
        $this->namespace = 'Jenga\\MyProject\\'.ucfirst($element_name).'\\Models';
        
        $schm['namespace'] = 'Jenga\\MyProject\\'.ucfirst($element_name).'\\Schema';
        $schm['class'] = $schema;
        
        $save = $this->_createModelClass($path, strtolower($element_name), $model_name, $schm);
        
        if($save){
            $io->success(ucfirst($model_name).' has been created in '.ucfirst($element_name));
        }
    }
    
    /**
     * Create the model class
     * @param type $modelpath
     */
    private function _createModelClass($modelpath, $element, $modelname, $schemadata){
        
        $model = File::get($this->mouldpath .DS. 'model.mld');
        
        $modelnamespace = 'Jenga\\MyProject\\'.ucfirst($element).'\\Models';
        $modelclass = $modelname;
        
        $mdl = str_replace('{{{mdl_namespace}}}', $modelnamespace, $model);
        $mdl2 = str_replace('{{{mdl_classname}}}', $modelclass, $mdl);
        
        //schema info
        $mdl3 = str_replace('{{{schm_classname}}}', $schemadata['class'], $mdl2);
        $mdl4 = str_replace('{{{schm_namespace}}}', $schemadata['namespace'], $mdl3);
        
        return File::put($modelpath .DS. $modelclass.'.php', $mdl4);
    }
}
