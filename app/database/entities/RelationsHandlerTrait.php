<?php
namespace Jenga\App\Database\Entities;

use Jenga\App\Core\App;
use Jenga\App\Models\Utilities\DB;
use Jenga\App\Models\Relations\Conditions;
use Jenga\App\Models\Relations\Relationships;
use Jenga\App\Models\Interfaces\SchemaInterface;

/**
 * The relation handling methods in the ActiveRecord
 * @author stanley
 */
trait RelationsHandlerTrait {
    
    /**
     * Active Relation flag
     * @var type 
     */
    private $_has_active_relations = false;
    
    /**
     * Holds the active relations
     * @var type 
     */
    private $_active_relations = [];
    
    /**
     * The list of relations to be embedded
     * @var type 
     */
    private $_relations_queue = [];
    
    /**
     * Handles the relation operation
     * @var type 
     */
    private $_handler;
    
    /**
     * Sets the mapped relationships
     * @param type $relations
     */
    public function setActiveRelations($relations, $is_active = FALSE){
        
        if(is_array($relations)){
            foreach($relations as $name => $relation){
                
                //clear all previous relations
                $this->_active_relations = [];
                
                if(is_object($relation)){
                    $this->_active_relations[$name] = clone $relation;
                }
            }
        }
        
        $this->_has_active_relations = $is_active;
        return TRUE;
    }
    
    /**
     * Activate relation
     * @param type $name
     * @param type $relation
     */
    public function activateRelation($name, $relation){
        
        $this->_active_relations[$name] = $relation;
        $this->_has_active_relations = TRUE;
    }
    
    /**
     * Return relations queue
     * @param type $name
     * @return type
     */
    public function getQueue($name = null) {
        
       if(!is_null($name)){
           return $this->_relations_queue[$name];
       }
        
       return $this->_relations_queue;
    }
    
    /**
     * Checks if relation is queued
     * @param type $name
     */
    public function isQueued($name){
        return array_key_exists($name, $this->_relations_queue);
    }
    
    /**
     * Adds the embedded relation to the queue
     * @param type $relations
     */
    public function setToQueue($relations){
        $this->_relations_queue = $relations;
    }
    
    /**
     * Set the relations to queue
     * @param type $relations
     */
    public function queueRelations($relations){
        
        foreach($relations as $name => $relation){
            $this->_relations_queue[$name] = $relation;
        }
    }
    
    /**
     * Unset the named active relation
     * @param type $name
     * @return boolean
     */
    public function unsetActiveRelation($name = null){
        
        if(!is_null($name)){
            if(array_key_exists($name, $this->_active_relations)){  

                unset($this->_active_relations[$name]);
                $this->_has_active_relations = false;
                return TRUE;
            }
        }
        else{
            //unset all active relations
            $this->_active_relations = [];
        }
        
        //set flag
        $this->_has_active_relations = false;
        
        return FALSE;
    }
    
    /**
     * Demotes the active relation
     * @param type $name
     */
    public function demoteActiveRelation($name){
        
        if(array_key_exists($name, $this->_active_relations)){
            
            $relation = $this->_active_relations[$name];
            $this->unsetActiveRelation($name);

            if(!is_null($relation)){
                $this->related_rows[$name] = $relation;
            }
        }
    }
    
    /**
     * Removes the 
     * @param type $name
     */
    public function resetActiveRelationRows($name = null) {
        
        if(is_null($name)){
            
            foreach($this->_active_relations as $name => $relation){

                $this->_active_relations[$name] = clone $relation;
                $$this->_active_relations[$name]->rows = [];
            }
        }
        else{
            
            $relation = $this->_active_relations[$name];
            unset($this->_active_relations[$name]);
            
            $this->_active_relations[$name] = clone $relation;
            $this->_active_relations[$name]->rows = [];
        }
    }
    
    /**
     * The active relations are re-saved into their relevant Relationship instances
     * @param type $relations
     */
    public function saveActiveRelationsToRows($id, $relations) {
        
        foreach ($relations as $name => $relation) {
          
            if(array_key_exists($name, $this->_active_relations)){ 
                
                $this->_active_relations[$name] = clone $relation;
                $this->_active_relations[$name]->rows[$id][] = $relation->foreign;
            }
        }
    }
    
    /**
     * Save to relations rows
     * @param type $name
     * @param type $id
     * @param type $rows
     */
    public function saveToRelationRows($name, $id, array $rows) {
        $this->_active_relations[$name]->rows[$id] = $rows;
    }
    
    /**
     * Clears the relations queue
     */
    public function clearQueue($name = null){
        
        if(!is_null($name))
            unset($this->_relations_queue[$name]);
            
        $this->_relations_queue = [];
    }
    
    /**
     * Clears the rows linked to any relation
     * @param type $name
     */
    public function clearRelationRows($name){
        $this->_active_relations[$name]->rows = [];
    }
    
    /**
     * Clears the pivot linked to any relation
     * @param type $name
     */
    public function clearRelationPivot($name){
        $this->_active_relations[$name]->pivot = null;
    }
    
    /**
     * Adds an array of the linked foreign schema
     * @param type $name
     * @param type $ids
     */
    public function pointRelationPivotToRows($name, $ids){
        
        $this->clearRelationPivot($name);
        $this->_active_relations[$name]->pivot = $ids;
    }
    
    /**
     * Returns the mapped relations
     * @return Relationships
     */
    public function getActiveRelations($name = null) {
        
       if(!is_null($name)){
           return $this->_active_relations[$name];
       }
        
       return $this->_active_relations;
    }
    
    /**
     * Check if a relation has been activated
     * @return type
     */
    public function hasActiveRelation(){
        return $this->_has_active_relations;
    }
    
    /**
     * Checks if there are relations on queue
     * @return type
     */
    public function hasQueuedRelations(){
        return count($this->_relations_queue) > 0;
    }
    
    /**
     * Check if relation is in queue
     * @param type $name
     */
    public function isRelationInQueue($name){        
        return array_key_exists($name, $this->_relations_queue);
    }
    
    /**
     * Checks if relation is present in the related rows
     * @param type $name
     * @return type
     */
    public function isRelationInRows($name){
        
        if(count($this->related_rows) > 0){
            return array_key_exists($name, $this->related_rows);
        }
        
        return FALSE;
    }
    
    /**
     * 
     * @param type $schema
     * @param type $relation
     */
    protected function convertSchemaFromArray(&$schema, $relation) {
            
        //set foreign column
        $column = $relation->foreigncol;
        $schema_class = get_class($relation->foreign);

        //check if foreign key is attached
        $_fk_attached = true;

        //get schema info
        $creationtime = $this->schema->getCreationTime();

        //if foreign column id is set, check for previous record
        if(array_key_exists($column, $schema)){
            $model = DB::schema($schema_class)->where($column, $schema[$column])->first();

            //if not found create new model
            if(is_null($model)){

                $_fk_attached = FALSE;
                $model = DB::schema($schema_class);
            }
        }
        elseif(array_key_exists($column, $this->schema->data[$creationtime])){

            $data = $this->schema->data[$creationtime];
            $model = DB::schema($schema_class)->where($column, $data[$column])->first();

            //if not found create new model
            if(is_null($model)){

                $_fk_attached = FALSE;
                $model = DB::schema($schema_class);
            }
        }
        else{

            $_fk_attached = FALSE;
            $model = DB::schema($schema_class);
        }

        //check if foreign key is attached in schema
        if($_fk_attached === FALSE && !array_key_exists($column, $schema)){

            switch ($relation->type) {

                case 'many-to-many':
                    App::warning('The schema cannot be an array is a many-to-many relationship');
                    break;

                default:

                    //get the condition
                    $condition = $relation->getCondition();
                    $localcol = explode('.',$condition[0])[1];
                    $fkcol = explode('.',$condition[2])[1];

                    //set the foreign key from the schema local key
                    $data = $this->schema->data[$creationtime];
                    $model->{$fkcol} = $data[$localcol];
                    break;
            }
        }

        //map keys to columns
        foreach($schema as $column => $value){
            $model->{$column} = $value;
        }

        //turn schema into object
        $schema = $model->schema;
    }
    
    /**Save the data linked to the related schema
     * @param type $schema
     */
    public function saveToRelatedSchema($schema, $pivotcols){
        
        //get active relation
        $active = array_values($this->getActiveRelations())[0];
        
        //convert schema if array
        if(is_array($schema)){
            $this->convertSchemaFromArray($schema, $active);
        }
        
        //set handler and query settings
        $this->_handler['query'] = clone $this->_query;
        $this->setTable($schema->table, $this->_handler['query']);
        
        //switch between types
        switch ($active->type) {
            case 'one-to-one':
                return $this->saveOneToOne($active, $schema);
                //break;

            case 'one-to-many':
                return $this->saveOneToMany($active, $schema);
                //break;
            
            case 'many-to-many':
                return $this->saveToManyToMany($active, $schema, $pivotcols);
                //break;
        }
    }
    
    /**
     * Attaches schema to record based on relation
     * @param mixed $schema
     */
    public function attach($schema, $pivotcols = null){
        
        //set handler and query settings
        $this->_handler['query'] = clone $this->_query;
        
        //get active relation
        $active = array_values($this->getActiveRelations())[0];
        
        //switch between types
        switch ($active->type) {
            
            case "one-to-one":
                $response = $this->attachOneToOne($schema, $active);
                break;
            
            case "one-to-many":
                $response = $this->attachOneToMany($schema, $active);
                break;
            
            case "many-to-many":
                $response = $this->attachManyToMany($schema, $active, $pivotcols);
                break;
        }
        
        //demote the relationship
        $this->demoteActiveRelation($active->alias);
        return $response;
    }
    
    /**
     * Attaches schema with one-to-one relationship
     * @param SchemaInterface $schema
     */
    protected function attachOneToOne(SchemaInterface $schema, Relationships $relation) {
        
        //get schema and condition
        $condition = Conditions::parse($relation->getCondition());
        
        //check child or parent
        if($relation->is_child){
            
            //set the child schema
            $localschema = $schema;
            
            //set the parent col
            $localschema->{$condition['foreign']['col']} = $this->schema->id;
        }
        else{
            $localschema = $this->schema;
        }
        
        //set the schema
        $localschema->{$condition['main']['col']} = $schema->{$condition['foreign']['col']};
        
        //save schema
        $this->setTable($localschema->table, $this->_handler['query']);
        $this->_saveForeignOrPivotSchema($localschema);
        
        //reset table if hasOneChild
        if($relation->is_child){
            $this->setTable($this->schema->table);
        }
        
        return $this->hasNoErrors();
    }
    
    /**
     * Attaches schema with one-to-many relationship
     * @param mixed $schema
     * @param Relationships $relation
     */
    protected function attachOneToMany($schema, Relationships $relation) {
        
        //get the condition
        $condition = Conditions::parse($relation->getCondition());
        
        //get schema 
        if(is_array($schema)){
            
            //set the table
            $this->setTable($relation->foreign->table, $this->_handler['query']);
            $fk = $this->getPrimaryKey($relation->foreign->table);
            
            foreach($schema as $id){
                $this->_handler['query']->where($fk,'=',$id,'OR');
            }
            
            //parse query and execute
            $this->_parseQuery(FALSE, $this->_handler['query']);        
            $results = $this->_dbhandle->fetchAll();
            
            //get schema and assign foreign key value
            $schemas = $this->mapResultsToSchema($results, $relation->foreign);
            foreach($schemas as $schema){
                
                //assign and save new schema
                $schema->{$condition['foreign']['col']} = $this->schema->{$condition['main']['col']};
                $this->_saveForeignOrPivotSchema($schema);
            }
            
            return $this->hasNoErrors();
        }
        elseif($schema instanceof SchemaInterface){
            
            //set the schema and condition
            $foreignschema = $schema;            
            
            //set the schema
            $foreignschema->{$condition['foreign']['col']} = $this->schema->{$condition['main']['col']};
            
            //save schema
            $this->setTable($foreignschema->table, $this->_handler['query']);
            $this->_saveForeignOrPivotSchema($foreignschema);
        }        
        
        return $this->hasNoErrors();
    }
    
    /**
     * Attaches schema with many-to-many relationship
     * @param mixed $schema
     * @param Relationships $relation
     * @param type $pivotcols
     */
    protected function attachManyToMany($schema, Relationships $relation, $pivotcols = null){
        
        //get pivot and condition
        $pivot = $relation->pivot;
        $left = Conditions::parseManyToMany('main',$relation->getCondition('plain'));
        $right = Conditions::parseManyToMany('foreign',$relation->getCondition('plain'));
        
        if(is_array($schema)){         
            
            foreach($schema as $id){
                
                //set pivot values
                $pivot->{$left['pivot']['col']} = $this->schema->{$left['main']['col']};
                $pivot->{$right['pivot']['col']} = $id;

                //set additional columns
                if(!is_null($pivotcols)){
                    
                    foreach($pivotcols as $col => $value){
                        
                        //check if column exists
                        if(property_exists($pivot, $col)){
                            $pivot->{$col} = $value;
                        }
                    }
                }            
                
                //save schema
                $this->setTable($pivot->table, $this->_handler['query']);
                $this->_saveForeignOrPivotSchema($pivot);
            }
        }
        else{
            
            //set pivot values
            $pivot->{$left['pivot']['col']} = $this->schema->{$left['main']['col']};
            $pivot->{$right['pivot']['col']} = $schema->{$right['foreign']['col']};
            
            //set additional columns
            if(!is_null($pivotcols)){
                foreach ($pivotcols as $col => $value) {
                    $pivot->{$col} = $value;
                }
            }

            //save schema
            $this->setTable($pivot->table, $this->_handler['query']);
            $this->_saveForeignOrPivotSchema($pivot);
        }
        
        return $this->hasNoErrors();
    }
    
    /**
     * Detaches schema from record based on relation
     * @param array $columnids 
     */
    public function detach(array $columnids = null){
        
        //set handler and query settings
        $this->_handler['query'] = clone $this->_query;
        
        //get active relation
        $active = array_values($this->getActiveRelations())[0];
        
        //switch between types
        switch ($active->type) {
            case "one-to-one":
                return $this->detachOneToOne($active);
                //break;
            
            case 'one-to-many':
                return $this->detachOneToMany($active, $columnids);
                //break;
            
            case 'many-to-many':
                return $this->detachManyToMany($active, $columnids);
                //break;
        }
    }
    
    /**
     * One-to-one detachment
     * @param Relationships $relation
     * @return boolean
     */
    protected function detachOneToOne(Relationships $relation){
        
        //get the condition
        $condition = Conditions::parse($relation->getCondition());
        
        //remove schema entry
        $this->schema->{$condition['main']['col']} = null;
        
        //save schema
        $this->setTable($this->schema->table, $this->_handler['query']);
        $this->_saveForeignOrPivotSchema($this->schema);
        
        return $this->hasNoErrors();        
    }
    
    /**
     * One-to-Many detachment
     * @param Relationships $relation
     * @param type $columnids
     * @return boolean
     */
    protected function detachOneToMany(Relationships $relation, $columnids) {
        
        if(is_null($columnids)){
            App::warning('Please send column ids to detach');
        }
        
        //get the condition
        $condition = Conditions::parse($relation->getCondition());
        
        //set foreign table
        $this->setTable($relation->foreign->table, $this->_handler['query']);
        
        //set where
        $fk = $this->getPrimaryKey($relation->foreign->table);        
        foreach ($columnids as $id){
            $this->_handler['query']->where($fk,'=',$id, 'OR');
        }
        
        //parse query and execute
        $this->_parseQuery(FALSE, $this->_handler['query']);        
        $results = $this->_dbhandle->fetchAll();
        
        //map resultsto foreign
        if(count($results) > 0){
            
            $schemas = $this->mapResultsToSchema($results, $relation->foreign);
            foreach($schemas as $schema){

                //nullify the foreign column
                $schema->{$condition['foreign']['col']} = NULL;
                
                //save new schema
                $this->_saveForeignOrPivotSchema($schema);
            }
            
            return TRUE;
        }

        return NULL;
    }
    
    /**
     * Many to many relationships
     * @param Relationships $relation
     * @param type $columnids
     */
    protected function detachManyToMany(Relationships $relation, $columnids) {
        
        if(is_null($columnids)){
            App::warning('Please send column ids to detach');
        }
        
        //get the condition
        $main = Conditions::parseManyToMany('main',$relation->getCondition('plain'));
        $foreign = Conditions::parseManyToMany('foreign',$relation->getCondition('plain'));
        
        //set pivot table
        $this->setTable($relation->pivot->table, $this->_handler['query']);
        
        //loop througn column ids
        foreach ($columnids as $id){
            
            $this->_handler['query']->where($main['pivot']['col'],'=', $this->schema->{$main['main']['col']}, 'AND');
            $this->_handler['query']->where($foreign['pivot']['col'],'=',$id, 'AND');
            
            //parse and delete the record
            $this->_handler['query']->delete();
            
            $this->_parseQuery(FALSE, $this->_handler['query']);        
            $this->_dbhandle->execute();
        }
        
        return $this->hasNoErrors();
    }
    
    /**
     * Maps the results to the sent schema
     * @param type $results
     * @param type $schema
     */
    protected function mapResultsToSchema($results, $schema) {
        
        $output = [];
        foreach($results as $result){
            $schema = clone $schema; 

            foreach($result as $column => $value){                           
                $schema->{$column} = $value;
            }

            //set created at
            $schema->setCreationTime();
            $created_at = $schema->getCreationTime();
            $schema->data = [ $created_at => $result ];
            
            $output[] = $schema;
        }
        
        return $output;
    }
    
    /**
     * Saves schema in one-to-one relationship
     * @param type $relation
     * @param SchemaInterface $schema
     * @return boolean
     */
    protected function saveOneToOne($relation, SchemaInterface $schema) {
        
        $condition = $relation->getCondition();
        $localcol = explode('.',$condition[0])[1];
        
        //save foreign schema
        $action = $this->_saveForeignOrPivotSchema($schema); 
        
        if($this->hasNoErrors()){
            
            if($action == 'insert'){                
                $insertid = $this->getPdo()->lastInsertId();
                $this->schema->{$localcol} = $insertid;
                
                //update the local schema
                if($relation->is_child === FALSE){
                    
                    $this->setTable($this->schema->table, $this->_handler['query']);
                    $this->_saveForeignOrPivotSchema($this->schema);
                }
                
                return $this->hasNoErrors();
            }
            elseif($action == 'update'){
                return TRUE;
            }
        }
    }
    
    /**
     * Saves schema in one to many relationship
     * @param type $relation
     * @param SchemaInterface $schema
     */
    protected function saveOneToMany(Relationships $relation, $schema) {
            
        //set the foreign key and local columns
        $condition = $relation->getCondition();
        $localcol = explode('.',$condition[0]);
        $fkcol = explode('.',$condition[2]);

        //set the fk value
        $schema->{$fkcol[1]} = $this->schema->{$localcol[1]};
        
        //save foreign or pivot schema
        $action = $this->_saveForeignOrPivotSchema($schema); 
        
        if($this->hasNoErrors()){

            if($action == 'insert'){                
                return $this->getPdo()->lastInsertId();
            }
            elseif($action == 'update'){
                return TRUE;
            }
        }
        else{ 
            return $this->getErrors(); 
        }
    }
    
    /**
     * Save schema in many-to-many relationship
     * @param type $relation
     * @param type $schema
     */
    protected function saveToManyToMany(Relationships $relation, SchemaInterface $schema, $pivotcols){
        
        $foreignid = NULL;
        $class = get_class($schema);
        
        //determine if foreign or pivot
        $dtmn = $this->_isSchemaForeignOrPivot($class, $relation, $schema); 
        
        //save foreign or pivot schema
        if($dtmn == 'foreign'){
            
            //get the condition
            $condition = $relation->getCondition('plain');
            $leftcol = end($condition)['left'];
            $fkcol = explode('.',$leftcol)[1];
            
            if(!is_null($schema->{$fkcol})){
                
                $action = 'insert';
                $foreignid = $schema->{$fkcol};
            }
            else{
                $action = $this->_saveForeignOrPivotSchema($schema, 'insert');
            }
        }
        else{
            $action = $this->_saveForeignOrPivotSchema($schema);  
        }
        
        //determine if foreign or pivot
        if($dtmn == 'foreign' && $this->hasNoErrors()){
            
            if($action == 'insert'){
                
                //create pivot schema
                if(is_null($foreignid)){
                    $foreignid = $this->getPdo()->lastInsertId();
                }
                
                $pivot = $this->_createNewPivot($relation, $schema, $foreignid, $pivotcols);
                
                //set tavle and save
                $this->setTable($pivot->table, $this->_handler['query']);
                $this->_saveForeignOrPivotSchema($pivot);
                
                if($this->hasNoErrors()){
                    return $foreignid;
                }
                else{
                    dump($this->_dbhandle->errorlog);
                }
            }
            elseif($action == 'update'){
                return TRUE;
            }
        }
        elseif($dtmn == 'pivot' && $this->hasNoErrors()){
            
            if($action == 'update'){
                return TRUE;
            }
        }
        else{ 
            return $this->getErrors(); 
        }
    }
    
    /**
     * Creates new pivot for the new entry
     * @param type $relation
     * @param type $schema
     * @param type $foreignid
     * @param type $pivotcols
     */
    private function _createNewPivot($relation, $schema, $foreignid, $pivotcols){
        
        $pivot = $relation->pivot;
        
        //parse condition
        $condition = $relation->getCondition('plain');
        
        //main columns
        $main = $condition[$pivot->table];
        $pivotcol = explode('.',$main['right'])[1];
        $localcol = explode('.',$main['left'])[1];
        
        //foreign columns
        $foreign = $condition[$schema->table];
        $fpivotcol = explode('.', $foreign['right'])[1];
        
        //set the pivot values
        $pivot->{$pivotcol} = $this->schema->{$localcol};
        $pivot->{$fpivotcol} = $foreignid;
        
        //add the additional pivot columns
        if(!is_null($pivotcols)){
            
            foreach($pivotcols as $column => $val){
                if(property_exists($pivot, $column)){
                    $pivot->{$column} = $val;
                }
                else{
                    App::warning('Warning: '.$column.' has not been found in the '. get_class($pivot) .' schema');
                }
            }
        }
        
        return $pivot;
    }
    
    /**
     * Save foreign schema
     * @param type $schema
     */
    private function _saveForeignOrPivotSchema($schema, $action = NULL){
        
        $status = $this->_isSchemaNewOrExisting($schema);  
        $schmdata = $schema->close();
        
        //recheck action
        if(is_null($action)){
            $action = ($status == 'existing' ? 'update' : 'insert');
        }
        
        //get and clear pivot, table and rows
        if(array_key_exists('pivot', $schmdata)){ unset($schmdata['pivot']); }
        if(array_key_exists('table', $schmdata)){ unset($schmdata['table']); }
        if(array_key_exists('rows', $schmdata)){ unset($schmdata['rows']); }
        
        //get diff and set creation and update times
        if($action == 'update'){
            
            $diff = $this->getUpdateDiff($schmdata, $schema);
            list($original, $created_at, $updated_at, $updates) = $diff;
        
            //set data timestamps
            $schmdata[$created_at] = $original;
            $schmdata[$updated_at] = $updates;
            
            //if no change return
            //if(count($updates) == 0) return $action;
        }
        elseif($action == 'insert'){
            
            $inserts = $this->getInsertData($schmdata, $schema);
            list($schmdata, $updated_at) = $inserts;            
        }
        
        //if status is new or existing
        if($status == 'existing'){
            
            $action = 'update';
            $this->_handler['query']->update($schmdata, $schema->getCreationTime(), $schema->getUpdateTime());
        }
        elseif($status == 'new'){
            
            $action = 'insert';
            $this->_handler['query']->insert($schmdata, $schema->getUpdateTime());
        }
        
        //process query
        $this->_parseQuery(FALSE, $this->_handler['query']);        
        $this->_dbhandle->execute();
        
        if($this->hasNoErrors()){
            return $action;
        }
        else{
            return $this->getErrors();
        }
    }
    
    /**
     * @param SchemaInterface $schema
     */
    private function _isSchemaNewOrExisting(SchemaInterface $schema){
        
        $creation = $schema->getCreationTime();
        $schm = $schema->close();
        
        if(array_key_exists($creation, $schm['data'])){
            return 'existing';
        }
        
        return 'new';
    }
    
    /**
     * @param type $class
     * @param type $relation
     * @param type $schema
     * @return string
     */
    private function _isSchemaForeignOrPivot($class, $relation){
        
        //check pivot
        if(is_array($relation->pivot)){
            $rows = $this->_getRowObjects($relation->rows);
            $pivot = $rows[0];
        }
        elseif(is_object($relation->pivot)){
            $pivot = $relation->pivot;
        }
        
        //switch class name
        switch ($class) {
            case get_class($relation->foreign):
                return 'foreign';
            
            case get_class($pivot):
                return 'pivot';
        }
    }
    
    /**
     * Get the schema row objects
     * @param type $rows
     */
    private function _getRowObjects($rows) {
        
        $objects = [];
        foreach ($rows as $schemas) {
            foreach($schemas as $schema){
                $objects[] = $schema;
            }
        }
        
        return $objects;
    }
}
