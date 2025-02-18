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
use Jenga\App\Models\Migration;
use Jenga\App\Project\Core\Project;

class DbMigrate extends Command{
    
    public function configure() {
        
        $this->setName('db:migrate')
                ->setDescription('Loads the Jenga Migration to allow the application database and tables to imported and exported')
                ->addArgument('element_migrator', 
                        InputArgument::OPTIONAL, 
                        'Specify the element and migrator using format <element>/<migration class>. If left blank the whole database will be exported')
                ->addOption('table', null, 
                        InputOption::VALUE_REQUIRED, 
                        'Specify the table to be exported')
                ->addOption('task', null, 
                        InputOption::VALUE_REQUIRED, 
                        'Select task to be executed. Default options are import or export')
                ->addOption('filter', null, 
                        InputOption::VALUE_REQUIRED, 
                        'For the export task specify what is to be exported. Default filter options are structure, data')
                ->addOption('filepath', null, 
                        InputOption::VALUE_OPTIONAL, 
                        'Specify the import or export path for saving (on export) retrieving (on import) the file.'
                        . 'The default path is project/migrations. To specify start from the /project folder onwards e.g. /articles/table.sql')
                ->addOption('drop', null, 
                        InputOption::VALUE_OPTIONAL, 
                        'This informs the migrator to drop any existing tables if found. Options are TRUE or FALSE. Defauls is FALSE');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output){
        
        $io = new SymfonyStyle($input, $output);
        
        //bind io to shell
        App::bind('migrator_io', $io);
        
        $migrator_instance = App::get(Migration::class);
        $configs = App::get('_config');
        
        $migratorarg = $input->getArgument('element_migrator');
        
        if(!is_null($migratorarg)){
            
            $argument = $input->getArgument('element_migrator');
            
            if(strpos($argument, '/') !== FALSE){
                
                $project = new Project();
                
                $project->build();
                $elements = $project->consoleBuild();
                
                $element_migrator = explode('/',$argument);

                $element_name = strtolower($element_migrator[0]);
                $mig_name = ucfirst($element_migrator[1]);
                
                $element = $elements[$element_name];
                $schema = $element['schema'];
        
                //check migrator schema 
                if(!array_key_exists($mig_name, $schema)){
                    $io->error('The migrator class ['.$mig_name.'] has not been found in the element: '.ucfirst($element_name));
                    exit();
                }
                
                $fullmig = $this->_getSchemaNameSpace($element_name,$schema[$mig_name]);
                
                //initialise migration class
                if(class_exists($fullmig)){

                    $task = $input->getOption('task');

                    if(method_exists($fullmig, $task)){

                        $execute = App::call([$fullmig, $task]);

                        if($execute !== FALSE || !is_null($execute)){

                            $io->newLine();                    
                            $io->write('The task [ '.$task.' ] has been successfully performed');                    
                            $io->newLine();

                            if(is_string($execute))
                                $io->write('Task Response: '.$execute);
                        }
                        else{
                            $io->error('The task [ '.$task.' ] has failed');
                        }
                    }           
                    else{
                        $io->warning('Please define the task ['.$task.'] within your migration class');
                    }
                }
                else{
                    $io->error('The schema class '.$fullschema.' does not exist');
                }
            }
            else{
                
                $migrator_name = $argument;
                $migrationclass = 'Jenga\\MyProject\\Migrations\\'.ucfirst($migrator_name);
                
                if(class_exists($migrationclass)){
                    
                    $task = $input->getOption('task');
                    
                    if(is_null($task)){
                        $io->error('The migration task must be defined --task [MIGRATION CLASS METHOD]');
                        exit;
                    }
                    
                    if(method_exists($migrationclass, $task)){
                        
                        $execute = App::call([$migrationclass, $task]);
                        
                        if($execute !== FALSE || !is_null($execute)){
                    
                            $io->newLine();                    
                            $io->write('The task [ '.$task.' ] has been successfully performed');                    
                            $io->newLine();

                            if(is_string($execute))
                                $io->write('Task Response: '.$execute);
                        }
                        else{
                            $io->error('The task [ '.$task.' ] has failed');
                        }
                    }
                    else{
                        $io->warning('Please define the task ['.$task.'] within your migration class');
                    }
                }
                else{
                    $io->error('The migration handler class '.$migrationclass.' does not exist');
                }
            }
        }
        
        //check if a table has been set
        if(!is_null($input->getOption('table'))){            
            $table = [$input->getOption('table')];
        }
        else{
            $table = [];
        }
        
        //check tasks option
        if(!is_null($input->getOption('task'))){
            
            $task = $input->getOption('task');
            
            if($task == 'export'){
                
                $path = $this->_getPath($input);
                
                if(is_null($input->getOption('filter'))){
                    $filter = 'both';
                }
                else{
                    $filter = $input->getOption('filter');
                }
                
                $timestamp = date('d_m_Y_Hi', time());
                
                if(count($table) === 0){
                    
                    $human_file_name = $configs->db.'-'.$timestamp.'.sql';
                    $filename = $path['full'] .DS. $configs->db.'-'.$timestamp;
                }
                else{
                    
                    $human_file_name = rtrim(join('_', $table),'_').'_table_export.sql';
                    $filename = $path['full'] .DS. rtrim(join('_', $table),'_').'_table_export';
                }
                
                $io->newLine();
                
                if($migrator_instance->export($table, $filename, $filter)){
                    $io->write('The exported file: '.$human_file_name.' has been successfully saved to '.$path['short']);                    
                }
                else{
                    $io->error('The export task has failed.');
                }
                
                $io->newLine();
            }
            elseif($task == 'import'){
                
                if(is_null($input->getOption('filepath'))){
                    $io->error('The import task MUST have a specified file path: --filepath [FILE PATH]');
                }
                else{
                    $path = $this->_getPath($input);
                    
                    if(file_exists($path['full'])){
                        
                        //check for the drop table option
                        if(is_null($input->getOption('drop')))
                            $drop = FALSE;
                        else
                            $drop = $input->getOption ('drop');
                        
                        if($migrator_instance->import($path['full'], $drop)){
                            $io->success('The import has been successfull.');
                        }
                        else{
                            $io->error('The import has failed.');
                        }
                    }
                    else{
                        $io->error('The specified import file has not been found in [ /project/'.$input->getOption('filepath').' ]');
                    }
                }
            }
        }
    }
    
    /**
     * Get the proper path to folder or file
     * 
     * @param type $input
     * @return string
     */
    private function _getPath($input){
        
        if(!is_null($input->getOption('filepath'))){
                    
            $path = $input->getOption('filepath');
            
            $path1 = str_replace('/', DS, $path);
            $path2 = str_replace('\\', DS, $path1);
            $spath = ltrim($path2, DS);

            if(strpos('project', $spath) === 0)
                $strpath = str_replace ('project', '', $spath);
            else
                $strpath = $spath;
            
            $fullpath['full'] =  ABSOLUTE_PROJECT_PATH .DS. $strpath;
            $fullpath['short'] = 'project'.DS.$strpath;
        }
        else{
            
            $this->_isMigrationsPresent();
            $fullpath['full'] = ABSOLUTE_PROJECT_PATH .DS. 'migrations';
            $fullpath['short'] = 'project'.DS.'migrations';
        }
        
        return $fullpath;
    }
    
    /**
     * Checks for migrations folder in project if not its created
     * 
     * @return boolean
     */
    private function _isMigrationsPresent(){
        
        $mgrpath = ABSOLUTE_PROJECT_PATH .DS. 'migrations';
        
        if(!file_exists($mgrpath))
            mkdir($mgrpath, 0777, true);
            
        return TRUE;
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
}
