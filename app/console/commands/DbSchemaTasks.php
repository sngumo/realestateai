<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

use Jenga\App\Core\App;
use Jenga\App\Project\Core\Project;

class DbSchemaTasks extends Command {
    
    protected function configure() {
        
        $this->setName('db:schema:tasks')
                ->setDescription('Loads the Jenga Schema and runs the schema methods (called tasks) within the specified element')
                ->addArgument('element_schema', 
                        InputArgument::REQUIRED, 
                        'Specify the element schema using format <element>/<schema class> ')
                ->addOption('task', null, 
                        InputOption::VALUE_REQUIRED, 
                        'Select task to be executed. Default options are seed, export or run');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $project = new Project();
        $io = new SymfonyStyle($input, $output);
        
        $project->build();
        $elements = $project->consoleBuild();
        
        $elmschema = $input->getArgument('element_schema');
            
        if(strpos($elmschema, '/') === FALSE){
            $element_name = strtolower($elmschema);
            $schema_name = ucfirst($elmschema).'Schema';
        }
        else{
            $element_schema = explode('/',$elmschema);
            
            $element_name = strtolower($element_schema[0]);
            $schema_name = ucfirst($element_schema[1]);
        }
        
        $element = $elements[$element_name];
        $schema = $element['schema'];
        
        //check schema 
        if(!array_key_exists($schema_name, $schema)){            
            $io->error('The schema ['.$schema_name.'] has not been found in the element: '.ucfirst($element_name));
            exit();
        }
        
        $fullschema = $this->_getSchemaNameSpace($element_name,$schema[$schema_name]);
       
        //initialise schema class
        if(class_exists($fullschema)){
            
            $schema_obj = App::get($fullschema);
            $schema_obj->tasks();
            
            $task = $input->getOption('task');
            
            if(method_exists($schema_obj, $task)){
                
                $execute = $schema_obj->{$task}();
                
                if($execute === TRUE){
                    
                    $io->newLine();                    
                    $io->success('The task [ '.$task.' ] has been successfully performed');                    
                    $io->newLine();
                    
                    if(is_string($execute))
                        $io->write('Task Response: '.$execute);
                }
                else{
                    $io->error('The task [ '.$task.' ] has failed');
                    $io->write('Task Response: '.print_r($execute));
                }
            }           
            else{
                $io->warning('Please define the task ['.$task.'] within your schema class');
            }
        }
        else{
            $io->error('The schema class '.$fullschema.' does not exist');
        }
    }
    
    /**
     * Returns Schema class in full namespace
     * 
     * @param string $element 
     * @param array $schema
     * 
     * @return string Fully Namespaced class
     */
    private function _getSchemaNameSpace($element, array $schema){
        
        //parse folder
        $folder = explode('/', $schema['folder']);
        $folders = array_map('ucfirst',$folder);
        
        //parse path
        $schema_arr = explode(DS,$schema['path']);
        $schema_file  = end($schema_arr);
        $schema_name = explode('.', $schema_file);
        
        return 'Jenga\MyProject\\'.ucfirst($element).'\\'.join('\\',$folders).'\\'.$schema_name[0];
    }
    
    /**
     * Checks the sent options and returns the value which isn't NULL
     * 
     * @param array $options
     * @return boolean
     */
    private function _checkOptions(array $options){
        
        $keywords = ['help','quiet','verbose','version','ansi','no-ansi','no-interaction'];
        
        foreach($options as $option => $value){
            
            if(!in_array($option, $keywords)){
                
                if(!is_null($value)){                    
                    $optionslist[] = $option;
                }
            }
        }
        
        if(isset($optionslist)){
            return $optionslist;
        }
    }
}
