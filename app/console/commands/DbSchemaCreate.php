<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Helpers\Help;
use Jenga\App\Project\Core\Project;

/**
 * Creates new schema inside a given element
 *
 * @author stanley
 */
class DbSchemaCreate extends Command {
    
    public $io;
    public $mouldpath;
    
    protected $namespace;
    protected $class;

    protected function configure() {
        
        $this->setName('db:schema:create')
                ->setDescription('Creates new schema inside a given element')
                ->addArgument('element_schema', 
                        InputArgument::REQUIRED, 
                        'Specify the element schema using format: <element>/<schema class>')
                ->addOption('table', null, InputOption::VALUE_REQUIRED,
                        'Specify the default table to be connected to the schema')
                ->addOption('writer-only',null, InputOption::VALUE_NONE,
                        'Specify to create only the schema writer');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $this->io = new SymfonyStyle($input, $output);
        $this->mouldpath = ABSOLUTE_PATH .DS. 'app' .DS. 'build' .DS. 'moulds' .DS. 'element';
        
        if(is_null($input->getOption('table'))){
            $this->io->error('The schema table must be specified using --table');
            exit;
        }
        
        //get the element schema
        $elmschema = $input->getArgument('element_schema');
        
        //get schema table
        $table = $input->getOption('table');
        
        if(strpos($elmschema, '/') === FALSE){
            $this->io->error('No schema name found. Please enter in the following format: <element>/<schema class>');
            return ;
        }
        
        $split = explode('/', $elmschema);

        $elm['element'] = strtolower($split[0]);
        $elm['schema'] = ucfirst($split[1]);
         
        $element = Project::elements()[$elm['element']];
        $path = ABSOLUTE_PROJECT_PATH .DS. $element['path'] .DS. 'schema';
         
        //create schema class
        $this->namespace = 'Jenga\\MyProject\\'.ucfirst($elm['element']).'\\Schema';
        
        //check for schema name
        if(stristr($elm['schema'], 'schema') !== FALSE){
            $this->class = $elm['schema'];
        }
        else{
            $this->class = $elm['schema'].'Schema';
        }
        
        //create the schema and writer classes
        if($input->getOption('writer-only') === FALSE){
            
            $save = $this->_createSchemaClass($path, $table);            
            if($save){ $this->io->success($elmschema.' and its writer created successfully'); }
        }
        elseif($input->getOption('writer-only') === TRUE){
            
            $this->_createSchemaWriter($path);
            $this->io->success($elmschema.' writer created successfully');
        }
    }
    
    /**
     * Create the schema class
     * @param type $schemapath
     */
    private function _createSchemaClass($schemapath, $table = null){
        
        $schema = File::get($this->mouldpath .DS. 'schema.mld');
        
        $schemastr = str_replace('{{{schm_namespace}}}', $this->namespace, $schema);
        $schemadata = str_replace('{{{schm_classname}}}', $this->class, $schemastr);
        
        //store table
        if(!is_null($table)){
            $schemadata = str_replace ('{{{schm_table}}}', $table, $schemadata);
        }
        
        //configure schema writer
        $this->_createSchemaWriter($schemapath);
        
        return File::put($schemapath .DS. $this->class.'.php', $schemadata);
    }
    
    /**
     * Creates the schema writer class
     * @param type $schemapath
     * @param type $element
     * @param type $name
     * @param type $table
     * @return type
     */
    private function _createSchemaWriter($schemapath) {
        
        //get writer mould
        $writer = File::get($this->mouldpath .DS. 'schemawriter.mld');
        
        $writerstr = str_replace('{{{schm_namespace}}}', $this->namespace, $writer);
        $writerdata = str_replace('{{{schm_classname}}}', $this->class, $writerstr);
        $writerclass = $this->class.'Writer';
        $writerpath = $schemapath .DS. 'tools';
        
        //save writer
        return File::put($writerpath .DS. $writerclass.'.php', $writerdata);
    }
}
