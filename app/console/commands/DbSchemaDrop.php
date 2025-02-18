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
use Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Schema\Builder;
use Jenga\App\Database\Systems\Pdo\Connections\DatabaseConnector;

use ReflectionClass;

/**
 * Drops selected schema and db table
 * @author stanley
 */
class DbSchemaDrop extends Command {
    
    public $io;
    public $mouldpath;
    
    protected function configure() {
        
        $this->setName('db:schema:drop')
                ->setDescription('Drops selected schema and db table')
                ->addArgument('element_schema', 
                        InputArgument::REQUIRED, 
                        'Specify the element schema using format: <element>/<schema class> ');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $this->io = new SymfonyStyle($input, $output);
        
        $elmschema = $input->getArgument('element_schema');
        
        if(strpos($elmschema, '/') === FALSE){
            $this->io->error('No schema name found. Please enter in the following format: <element>/<schema class>');
            return ;
        }
        
        $split = explode('/', $elmschema);

        $elm['element'] = strtolower($split[0]);
        $elm['schema'] = ucfirst($split[1]);
         
        $element = Project::elements()[$elm['element']];
        $schema_class = 'Jenga\\MyProject\\'.ucfirst($element['name']).'\\Schema\\'.$elm['schema'];
        
        if(class_exists($schema_class)){
            
            //get schema path
            $path = ABSOLUTE_PROJECT_PATH .DS. $element['path'] .DS. 'schema' .DS. $elm['schema'].'.php';
            $writer_path = ABSOLUTE_PROJECT_PATH .DS. $element['path'] .DS. 'schema' .DS. 'tools' .DS. $elm['schema'].'Writer.php';
            
            //get database connection
            $connector = App::get(DatabaseConnector::class);
            $pdo = $connector::connect();
            $active = $pdo->getActiveConnection();
            
            //get builder
            $builder = App::make(Builder::class, ['prefix' => $active['prefix']]);
            
            $reflect = new ReflectionClass($schema_class);
            $schemacols = $reflect->getProperties();
            
            $result = null;
            foreach ($schemacols as $property) {
                
                $property->setAccessible(TRUE);
                $value = $property->getValue(new $schema_class);
                
                if($property->name == 'table'){
                    
                    //disable foreign key check
                    $builder->setForeignKeyCheck(0);
                    
                    //drop table
                    $result = $builder->dropTable($value);
                }                
            }
            
            if($result === true){
                
                //enable foregn key check
                $builder->setForeignKeyCheck(1);
                
                //delete schema
                $delete = File::delete($path);
                
                //delete schema writer
                if(File::exists($writer_path)){
                    File::delete($writer_path);
                }
                
                if($delete){
                    $this->io->success($elmschema.' successfully deleted');
                }
            }
        }
        else{
            $this->io->error($elmschema.' not found');
        }
    }
}
