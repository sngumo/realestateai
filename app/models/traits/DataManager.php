<?php
namespace Jenga\App\Models\Traits;

use Jenga\App\Database\Systems\Pdo\Schema\Tasks;

use Carbon\Carbon;
use ReflectionObject;
use ReflectionProperty;
use DocBlockReader\Reader;

/**
 * Manages all the data sent from the Active Record 
 * and also allows for protected class properties to be assigned
 * 
 * @author stanley
 */
trait DataManager {
    
    /**
     * Created_at timestamp
     * @schema\omit
     */
    private $_created_at = 'created_at_';
    
    /**
     * Updated at timestamp
     * @var type 
     * @schema\omit
     */
    private $_updated_at = 'updated_at_';
    
    /**
     * Checks simple join
     * @var boolean
     * @schema\omit
     */
    public $is_joined = false;
    
    /**
     * Checks for join type either direct or mapped
     * @var type 
     * @schema\omit
     */
    private $_join_type = null;
    
    /**
     * Holds the join condition
     * @var type 
     */
    private $_join_condition;
    
    /**
     * Determine the MySql join type direction
     * @var type 
     * @schema\omit
     */
    private $_join_direction;
    
    /**
     * Holds data for the simple joins
     * @var type 
     * @schema\omit
     */
    private $_join_data = [];
    
    /**
     * Holds the join update data
     * @var type 
     * @schema\omit
     */
    private $_join_update = [];

    /**
     * Holds the joined tables for the simple joins
     * @var type 
     * @schema\omit
     */
    private $_join_tables = [];
    
    /**
     * Holds the auxillary data in the schema
     * @var type 
     * @schema\omit
     */
    private $_aux_data = [];
    
    /**
     * Holds all the data for the retrieved record
     * @var type 
     * @schema\omit
     */
    public $data =  [];
    
    /**
     * The properties to omit when the schema is closed
     * @var type 
     */
    private $_omit = ['is_joined'];
    
    /**
     * Initializes the Tasks class
     */
    public function tasks(){
        return new Tasks($this);
    }
    
    /**
     * Sets the initial results for the schema
     * @param type $result
     */
    public function setInitialSchemaData($result){
        $this->data[$this->_created_at] = $result;
    }
    
    /**
     * Sets the auxillary data
     * @param type $name
     * @param type $value
     */
    public function saveToAuxData($name, $value){
        $this->_aux_data[$name] = $value;
    }
    
    /**
     * Retrieves from the aux data
     * @param type $name
     */
    public function getAuxData($name){
        if(array_key_exists($name, $this->_aux_data)){
            return $this->_aux_data[$name];
        }
        
        return NULL;
    }
    
    /**
     * Returns fillable columns in the schema
     * @param type $add_annotations
     * @return type
     */
    public function getFillableColumns($add_annotations = false){
        
        //get all public columns
        $columns = $this->close();
        
        //remove the table
        unset($columns['table'], $columns['data']);
        
        //if true add the annotation information
        if($add_annotations){
            
            foreach(array_keys($columns) as $property){
                $annotaions =  new Reader ($this, $property, 'property');
                $columns[$property] = $annotaions->getParameters();
            }
        }
        
        return $columns;
    }
    
    /**
     * Checks if var is saved in aux data
     * @param type $name
     */
    public function isVarInAuxData($name){
        return array_key_exists($name, $this->_aux_data);
    }
    
    /**
     * Use reflection to get schema properties
     * @param type $schema
     * @param type $filter
     * @return type
     */
    private function reflectSchema($schema, $filter){
        
        $reflectedObject = new ReflectionObject($schema);
        
        if(is_null($filter))
            return $reflectedObject->getProperties();
        else
            return $reflectedObject->getProperties($filter);
    }
    
    /**
     * Get schema properties
     * @param type $schema
     * @param type $filter
     * @return type
     */
    private function _getSchemaProperties($schema){
        
        $props = [];
        $filters = [ 'public' => ReflectionProperty::IS_PUBLIC, 'protected' => ReflectionProperty::IS_PROTECTED];        
        
        foreach($filters as $type => $filter){
            $props[$type] = $this->reflectSchema($schema, $filter);
        }
        
        return $this->_filterProps($props, 'name');
    }
    
    /**
     * Filters the class properties and only brings the filtered ones
     * @param type $filter
     */
    private function _filterProps($props, $filter) {
        
        $list = [];
        foreach ($props as $type => $prop) {
            
            if($type == 'public'){
                
                foreach($prop as $reflectProp){
                    $name =  $reflectProp->{$filter};  
                    
                    if(!in_array($name, $this->_omit))
                        $list[$type][] = $name;
                }
            }
            elseif($type == 'protected'){
                
                if(count($prop) > 0){
                    
                    foreach($prop as $reflectProp){
                        $reflectProp->setAccessible(TRUE);
                        $name = $reflectProp->{$filter};
                        
                        if(!in_array($name, $this->_omit))
                            $list[$type][] = $name;
                    }
                }
            }
        }
        
        return $list;
    }
    
    /**
     * Inspects the schema class and returns the set property values
     */
    public function close(){
        
        $this->setUpdateTime();
        $props = $this->_getSchemaProperties($this);
        
        $data = [];
        foreach($props as $type => $prop){
            
            if($type == 'public'){
                foreach($prop as $property){
                    $data[$property] = $this->{$property};
                }
            }
            elseif($type == 'protected'){
                foreach($prop as $property){
                    $data[$property] = $this->{'get'.ucfirst($property)}();
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Sets the time the schema was instantiated
     * @param type $time
     */
    public function setCreationTime($time = null){
        
        //clear existing timestamps
        $this->resetTimeStamps();
        
        //set the new timestamps
        if(is_null($time))
            $this->_created_at .= Carbon::now()->toDateTimeString();
        else
            $this->_created_at .= $time;
    }
    
    /**
     * Sets the time the schema data was retrieved for db updates
     * @param type $time
     */
    public function setUpdateTime($time = null){
        
        if(is_null($time)){
            
            if($this->_updated_at == 'updated_at_'){
                $this->_updated_at .= Carbon::now()->toDateTimeString();
            }
        }
        else
            $this->_updated_at .= $time;
    }
    
    /**
     * Returns created at time
     * @return type
     */
    public function getCreationTime(){
        return $this->_created_at;
    }
    
    /**
     * Returns updated at time
     * @return type
     */
    public function getUpdateTime(){
        return $this->_updated_at;
    }
    
    /**
     * Resets the schema timestamps
     */
    public function resetTimeStamps(){        
        $this->_created_at = 'created_at_';
        $this->_updated_at = 'updated_at_';
    }
    
    /**
     * Returns join data
     * @return type
     */
    public function getJoinData($table = null){
        
        if(!is_null($table)){
            
            if(in_array($table, $this->_join_tables))
                return $this->_join_data[$table];
            else{
                return NULL;
            }
        }
        
        return $this->_join_data;
    }
    
    /**
     * Set join data
     * @param type $table
     * @param type $col
     * @param type $value
     */
    public function setJoinData($table, $col, $value){      
        $this->_join_data[$table][$col] = $value;
    }
    
    /**
     * Sets Join data
     * @param type $table
     */
    public function setJoinTable($table){        
        $this->_join_tables[] = $table;
    }
    
    /**
     * Get the joined tables
     */
    public function getJoinTables(){
        return $this->_join_tables;
    }
    
    /**
     * Evaluates if join type is set
     */
    public function isJoinTypeSet() {
        return is_null($this->_join_type);
    }
    
    /**
     * Sets join type
     * @param type $type
     */
    public function setJoinType($type){
        
        if($type == 'direct' || $type == 'mapped' || $type == 'queued'){
            $this->_join_type = $type;
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * Set MySql join direction
     * @param type $direction
     */
    public function setJoinDirection($direction){
        $this->_join_direction = $direction;
    }
    
    /**
     * Sets the join condition
     * @param type $joined_table
     * @param type $lefttablecol
     * @param type $operator
     * @param type $righttablecol
     */
    public function setJoinCondition($joined_table, $lefttablecol, $operator, $righttablecol){
        
        $this->_join_condition[$joined_table]['left'] = $lefttablecol;
        $this->_join_condition[$joined_table]['operator'] = $operator;
        $this->_join_condition[$joined_table]['right'] = $righttablecol;
    }
    
    /**
     * Returns the join type
     * @return type
     */
    public function getJoinType(){
        return $this->_join_type;
    }
    
    /**
     * Get MySql join condition
     * @param type $table
     * @return type
     */
    public function getJoinCondition($table) {
        return $this->_join_condition[$table];
    }
}
