<?php
namespace Jenga\App\Database\Build;

use Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Processors\QueryProcessor;

/**
 * The holds the query information
 *
 * @author stanley
 */
class Query {
    
    /**
     * The table prefix
     * @var type 
     */
    public $prefix;
    
    /**
     * The db table
     * @var type 
     */
    public $table;
    
    /**
     * The table primary key
     * @var type 
     */
    public $primarykey;
    
    /**
     * The query arguments
     * @var type 
     */
    public $data = [];
    
    /**
     * The query statement
     * @var type 
     */
    public $stmt = [];
    
    /**
     * Holds the retained query
     * @var type 
     */
    private $_query_store;
    
    /**
     * The query commands
     * @var type 
     */
    private $_commands = ['SELECT','SELECTRAW','INSERT','UPDATE','DELETE','CALL','REPLACE','DO'];
    
    /**
     * This will align the MySql keywords into the statement
     * 
     * @param type $name
     * @param type $arguments
     */
    public function __call($name, $arguments){   
        
        //clear empty arguments
        $args = array_filter($arguments,[$this, '_filterArgs']);
        
        //add commands to top of array
        if(in_array(strtoupper($name), $this->_commands)){
            
            $cmds = array_keys($this->stmt);
            
            //check if command exists as first entry
            if(count($cmds) > 0 && !in_array(strtoupper($cmds[0]), $this->_commands)){
                $this->stmt = [$name => [$args]] + $this->stmt;
            }
            else{
                $this->stmt[$name][] = $args;
            }
        }
        else{
            
            //clear limit array
            if($name == 'limit' && array_key_exists($name, $this->stmt) && count($this->stmt[$name]) > 0){
                unset($this->stmt[$name]);
            }
            
            $this->stmt[$name][] = $args;
        }
    }
    
    /**
     * Filters query arguments
     * @param type $item
     * @return boolean
     */
    private function _filterArgs($item){        
        if(is_int($item) || is_string($item) || is_bool($item) || is_array($item) || is_object($item) || is_double($item) || is_float($item)){
            return TRUE;
        }
    }
    
    /**
     * Return any query properties
     * 
     * @param type $name
     * @return boolean
     */
    public function __get($name) {
        
        if(array_key_exists($name, $this->data))
            return end($this->data[$name]);
        
        return FALSE;
    }
    
    /**
     * Sets the query properties
     * 
     * @param type $name
     * @param type $value
     */
    public function __set($name, $value) {
        $this->data[$name][] = $value;
    }
    
    /**
     * On clone clear query store
     */
    public function __clone() {
        $this->_query_store = [];
        $this->stmt = [];
    }
    
    /**
     * If unset is called for any query property
     * @param type $name
     */
    public function __unset ($name) {
        unset ($this->data[$name]);
    }
    
    /**
     * Convert the query object into a full query & execute
     */
    public function runProcessor($retain_query){
        
        $processor = new QueryProcessor($this);
        $mysql_query = $processor->translate();
        
        if($retain_query){
            
            $this->_query_store['stmt'] = $this->stmt;
            $this->_query_store['data'] = $this->data;
        }
        
        //unset stmt and data
        $this->stmt = [];
        $this->data = [];
        
        return $mysql_query;
    }
    
    /**
     * Returns the stored query
     * @param type $type
     * @return type
     */
    public function returnStoredQuery($type = null){
        
        if(!is_null($type)){
            return $this->_query_store[$type];
        }
        
        return $this->_query_store;
    }
    
    /**
     * Checks if sent keyword is in the present query
     * @param type $keyword
     */
    public function isKeywordInQuery($keyword){
        return array_key_exists($keyword, $this->stmt);
    }
}
