<?php
namespace Jenga\App\Database\Systems\Pdo\Schema;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Helpers\Help;
use Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Schema\Builder;
use Jenga\App\Database\Systems\Pdo\Connections\DatabaseConnector;

use ReflectionClass;
use DocBlockReader\Reader;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Takes the schema class properties and creates a proper schema builder class
 * @author stanley
 */
class Processor {
    
    protected $pdo;
    
    /**
     * @var Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Schema\Builder
     */
    public $builder;
    protected $schema;
    public $activeconnection;

    private $_cache = [];
    private $_table;
    private $_vars;
    private $_annotations;
    private $_keywords;
    private $_console;
    private $_omit_keywords = ['schema\omit'];
    
    /**
     * 
     * @param DatabaseConnector $connection
     * @param ReflectionClass $schema
     * @param SymfonyStyle $io
     */
    public function __construct(DatabaseConnector $connection, ReflectionClass $schema, SymfonyStyle $io) {
        
        //get the active connection
        $this->activeconnection = $activeconnection = $connection->getActiveConnection();   
        $connections = $connection->getConnections();
        
        //initialize builder & schema
        $this->builder = $this->_setBuilder($activeconnection);
        $this->schema = $schema;
        
        //get annotations guide
        $annote_path = DATABASE .DS. 'systems' .DS. strtolower($connections['dbal']) .DS. 'config' .DS. 'annotations.php';
        $this->_keywords = require $annote_path;
        
        //assign console
        $this->_console = $io;
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
     * Returns the schema builder
     */
    public function getBuilder(){
        return $this->builder;
    }
    
    private function _resolveBuilderClass($driver){
        return 'Jenga\App\Database\Systems\Pdo\Drivers\\'.ucfirst($driver).'\Schema\Builder';
    }
    
    /**
     * Calls the schema builder directly
     * @param type $name
     * @param type $arguments
     */
    public function __call($name, $arguments) {
        
        //check the active record methods first
        if(method_exists($this->builder, $name)){
            return call_user_func_array([$this->builder, $name], $arguments);
        }
    }
    
    /**
     * Initiates the Docreader and reads annotations
     * 
     * @param type $class
     * @param type $methodproperty
     * @param type $type
     * @return DocBlockReader\Reader
     */
    private function _read($class, $methodproperty, $type = null){
        
        if(!is_null($type))
            return new Reader ($class, $methodproperty, $type);
        else
            return new Reader ($class, $methodproperty);
    }
    
    /**
     * Parse the schema table columns and read annotations
     * @param type $cols
     * @return type
     */
    public function parseColumns($cols){
        
        foreach ($cols as $col) {
            
            //set column to be accessible
            $col->setAccessible(true);
            
            //get column value
            $value = $col->getValue(new $this->schema->name);
            
            //set the builder table name
            if($col->name == 'table'){
                
                $this->_vars['table']['name'] = $value;
                $this->_table = $value;
                
                //read
                $annotations = $this->_read($col->class, $col->name, 'property');
                $this->_annotations['table'][$this->_table] = $annotations->getParameters();
            }
            else{
                
                //read
                $annotations = $this->_read($col->class, $col->name, 'property');
                $this->_annotations['columns'][$col->name] = $annotations->getParameters();
            }
        }
        
        //filter annotationed columns
        $this->_annotations['columns'] = $this->filterColumnAnnotations();
        
        //cache the compiled annotations
        $this->cacheAnnotations($this->_table, $this->_annotations);        
        return $this->_annotations;
    }
    
    /**
     * Filter the underscored and omitted variables
     * @param type $annotations
     */
    public function filterColumnAnnotations(){
        
        //filter underscored annotations
        $annotations = $this->_annotations['columns'];
        $cols = array_keys($annotations);
        
        //remove underscored cols
        $public_cols = [];
        foreach($cols as $column){
            
            //filter underscores
            if(strpos($column, '_') !== 0){
                $public_cols[$column] = $annotations[$column];
            }
        }
        
        //filter by keywords
        $tablecols = [];
        foreach($this->_omit_keywords as $omit){
            
            foreach($public_cols as $col => $attrs){
                
                //remove the omit keywords
                $attrkeys = array_keys($attrs);                
                if(!in_array($omit, $attrkeys)){                    
                    $tablecols[$col] = $attrs;
                }
            }
        }
        
        return $tablecols;
    }
    
    /**
     * Cache annotations
     * @param type $table
     * @param type $annotations
     */
    public function cacheAnnotations($table, $annotations){
        
        $cache_path = DATABASE .DS. 'systems' .DS. 'pdo' .DS. 'schema' .DS. 'cache' .DS. strtolower($table).'.cache';
        $cache = serialize($annotations);
        
        //save the cache file
        $this->_cache['path'] = $cache_path;
        $this->_cache['data'] = $cache;
    }
    
    /**
     * Starts PDO transaction and wraps all the database actions into a PDO transaction to allow for rollbacks
     */
    public function start(){
        $this->builder->getHandle()->startTransaction();
    }
    
    /**
     * Checks if pdo transaction is ongoing
     */
    public function inTransaction() {
        return $this->builder->getHandle()->inTransaction();
    }
    
    /**
     * Commits the tracked queries
     */
    public function commit(){
        $this->builder->getHandle()->commitTransaction();
    }
    
    /**
     * Rolls back transactions in case of an error
     */
    public function cancel() {
        $this->builder->getHandle()->cancelTransaction();
    }
    
    /**
     * Take the sent annotations and create builder
     * @param type $annotations
     * @return Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Schema\Builder
     */
    public function createBuilder($annotations){
        
        //get table
        $table = $annotations['table'];
        $tablename = array_keys($table)[0];
        
        //add table
        $this->builder->table($tablename);
        
        //add table attributes
        $tableattrs = $table[$tablename];        
        foreach ($tableattrs as $attr => $value) {
            
            switch ($attr) {
                
                case 'collation':
                    $this->builder->collation($value);
                    break;
                
                case 'engine':
                    $this->builder->engine($value);
                    break;
            }
        }
        
        //build columns
        return $this->_buildColumns($annotations['columns']);
    }
    
    /**
     * Save table cache
     */
    public function saveCache(){        
        return File::put($this->_cache['path'], $this->_cache['data']);
    }
    
    /**
     * Builds the table columns
     * @param type $columns
     */
    private function _buildColumns($columns){
        
        //loop through the columns
        foreach ($columns as $name => $attrs) {
            
            foreach ($attrs as $attr => $value) {
                
                switch ($attr) {
                    
                    //@var
                    case 'var':
                        
                        //switch between array and string
                        switch (gettype($value)) {
                        
                            case 'array':
                                $this->builder->column($name, $value);
                                break;
                            
                            case 'string':
                                $this->builder->column($name, [$value]);
                                break;
                        }
                        break;
                    
                    //@primary
                    case "primary":
                        $this->builder->primary($name);
                        break;
                    
                    //@comment
                    case "comment":
                        $this->builder->comment($name, $value);
                        break;
                    
                    //@foreign
                    case "foreign":                        
                        $json = json_encode($value);                        
                        $this->buildForeignKey($name, $json);
                        break;
                    
                    //@unique
                    case 'unique':
                        $this->builder->unique($name);
                        break;
                }
            }
        }
        
        return $this->builder;
    }
    
    /**
     * Builds the Foreign key
     * @param type $json
     */
    public function buildForeignKey($name, $json) {
        
        //check for json validity
        if(Help::isHtml($json)){

            $fkeys = json_decode($json, TRUE);
            $ftable = array_keys($fkeys)[0];

            //add native column
            $fsettings = array_change_key_case($fkeys[$ftable], CASE_LOWER);
            
            if(array_key_exists('column', $fsettings))
                $this->builder->foreign($name)->references($fsettings['column'])->on($ftable);
            else
                $this->_console->error("Foreign Column value must be defined");

            //check for ondelete
            if(in_array('ondelete', array_keys($fsettings))){
                $this->builder->onDelete($fsettings['ondelete']);
            }
            
            //check for onupdate
            if(in_array('onupdate', array_keys($fsettings))){
                $this->builder->onUpdate($fsettings['onupdate']);
            }
        }
        else{
            $this->_console->error('Foreign key '.$json.' not a valid JSON object');
        }
    }
    
    /**
     * Adds foreign key independently
     * @param type $name
     * @param type $json
     */
    public function addForeignKey($local, $foreign, $json) {
        
        //set local table
        $this->builder->table($local);
        
        //check for json validity
        if(Help::isHtml($json)){

            $fkeys = json_decode($json, TRUE);
            $ftable = array_keys($fkeys)[0];

            //add native column
            $fsettings = array_change_key_case($fkeys[$ftable], CASE_LOWER);
            
            if(array_key_exists('column', $fsettings)){
                $refcolumn = $fsettings['column'];
            }
            else{
                $this->_console->error("Foreign Column value must be defined");
            }
            
            //check for ondelete
            if(in_array('ondelete', array_keys($fsettings))){
                $ondelete = $fsettings['ondelete'];
            }
            
            //check for onupdate
            if(in_array('onupdate', array_keys($fsettings))){
                $onupdate = $fsettings['onupdate'];
            }
            
            return $this->builder->addForeignKey($refcolumn, $foreign, $ondelete, $onupdate);
        }
        else{
            $this->_console->error('Foreign key '.$json.' not a valid JSON object');
        }
    }
}
