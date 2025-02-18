<?php
namespace Jenga\App\Models\Relations;

use Jenga\App\Core\App;
use Jenga\App\Project\Core\Project;

/**
 * Handles all the respective table relationships for each result row
 * @author stanley
 */
class Relationships {
    
    /**
     * The local table
     * @var type 
     */
    public $local;
    
    /**
     * Designates the linked local column
     * @var type 
     */
    public $localcol;
    
    /**
     * Designates the secondary table
     * @var type 
     */
    public $foreign;
    
    /**
     * Designates the linked foreign column
     * @var type 
     */
    public $foreigncol;
    
    /**
     * The relationship alias
     * @var type 
     */
    public $alias = null;
    
    /**
     * Designates the pivot table
     */
    public $pivot = null;
    
    /**
     * The foreign key for the primary table in the pivot
     * @var type 
     */
    public $localpivot;
    
    /**
     * The foreign key for the secondary table in the pivot
     * @var type 
     */
    public $foreignpivot;
    
    /**
     * The column of the connected table via pivot
     * @var type 
     */
    public $foreign_connected_col;
    
    /**
     * The relationship type
     * @var type 
     */
    public $type;
    
    /**
     * Holds data directly joined to primary table
     * @var type 
     */
    private $_direct_join_data;
    
    /**
     * Maps the alias to its original name
     * @var type 
     */
    private $_alias_index = [];
    
    /**
     * Designate lazy loaded table relation
     * @var type 
     */
    public $is_eager = false;
    
    /**
     * Designates the one-to-one child relation
     */
    public $is_child = null;
    
    /**
     * The connected element
     * @var type 
     */
    protected static $elm;

    /**
     * The Join condition
     * @var type 
     */
    protected $condition = [];
    
    /**
     * Holds the rows results for each query
     * @var type 
     */
    public $rows = [];
    
    public function __construct($table) {
        $this->local = $table;
    }
    
    /**
     * Clear the rows on clone
     */
    public function __clone() {
        
        //check pivot
        if(!is_null($this->pivot) && is_object($this->pivot)){
            $this->pivot = clone $this->pivot;
        }
        
        //check foreign
        if(!is_null($this->foreign)){
            $this->foreign = clone $this->foreign;
        }
        
        $this->rows = [];
    }
    
    /**
     * Translate the direct join data
     * @param type $data
     */
    public function translateJoinData($data){
        $this->_direct_join_data = $data;
    }
    
    /**
     * Sets the alias index
     * @param type $table
     * @param type $alias
     */
    public function setIndex($table, $alias) {
        $this->_alias_index[$table] = $alias;
    }
    
    /**
     * Checks if the relationship is aliased
     */
    public function hasAlias(){
        return !is_null($this->alias);
    }
    
    /**
     * Gets the alias index
     * @param type $table
     * @return type
     */
    public function getIndex($table = null){
        
        if(!is_null($table)){
            return $this->_alias_index[$table];
        }
        
        return $this->_alias_index;
    }
    
    /**
     * Sets the join condition based on type
     * @return $this
     */
    public function setCondition(){
        
        switch ($this->type) {

            //one-to-one relationship
            case "one-to-one":
                $this->condition['left'] = $this->local.'.'.$this->localcol;
                $this->condition['right'] = $this->foreign->table.'.'.$this->foreigncol;
                break;
            
            //one-to-many relationship
            case "one-to-many":
                $this->condition['left'] = $this->local.'.'.$this->localcol;
                $this->condition['right'] = $this->foreign->table.'.'.$this->foreigncol;
                break;
            
            //many-to-many relationship
            case "many-to-many":
                
                //unset the prevous left and right keys
                unset($this->condition['left'], $this->condition['right']);
                
                //set the pivot table
                $this->condition[$this->pivot->table] = [
                    'left' => $this->local.'.'.$this->localcol,
                    'right' => $this->pivot->table.'.'.$this->localpivot
                ];
                
                //set the foreign table
                $this->condition[$this->foreign->table] = [
                    'left' => $this->foreign->table.'.'.$this->foreign_connected_col,
                    'right' => $this->pivot->table.'.'.$this->foreignpivot
                ];
                
                break;
        }        
        
        return $this;
    }
    
    /**
     * Returns JOIN condition
     * @param type $return options are array or plain
     * @return type
     */
    public function getCondition($return = 'array') {
        
        if($return == 'array')
            return [$this->condition['left'],'=', $this->condition['right']];
        elseif ($return == 'plain') 
            return $this->condition;
        
        return FALSE;
    }
    
    /**
     * Parses the element and schema values sent
     * @param type $elmschema
     * @return array Full array with the element. schema, full schema class and table
     */
    public static function parse($elmschema) {
        
        $elm = [];
        
        //parse schema
        if(strpos($elmschema, '/') === FALSE){
            $elm['element'] = strtolower($elmschema);
            $elm['schema'] = ucfirst(strtolower($elmschema)).'Schema';
        }
        else{
            $split = explode('/', $elmschema);

            $elm['element'] = strtolower($split[0]);
            $elm['schema'] = ucfirst($split[1]);
        }
        
        $element = Project::elements()[$elm['element']];
        $schemas = array_keys($element['schema']);
        
        //check if schema is present
        if(in_array($elm['schema'], $schemas)){
            
            $schmclass = 'Jenga\MyProject\\'.ucfirst($elm['element']).'\Schema\\'. ucfirst($elm['schema']);
            
            $reflect = new \ReflectionClass($schmclass);
            $props = $reflect->getProperties();
            
            foreach($props as $prop){
                
                if($prop->name == 'table'){
                    $table = $prop->getValue(new $schmclass);
                    break;
                }
            }
            
            //add table to elm
            $elm['table'] = $table;
            $elm['foreign'] = App::make($schmclass);   
            
            return self::$elm = $elm;
        }
        else{
            App::exception($elm['schema'].' not found in '.$elm['element']);
        }
    }
    
    /**
     * Gets the pivot schema class by the corresponding table name
     * @param type $table
     */
    public static function getPivotByTableName($table){
        
        //get all elements
        $elements = Project::elements();
        
        //loop through the schemas
        $schemas = [];
        foreach($elements as $name => $element){
            
            if(array_key_exists('schema', $element)){
                $schemas[$name] = array_keys($element['schema']);
            }
        }
        
        //loop through the schema to get the correct table
        foreach($schemas as $element => $schema){
            
            if(count($schema) == 1){
                
                $schmclass = 'Jenga\MyProject\\'.ucfirst($element).'\Schema\\'. ucfirst($schema[0]);
                $schm = App::make($schmclass);
                
                if($schm->table == $table){
                    return $schm;
                }
            }
            else{
                
                foreach($schema as $schm){
                    
                    $schmclass = 'Jenga\MyProject\\'.ucfirst($element).'\Schema\\'. ucfirst($schm);
                    $schm = App::make($schmclass);
                    
                    if($schm->table == $table){
                        return $schm;
                    }
                }
            }
        }
        
        return NULL;
    }
}
