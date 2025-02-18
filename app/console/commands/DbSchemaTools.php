<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

use Jenga\App\Core\App;
use Jenga\App\Project\Core\Project;
use Jenga\App\Database\Systems\Pdo\Schema\AnnotationsWriter;

/**
 * Description of DbSchemaTools
 *
 * @author stanley
 */
class DbSchemaTools extends Command {
    
    protected $io;
    protected $output;
    
    protected function configure() {
        
        $this->setName('db:schema:tools')
                ->setDescription('Loads the schema tools within the specified element')
                ->addArgument('element_schema', 
                        InputArgument::REQUIRED, 
                        'Specify the element schema using format <element>/<schema_class>')
                ->addOption('writer', NULL, InputOption::VALUE_REQUIRED, 'The available writer options are: '
                        . 'write, write-and-update, rebuild, rebuild-and-update and force-build');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $this->io = new SymfonyStyle($input, $output);
        $this->output = $output;
        
        //get writer
        $writer = $input->getOption('writer');
        if(is_null($writer)){
            $this->io->error('The tool action must be specified using --writer');
            exit;
        }
        
        //process element schema
        $elmschema = $input->getArgument('element_schema');        
        if(strpos($elmschema, '/') === FALSE){
            $elm['element'] = ucfirst($elmschema);
            $elm['schema'] = $elmschema.'Schema';
        }
        else{
            $split = explode('/', $elmschema);

            $elm['element'] = ucfirst($split[0]);
            $elm['schema'] = ucfirst($split[1]);
        }
        
        //swicth between writers
        switch ($writer) {
            
            case 'write':
                $this->writeSchemaAnnotations($elm);
                break;
            
            case 'write-and-update':
                $this->writeSchemaAnnotations($elm, TRUE);
                break;
            
            case 'rebuild':
                $this->writeSchemaAnnotations($elm, FALSE, TRUE);
                break;
            
            case 'rebuild-and-update':
                $this->writeSchemaAnnotations($elm, TRUE, TRUE);
                break;
            
            case 'force-build':
                $this->writeSchemaAnnotations($elm, FALSE, FALSE, TRUE);
                break;
        }
    }
    
    /**
     * Write annotated columns into schema
     * @param type $element
     * @param type $update
     */
    protected function writeSchemaAnnotations($element, $update = FALSE, $rebuild = FALSE, $forcebuild = FALSE) {
        
        $append = null;
        $writerclass = 'Jenga\MyProject\\'.$element['element'].'\Schema\Tools\\'.$element['schema'].'Writer';
        
        if(class_exists($writerclass)){

            //rebuild
            if($rebuild){
                AnnotationsWriter::rebuildFromPreviousSchema();
            }
            
            //force
            if($forcebuild){
                AnnotationsWriter::dropAndCreate();
            }
          
            //get the writer class 
            $writer = App::get($writerclass);
            $result = $writer->write($update);
            
            //check if force build is enabled
            if($result === TRUE && $forcebuild === TRUE){
                $result = 'UPDATE_SCHEMA';
            }
            
            //run the update schema command
            if($result === 'UPDATE_SCHEMA'){
                
                $cmd = $this->getApplication()->find('db:schema:update');
                $args = [
                    'command' => 'db:schema:update',
                    'element_schema' => $element['element'].'/'.$element['schema']
                ];
                
                //add force build
                if($forcebuild){
                    $args['--force'] = true;
                }
                
                //add array input arguments
                $cmdInput = new ArrayInput($args);
                $cmd->run($cmdInput, $this->output);
                
                //append response
                if($rebuild)
                    $append = ',rebuilt and updated';
                else 
                    $append = ' and updated';
            }
            elseif($rebuild && is_int($result)) {
                $append = ' and rebuilt';
            }
            
            if(is_int($result) || $result == 'UPDATE_SCHEMA'){
                $this->io->success('The schema columns have been written'.$append.' successfully. Task Response: '.$result);
            }
            else{                
                $this->io->error('Task Response: '.$result);      
            }
        }
        else{
            $this->io->error('Annotator class'.$element['schema'].'Writer'.' not found in '.$element['element']);
        }
    }
}
