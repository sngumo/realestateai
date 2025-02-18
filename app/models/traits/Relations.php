<?php
namespace Jenga\App\Models\Traits;

use Jenga\App\Core\App;
use Jenga\App\Models\Relations\Relationships;

/**
 * This class connects the various schemas for the tables within the project
 *
 * @author stanley
 */
trait Relations {
    
    /**
     * Holds the relationship data for the element
     * @var Relationships
     */
    protected $relations = [];
    
    /**
     * All relations to be processed within a model are queued here
     * @var type 
     */
    protected $queue = [];
    
    /**
     * Flag to check if queue is closed
     * @var type 
     */
    protected $queue_closed = FALSE;

    /**
     * Sets the primary table relation instance
     * @param type $name
     * @return Relationships
     */
    private function _setRelationship($name){
        
        $relationship = App::make(Relationships::class, ['table' => $this->schema->table]);
        $this->relations[$name] = $relationship;
        
        return $relationship;
    }
    
    /**
     * Return the most current relation
     * @return Relationships
     */
    protected function getCurrentRelation($return_array = false) {   
        
        $relate = array_keys($this->relations);
        $name = end($relate);
        
        if($return_array){
            return [$name => $this->relations[$name]];
        }
            
        return $this->relations[$name];
    }
    
    /**
     * Checks if relations queue is closed
     */
    public function isQueueClosed() {
        return $this->queue_closed;
    }
    
    /**
     * Closes the relations queue
     */
    public function closeQueue() {
        $this->queue_closed = TRUE;
    }
    
    /**
     * Opens relations queue
     */
    public function openQueue() {
        $this->queue_closed = FALSE;
    }
    
    /**
     * Clears the queue in the existing model
     */
    public function clearQueue(){
        $this->queue = [];
        $this->record->clearQueue();
        
        return $this;
    }
    
    /**
     * Sets the relation to only be fully loaded on demand
     */
    public function lazy(){        
        
        $relation = $this->getCurrentRelation();
        $relation->is_eager = FALSE;
        
        return $this;
    }
    
    /**
     * Sets relation to be loaded immediately when the model loads
     * @return $this
     */
    public function isEager() {
        
        $relation = $this->getCurrentRelation();
        $relation->is_eager = TRUE;
        
        return $this;
    }
    
    /**
     * Returns all the mapped relations
     */
    public function getMappedRelations(){
        return $this->relations;
    }
    
    /**
     * Defines the one-to-one table relationship
     * @param type $element_schema
     * @param string $foreignkey  The foreign column in the current table 
     * @param string $localkey The corresponding column in the other table
     * @return $this
     */
    public function hasOne($element_schema, $foreignkey = null, $localkey = null, $isChild = false){
        
        $elm = Relationships::parse($element_schema);  
        
        //if schema is set use it as name
        if(strpos($element_schema, '/') !== FALSE){
            $name = $elm['schema'];
        }
        else{
            $name = $elm['element'];
        }
        
        $relation = $this->_setRelationship($name); 
        $relation->is_child = $isChild;
        
        //set foreign key
        if(is_null($foreignkey)){
            $foreignkey = $elm['foreign']->table.'_id';
        }
        
        //set local key
        if(is_null($localkey)){
            $localkey = 'id';
        }
        
        $relation->foreign = $elm['foreign'];
        $relation->foreigncol = $localkey;
        $relation->localcol = $foreignkey;
        
        $relation->type = 'one-to-one';
        
        //set the rest
        $this->_setConditionAndSchema($relation, $elm); 
        
        return $this;
    }
    
    /**
     * Defines a one-to-one child relationship
     * @param type $element_schema
     * @param type $foreignkey
     * @param type $localkey
     */
    public function hasOneChild($element_schema, $foreignkey = null, $localkey = null){
        
        //check foreign key
        if(is_null($foreignkey)){
            $foreignkey = 'id';
        }
        
        //check local key
        if(is_null($localkey)){
            $localkey = $this->schema->table.'_id';
        }
        
        return $this->hasOne($element_schema, $foreignkey, $localkey, true);
    }
    
    /**
     * Inverse of the one-to-one parent relationship
     * @param type $element_schema
     * @param type $foreignkey
     * @param type $localkey
     * @return type
     */
    public function hasOneParent($element_schema, $foreignkey = null, $localkey = null){
        return $this->hasOneChild($element_schema, $foreignkey, $localkey);
    }
    
    /**
     * Defines the one-to-many table relationship 
     * or a many-to-many relationship when followed with the via() method
     * 
     * @param type $element_schema
     * @param string $foreignkey The foreign column in the other table or the foreign column in the pivot table
     * @param string $localkey The local column in the current table or the linked column in the pivot table
     * @return $this
     */
    public function hasMany($element_schema, $foreignkey = null, $localkey = null){
        
        //set foreign key
        if(is_null($foreignkey)){ $foreignkey = $this->schema->table.'_id'; }
        
        //set local key
        if(is_null($localkey)){  $localkey = 'id'; }
        
        $elm = Relationships::parse($element_schema);
        
        //if schema is set use it as name
        if(strpos($element_schema, '/') !== FALSE){
            $name = $elm['schema'];
        }
        else{
            $name = $elm['element'];
        }
        
        $relation = $this->_setRelationship($name);        
        
        $relation->foreign = $elm['foreign'];
        $relation->foreigncol = $foreignkey;
        $relation->localcol = $localkey;
        
        $relation->type = 'one-to-many';
        
        //set the rest
        $this->_setConditionAndSchema($relation, $elm);        
        
        return $this;
    }
    
    /**
     * Declares pivot table. By convention, the pivot table should be the names on the connected tables 
     * linked using an underscore
     * 
     * @param type $connectingtable
     * @param type $linkedforeign this is the linked column in the final table
     * @param type $linkedlocal this is the linked column in the current table
     * @return $this
     */
    public function via($connectingtable, $linkedforeign = null, $linkedlocal = null){
        
        $relation = $this->getCurrentRelation();
        
        //get the pivot schema instance
        if(strpos($connectingtable, '/') !== FALSE){
            
            $parts = explode('/', $connectingtable);
            $schmclass = 'Jenga\MyProject\\'.ucfirst($parts[0]).'\Schema\\'. ucfirst($parts[1]);
            
            $pivot = App::make($schmclass);
        }
        else{
            $pivot = Relationships::getPivotByTableName($connectingtable);
        }
        
        //set foreign key              
        $relation->foreignpivot = $relation->foreign->table.'_id'; //this is the foreign column linking the second table
        
        //set local key              
        $relation->localpivot = $relation->foreigncol; //this is the foreign column linking the main table
        
        //set the column for the connected table
        if(is_null($linkedforeign)){ $fcol = 'id'; }  
        $relation->foreign_connected_col = $fcol;
        
        //overwrite the local column if need be
        if(!is_null($linkedlocal)){ 
            $relation->localcol = $linkedlocal; 
        }  
        
        //overwrite previous setting to many-to-many
        $relation->type = 'many-to-many';
        
        //set pivot
        if(!is_null($pivot))
            $relation->pivot = $pivot;
        else
            App::critical_error ('Table: '.$connectingtable.' not found in the elements');
        
        //finish many-to-many setup
        $this->_setConditionAndSchema($relation);
        
        return $this;
    }
    
    private function _setConditionAndSchema($relation, $schema = null){
        
        //set join condition
        $relation->setCondition();
        
        //set schema join flag 
        if(!is_null($schema)){            
            $this->schema->is_joined = TRUE;
            $this->schema->setJoinType('queued');
        }
        
        //also add to queue
        $this->setRelationsQueue($schema['schema']);
    }
    
    /**
     * Set relation to queue
     * @param type $name
     * @return boolean
     */
    protected function setRelationsQueue($name){
        
        if($this->queue_closed === FALSE){
            $this->queue[] = $name;
            return TRUE;
        }
        else{
            
            //set relation as active touchpoint
            $this->_act_on_relation = TRUE;
            $relation = $this->getCurrentRelation(TRUE);
            
            //set to queue
            $this->queue[] = array_keys($relation)[0];
            
            //also set relation immediately as active          
            $this->record->queueRelations($relation);
            $this->record->setActiveRelations($relation, TRUE);
        }
        
        return FALSE;
    }
    
    /**
     * Turns relation to eager
     * @param type $name
     */
    public function activateRelation($name){
        
        //set active relation
        $this->setRelationsQueue($name);
    }
    
    /**
     * Checks if the model has attached relations
     * @return type
     */
    public function hasRelations() {
        return count($this->relations) > 0;
    }
    
    /**
     * Returns all linked relations
     * @return type
     */
    public function getAllRelations(){
        return $this->relations;
    }
    
    /**
     * Returns a specific relation whether active or not
     * @param type $name
     */
    public function getSpecificRelation($name) {
        return $this->relations[$name];
    }
    
    /**
     * Returns the active relation
     * @return type
     */
    public function getActiveRelations($name = null){
        
        if($this->hasActiveRelation()){
            
            if(is_array($this->active_relations) && is_null($name)){
                
                //return all the set relations
                foreach($this->active_relations as $name){
                    $relation = $this->relations[$name];
                    
                    //filter only for eager relations
                    if($relation->is_eager){
                        $relations[$name] = $relation;
                    }
                }
                
                return $relations;
            }
            elseif(is_array($this->active_relations) && !is_null($name)){
                
                if(!in_array($name, $this->active_relations)){
                    App::warning($name.' table relation has not been found');
                }
            }
            
            return $this->relations[$name];
        }
        
        return NULL;
    }
    
    /**
     * Checks if relations have been queued in the model
     * @return type
     */
    public function hasQueuedRelationsInModel($name = null){        
        if(count($this->queue) > 0){            
            if(!is_null($name)){
                return in_array($name, $this->queue);
            }
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * Define the relationship alias
     * @param type $alias
     * @return $this
     */
    public function alias($alias) {
        
        $relate = array_keys($this->relations);
        $name = end($relate);
        $rship = $this->relations[$name];
        
        //set the alias index
        $rship->setIndex($name, $alias);
        
        //remove the name
        unset($this->relations[$name]);
        
        //replace with alias
        $rship->alias = $alias;
        $this->relations[$alias] = $rship;
        
        //check queued relations
        if(in_array($name, $this->queue)){
            
            //remove and add the new alias
            $pos = array_search($name, $this->queue);
            unset($this->queue[$pos]);
            
            $this->queue[$pos] = $alias;
        }
        
        //check for active relations in record & replace it
        if(!is_null($this->record) && $this->record->hasActiveRelation()){
            
            //clear queue and active
            $this->record->clearQueue($name);
            $this->record->unsetActiveRelation($name);
            
            //set active and queued relation
            $this->record->setToQueue([$alias => $rship]);
            $this->record->setActiveRelations([$alias => $rship], TRUE);
        }
        
        return $this;
    }
}
