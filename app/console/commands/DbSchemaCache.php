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
use Jenga\App\Database\Systems\Pdo\Connections\DatabaseConnector;

use ReflectionClass;

/**
 * Controls the cached tables in the Jenga Database manager
 * @author stanley
 */
class DbSchemaCache extends Command {
    
    public $cache_path = DATABASE .DS. 'systems' .DS.'pdo' .DS. 'schema' .DS. 'cache';
    
    protected function configure() {
        
        $this->setName('db:schema:cache')
                ->setDescription('Controls the cached tables in the Jenga Database Manager')
                ->addOption('refresh',NULL, InputOption::VALUE_NONE,
                        'Compares the existing tables and the cache and removes all the unattached cache files');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $io = new SymfonyStyle($input, $output);
        
        //get database connector
        $conn = App::get(DatabaseConnector::class);
        $connect = $conn::connect();
        
        //get the schema builder
        $builder = $this->_setBuilder($connect->getActiveConnection());
        
        if($input->getOption('refresh')){            
            $this->refreshAllTableCache($builder, $connect, $io);
        }
    }
    
    protected function refreshAllTableCache($builder, $connect, $io){
        
        $tables = array_values($builder->getAllTables());
        $prefix = $connect->getActiveConnection()['prefix'];

        //get cache files
        $cache  = File::scandir($this->cache_path);

        //loop through the files and delete accordingly
        $trash = [];
        foreach($cache as $file){
            
            $explode = explode(DS, $file);
            $ex = end($explode);
            $name = str_replace('.cache','',$ex);

            //delete if cache file isnt in table
            if(!in_array($prefix.$name, $tables)){

                //delete cache file
                $result = File::delete($file);
                
                //add to trash
                if($result) $trash[] = $file;
            }
        }

        if(count($trash) > 0){
            $io->success("Database cache has been refreshed. ".count($trash)." files deleted");
        }
        else{
            $io->note('No changes made to cache ');
        }
    }
    
    /**
     * Assign schema builder
     * @param type $aconn
     * @return Builder instance of SchemaBuilder
     */
    private function _setBuilder($aconn) {
        
        $builderclass = $this->_resolveBuilderClass($aconn['driver']);
        $builder = App::make($builderclass, ['prefix' => $aconn['prefix']]);
        
        return $builder;
    }
    
    /**
     * Get the correct database driver class
     * @param type $driver
     * @return type
     */
    private function _resolveBuilderClass($driver){
        return 'Jenga\App\Database\Systems\Pdo\Drivers\\'.ucfirst($driver).'\Schema\Builder';
    }
}
