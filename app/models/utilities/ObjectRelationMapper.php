<?php
namespace Jenga\App\Models\Utilities;

use Jenga\App\Core\App;
use Jenga\App\Html\Form;
use Jenga\App\Models\Traits;
use Jenga\App\Models\Utilities\DB;
use Jenga\App\Models\Utilities\Collector;
use Jenga\App\Models\Relations\Conditions;
use Jenga\App\Models\Relations\Relationships;
use Jenga\App\Models\Interfaces\SchemaInterface;
use Jenga\App\Models\Interfaces\BuilderInterface;
use Jenga\App\Models\Interfaces\ActiveRecordInterface;
use Jenga\App\Models\Interfaces\ObjectRelationMapperInterface;

use Jenga\App\Models\Relations\Types\OneToOne;
use Jenga\App\Models\Relations\Types\OneToMany;
use Jenga\App\Models\Relations\Types\ManyToMany;

use Jenga\App\Database\Abstraction\ConnectionsMap;
use Jenga\App\Models\Utilities\Paginator;
use Jenga\App\Project\Core\Project;

/**
 * This class maps the ActiveRecord, Schema and Builder and statically binds to the element's Model
 *
 * @author stanley
 */
abstract class ObjectRelationMapper extends ConnectionsMap implements ObjectRelationMapperInterface {
    
    use Traits\Relations;
    use Traits\ActiveRecordTrait;
    
    /**
     * The PDO connection in use
     * @var type 
     */
    public $activeconnection;
    
    /**
     * This is the connection name to be used within the model
     * @var string
     */
    public $connection = 'default';
    
    /**
     * The ActiveRecord instance
     * @var Jenga\App\Models\Interfaces\ActiveRecordInterface
     */
    public $record;
    
    /**
     * The primary table schema
     * @var type 
     */
    public $schema;
    
    /**
     * the schema builder
     * @var Jenga\App\Models\Interfaces\BuilderInterface
     */
    public $builder;
    
    /**
     * New or existing flag
     */
    public $isNew = TRUE;
    
    /**
     * Holds the page info for the model
     * @var type 
     */
    public $page;
    
    /**
     * The paginator class
     */
    protected $paginator;
    
    /**
     * Model instance for static usage
     * @var type 
     */
    public static $instance;
    
    /**
     * Flag for whether the main results should be loaded into mapped relation results
     * @var type 
     */
    private $_eager_loading = false;
    
    /**
     * Hold the relationship names that have been eagerly loaded
     * @var type 
     */
    private $_eager_results_index = [];
    
    /**
     * @var boolean
     */
    private $_act_on_relation = false;
    
    /**
     * Return collection
     * @var type 
     */
    private $_return_collection = false;
    
    /**
     * Is the model single or multiple/collected
     * @var type 
     */
    private $_result_type = 'multiple';
    
    /**
     * Sets the output format
     * @var type 
     */
    private $_output_format = null;
    
    /**
     * The columns to be applied for the context
     * @var array
     */
    protected $contextColumns = null;
    
    /**
     * Attaches the schema and the active record
     * @param SchemaInterface $schema
     * @param ActiveRecordInterface $record
     */
    public function boot(SchemaInterface $schema, ActiveRecordInterface $record){
        
        //set the ActiveRecord
        $this->record = $record;
        $this->record->setSchema($schema);
        
        //set the Schema
        $this->schema = $this->record->schema;
        
        //check for eagerly queued relationss
        if($this->hasQueuedRelations()){
            $relations = $this->getAllRelations();
            
            //lopp through the relations
            foreach($relations as $name => $relation){
                
                //set to ActiveRecord queue
                $this->record->queueRelations([$name => $relation]);
            }
        }
    }
    
    /**
     * Loads external element model
     * @param $model_class
     * @return ObjectRelationMapperInterface $model
     */
    public static function load($model_class){
        
        $model = App::make($model_class);
        $dbal = Project::getDatabaseConnector();
        
        return call_user_func_array([$model, '__map'], [App::get($dbal)]);
    }
    
    /**
     * Get active connection
     * @return type
     */
    public function getActiveConnaction(){
        
        $dbal = App::get(Project::getDatabaseConnector());
        return (object)$dbal::connect()->getActiveConnection();
    }
    
    /**
     * Attaches a new schema to the existing model and clears all existing relationships
     * return new static
     */
    public static function attachSchema($schema){
        
        $newschema = App::get($schema);
        $model = static::load(static::class);
        
        //clear queue
        $model->clearQueue();
        
        //replace schema
        $model->schema = $newschema;
        $model->record->schema = $newschema;
        $model->record->setTable($newschema->table);
        
        return $model;
    }
    
    /**
     * Checks the ActiveRecord methods then the Schema methods
     * @param type $name
     * @param type $arguments
     */
    public function __call($name, $arguments) {
        
        //check if name is a relation
        if($this->hasQueuedRelations(strtolower($name))){
            
            //get relation from model
            $name = strtolower($name);
            $relation = $this->getSpecificRelation($name);
            
            //set to ActiveRecord
            if($this->record->isRelationInQueue(strtolower($name)) == FALSE){
                $this->record->queueRelations([$name => $relation]);
            }
            
            //set the active relation name
            $this->_act_on_relation = TRUE;
            $this->record->setActiveRelations([$name => $relation], TRUE);
            
            //activate eager loading
            $this->isEager();
            
            return $this;
        }
        elseif($this->record->isRelationInRows($name) || $this->record->isRelationInQueue($name)){
            
            //set save to relation flag
            $this->_act_on_relation = TRUE;
            
            //get and set the active relation
            if($this->record->isRelationInRows($name)){
                $relation = $this->record->related_rows[$name];
            }
            elseif($this->record->isRelationInQueue($name)){
                $relation = $this->record->getQueue($name);
            }
            
            $this->record->activateRelation($name, $relation);
            
            return $this;
        }
        
        //check the record methods first
        if(method_exists($this->record, $name)){
            return call_user_func_array([$this->record, $name], $arguments);
        }
        //else check the schema methods
        elseif(method_exists($this->schema, $name)){
            return call_user_func_array([$this->schema, $name], $arguments);
        }
        
        App::warning('Class Method '.$name.'() not found in '. get_class($this));
    }
    
    /**
     * Create new copies of the Schema and ActiveRecord instances
     */
    public function __clone() {     
        
        $this->schema = clone $this->schema;
        $this->record = clone $this->record;
        $this->record->schema = clone $this->record->schema;
        $this->builder = clone $this->builder;
    }
    
    /**
     * Returns the set properties of the schema
     * @param type $name
     * @return type
     */
    public function __get($name) {
        
        $creationtime = null;
        if($this->schema->getCreationTime() !== 'created_at_'){
            $creationtime = $this->schema->getCreationTime();
        }
        
        //check relation name
        if($this->record->isQueued($name)){ 
            return $this->fetch($name);
        }
        
        //check the schema
        if(property_exists($this->schema, $name)){
            return $this->schema->{$name};
        }
        //check joined data
        elseif($this->schema->is_joined){
            
            //check if name is in raw data
            if(array_key_exists($creationtime, $this->schema->data)){
                if(!is_null($this->schema->data[$creationtime]) && array_key_exists($name, $this->schema->data[$creationtime])){
                    return $this->schema->data[$this->schema->getCreationTime()][$name];
                }
            }
            
            //get the join data
            if($this->schema->isVarInAuxData($name)){
                return $this->schema->getAuxData($name);
            }
            
            $joindata = $this->schema->getJoinData();
            
            //loop through the joined tables
            foreach ($joindata as $cols) {
                
                if(array_key_exists($name, $cols)){
                    return $cols[$name];
                }
            }
        }
        elseif(!is_null($creationtime) 
                && array_key_exists($creationtime, $this->schema->data)
                && $this->schema->isVarInAuxData($name) === FALSE){
            
            //check schema raw data results
            if(array_key_exists($name, $this->schema->data[$creationtime])){
                return $this->schema->data[$this->schema->getCreationTime()][$name];
            }
        }
        elseif(property_exists($this->record, $name)){
            
            //check active record
            return $this->record->{$name};
        }
        elseif($this->schema->isVarInAuxData($name)){
            
            //finally check auxillary data
            return $this->schema->getAuxData($name);
        }
        
        return NULL;
    }
    
    /**
     * Sets the schema values
     * @param type $name
     * @param type $value
     */
    public function __set($name, $value) {
        
        //set into primary schema
        if(property_exists($this->schema, $name)){
            $this->schema->{$name} = $value;
        }
        //check for direct joins
        elseif($this->schema->is_joined){
            
            //get all tables
            $tables = $this->schema->getJoinTables();            
            if($this->schema->getJoinType() == 'direct'){
                
                foreach ($tables as $table) {

                    //set table
                    $this->builder->table($table);        

                    //check column
                    if($this->builder->hasColumn($name)){
                        $this->schema->setJoinData($table, $name, $value);
                    }
                    elseif(strpos($name, $table) === (int)0){

                        //add the duplicated columns from the join
                        $fil = array_filter(explode('_',$name));
                        $col = end($fil);
                        $this->schema->setJoinData($table, $col, $value);
                    }
                }
            }
            //if not just add to schema
            else{
                $this->schema->saveToAuxData($name, $value);
            }
        }
        //if not just add to schema
        else{
            $this->schema->saveToAuxData($name, $value);
        }
    }
    
    /**
     * Selects the first row of the queried results
     * @param type $column
     * @return $this
     */
    public function first($column = '*') {
        
        //get the full result
        $result = $this->get(1, $column);
        return $result[0];
    }
    
    /**
     * Synonym for get() function
     * @param type $numRows
     * @param type $column
     * @return type
     */
    public function show($numRows = null, $column = '*'){
        return $this->get($numRows, $column);
    }
    
    /**
     * Returns all the rows within  given table
     */
    public function all(){
        return $this->get();
    }
    
    /**
     * Returns specified row pointer by count 
     * @param type $pointers
     */
    public function pluck($pointers) {
        $this->_return_collection = TRUE;
        return $this->collect()->pluck($pointers);
    }
    
    /**
     * Set the results collection to be returned
     * @return Collector
     */
    public function collect($_defer_mapping = true, $numRows = null, $column = '*'){
        
        $this->_return_collection = TRUE;
        return $this->get($numRows, $column, $_defer_mapping);
    }
    
    /**
     * Allows the joined tables to be accessed separately
     * @param type $table
     */
    public function on($table = null){
        $prefix = $this->record->getPrefix();
        $joins =  $this->schema->getJoinData($prefix.$table);
        
        if(!is_null($joins)){
            return (object) $joins;
        }
        
        return NULL;
    }
    
    /**
      * This function directly loads a specific row based on the id of the primary or search column
     * 
     * @param mixed $id if array the array key should be the search column to be used and its value eg ['name'=> $name],
     *                  if string, the value will be compared against the table primary key
     * @param type $select_column to be returned in result
     * 
     * @return new static
     */
    public function find($id, $select_column='*'){
        
        $this->select($select_column);
        
        //check if $id is array
        if(!is_array($id)){      
            $this->record->where($this->record->getPrimaryKey(), $id);
        }
        else{
            
            //lood through the sent conditions
            foreach($id as $column => $value){
                $this->record->where($column, $value);
            }
        }
        
        //get the full result
        $rows = $this->get();
        
        //assign result to object
        return $rows[0];
    }
    
    /**
     * Sets output format
     * 
     * @param type $type
     * @return $this
     */
    protected function format($type) {
        $this->_output_format = $type;
        return $this;
    }
    
    /**
     * This is the function to just retrieve the rows linked to the set table
     * @param type $numRows
     * @param type $column
     * @param type $_defer_mapping
     * @return ActiveRecordInsterface
     */
    public function get($numRows = null, $column = '*', $_defer_mapping = false){
        
        //check context
        if($this->hasContext && $this->ignoreContext === false){
            $this->_addContextConditions();
        }
        
        //close the relations queue
        if($this->isQueueClosed() === FALSE){
            $this->closeQueue();
        }
        
        //embed all relations
        if($this->record->hasActiveRelation()){
            
            //check for direct fetch
            $name = array_keys($this->record->getActiveRelations())[0];
            return $this->fetch($name);
        }      
        
        //get the full result
        $results = $this->record->get($numRows, $column);
        
        //check for errors
        if($this->hasNoErrors() && $results !== FALSE && count($results) > 0){
            
            //set multiple record flag
            if($this->record->count() === 1){            
                $this->_result_type = 'single';   
            }
            else {
                $this->_result_type = 'multiple';
            }

            //set the isNew flag
            $this->isNew = FALSE;
            
            //return raw results if array is set
            if(!is_null($this->_output_format) && $this->_output_format == 'array'){
                return $results;
            }
            
            //collect the rows
            $collector = new Collector($this, $results);
            
            //set defer mapping
            if($_defer_mapping){
                $collector->setDeferMapping($_defer_mapping);
            }
            else{
                $collection = $collector->assignOutputToModel();
            }
            
            //to return collection or not
            if($this->_return_collection){
                
                //reset defer mapping
                $this->_return_collection = false;
                return $collector;
            }
            else{
                $rows = $collection->getRows();
                return $rows;
            }
        }
        elseif($this->hasNoErrors() && !is_null($results) && $_defer_mapping == true){
            
            //collect the rows
            if(count($results) == 0 ){
                $collector = new Collector($this, $results);
                return $collector;
            }
        }
        elseif($this->hasNoErrors() === FALSE){
            dump($this->getLastError());
        }
        
        return NULL;
    }
    
    /**
     * Disable eager loading
     */
    public function isLazy(){
        $this->_eager_loading = false;
    }
    
    /**
     * Enable eager loading
     */
    public function isEager(){
        $this->_eager_loading = true;
    }
    
    /**
     * Add the context where conditions
     */
    private function _addContextConditions(){
        
        if(!is_null($this->contextColumns)){
            foreach($this->contextColumns as $column => $value){

                //check if table has been attached
                if(strpos($column, '.') === FALSE){
                    
                    //check if column exists
                    if(property_exists($this->schema, $column)){

                        //add the context column condition
                        $this->where($column, $value);
                    }
                }
                else{
                    $col = explode('.', $column);
                    
                    //check if column exists
                    if(property_exists($this->schema, $col[1])){

                        //add the context column condition
                        $this->where($column, $value);
                    }
                }
            }
        }
    }
    
    /**
     * Adds the schema column value
     */
    private function _addContextSchemaValue(){
        
        if(!is_null($this->contextColumns)){
            foreach($this->contextColumns as $column => $value){

                //check if column exists
                if(property_exists($this->schema, $column)){

                    //add the context column condition
                    $this->schema->{$column} = $value;
                }
            }
        }
    }
    
    /**
     * Returns query results as an array
     */
    public function toArray(){
        return $this->format('array')->get();
    }
    
    /**
     * Calls any element within the project
     * @param type $element
     */
    public static function call($element) {        
        return Project::call($element);
    }
    
    /**
     * Alias for getCount()
     * @return type
     */
    public function count(){
        return $this->getCount();
    }
    
    /**
     * Returns the row count of the created query
     * @return type
     */
    public function getCount(){
        
        //get the record query
        $cwhere = $wheres = [];
        $query = $this->record->getQuery();
        $stored = $query->returnStoredQuery();
        
        if(array_key_exists('where', $query->stmt)){
            foreach($query->stmt['where'] as $currentwhere){
                $cwhere[] = $currentwhere;
            }
        }
        
        $clone = clone $query;
        
        //add select
        $clone->select('count(*)');
        
        //add previous wheres
        if(!is_null($stored['stmt']['where'])){
            $wheres = $stored['stmt']['where'];
        }
        
        //combine every where
        if(count($cwhere) > 0){
            $wheres = array_merge($wheres, $cwhere);
        }
        
        if(!is_null($wheres) && count($wheres) > 0){
            foreach($wheres as $where){
                $clone->where($where[0], $where[1], array_key_exists(2, $where) ? $where[2] : null, $where[3]);
            }
        }
        
        $sql = $clone->runProcessor(false);
        $args = $this->getCloneHandle()->rawQuery($sql['stmt'], is_null($sql['args']) ? [] : $sql['args']);
        
        if(array_key_exists(0, $args)){
            $qresults = $args[0];
            return $qresults['count(*)'];
        }
        else{
            return 0;
        }
    }
    
    /**
     * Paginate the records results
     * @param type $rows_per_page
     * @param type $maxpages
     * @param type $url_pattern A URL for each page, with (:num) as a placeholder for the page number. Ex. '/foo/page/(:num)'
     * @return type
     */
    public function paginate($rows_per_page, $maxpages = 10, $url_pattern = null){
        
        $count = $this->getCount();
        $this->paginator = new Paginator($count, $rows_per_page, $url_pattern);
        
        //set the max pages
        $this->paginator->setMaxPagesToShow($maxpages);
        
        //generate the indexed pages
        return $this->paginator->generate($this);
    }
    
    /**
     * Returns the paginator
     * @return Paginator
     */
    public function pages(){
        return $this->paginator;
    }
    
    /**
     * Return the raw results
     */
    public function toRaw(){
        return $this->collect()->getRaw();
    }
    
    /**
     * Extracts any expressions data in the data section
     * @param type $exp
     */
    public function getExp($exp){
        
        if(array_key_exists($exp, $this->schema->data[$this->schema->getCreationTime()]))
            return $this->schema->data[$this->schema->getCreationTime()][$exp];
        
        return NULL;
    }
    
    /**
     * Sets dumpQuery flag
     * @param type $flag
     */
    public function dumpQuery() {
        $this->record->dumpQuery();
        return $this;
    }
    
    /**
     * Generates data from schema and resolves the data back to the Active Record
     */
    public function execute() {
        
        //extract data from schema
        $exec = $this->schema->close();
        
        //remove table
        unset($exec['table']);
        
        //insert or update
        if(count($exec['data']) > 0){
            $flag = $this->_setUpdateData($exec, $this->schema);
        }
        else{
            $flag = $this->_setInsertData($exec, $this->schema);
        }
        
        return $flag;
    }
    
    /**
     * Sets the ActiveRecord insert data
     * @param type $exec
     */
    private function _setInsertData($exec, SchemaInterface $schema) {
        
        $inserts = $this->getInsertData($exec, $schema);
        list($data, $updated_at) = $inserts;
        
        $this->record->setData($data);
        $this->record->updated_at = $updated_at;
        
        return TRUE;
    }
    
    /**
     * Sets the ActiveRecord update data
     * @param type $exec
     */
    private function _setUpdateData($exec, SchemaInterface $schema){
        
        $diff = $this->getUpdateDiff($exec, $schema);
        list($original, $created_at, $updated_at, $updates) = $diff;
        
        //reassign ActiveRecord
        if(count($updates) > 0){
            
            $data[$created_at] = $original;
            $data[$updated_at] = $updates;

            $this->record->setData($data);
            $this->record->created_at = $created_at;
            $this->record->updated_at = $updated_at;

            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * Checks if model has embedded relations
     * @return boolean
     */
    public function hasQueuedRelations($name = null){
        
        if($this->record->isRelationInQueue($name) === FALSE){
            return $this->hasQueuedRelationsInModel($name);
        }
        
        return TRUE;
    }
    
    /**
     * Add the relations
     * @param type $relations
     */
    public function embedRelations($relations, $is_active = false){
        
        if($is_active === FALSE) $this->record->setToQueue($relations);
        else $this->record->saveActiveRelations($relations);
        
        return $this;
    }
    
    /**
     * Add the relations to rows
     * @param type $id the record primary key value
     * @param type $relations
     */
    public function saveRelationsToRows($id,$relations) {        
        $this->record->saveActiveRelationsToRows($id,$relations);        
        return $this;
    }
    
    /**
     * Allows for selecting the queued relation to be loaded
     */
    public function fetch($name = null){
        
        if(!$this->record->isQueued($name)){
            App::critical_error('The named relation "'.$name.'" has not been declared in the Model: '. get_class($this));
        }
        
        //set relation as active touchpoint
        $this->_act_on_relation = TRUE;
        
        //get results
        $activejoin = $fetched = null;
        $results = $this->getResultsFromRelations($name, $activejoin);
        
        if(!is_null($results) && count($results) > 0 && $this->hasNoErrors()){
            
            //check eager loading
            if($this->_eager_loading){
                
                $collector = new Collector($this, $results);
                $collection = $collector->assignOutputToModel()->getRows();
                
                //garbage collection
                $this->_garbageCollectOnRelations();
                
                //start mapping
                foreach($collection as &$model){
                    $this->_mapResultsToActiveRecord($model, $results, $name, $activejoin, TRUE);
                }
                
                $fetched = $collection;
            }
            else{
                $fetched = $this->_mapResultsToActiveRecord($this, $results,$name, $activejoin);
            }
        }
        elseif($this->hasNoErrors() === FALSE){
            dump($this->getLastError());
        }
        
        //disable eager loading
        $this->_eager_loading = FALSE;
        $this->record->demoteActiveRelation($name);
        
        if(array_key_exists($name, $this->record->related_rows) 
                && $this->record->related_rows[$name]->type == 'one-to-one'){
            
            if($fetched[0] instanceof SchemaInterface){
                return $fetched[0];
            }
            elseif(!is_null($fetched)){
                
                //fish for the retrieved row inside the model
                $rows = $fetched[0]->record->related_rows[$name]->rows;
                foreach($rows as $row){
                    return $row[0];
                }
            }
        }
        else{
            return $fetched;
        }
        
        return NULL;
    }
    
    /**
     * Clear out any pending active relations in main model
     */
    private function _garbageCollectOnRelations(){
        
        if($this->record->hasActiveRelation()){
            $this->record->unsetActiveRelation();
        }
    }
    
    /**
     * Maps to ActiveRecord individually
     * @param type $model
     * @param type $results
     * @param type $name
     * @param type $activejoin
     * @param type $return Whether to return the full model or the related rows
     * @return type
     */
    private function _mapResultsToActiveRecord($model, $results, $name, $activejoin, $return = false) {
        
        //map results to model
        $output = null;
        
        $this->_mapResultsToModel($model, $activejoin->type, $results);
        $rows = $model->record->related_rows;
        
        //set eager index
        $model->_eager_results_index[] = $name;
        
        //return model if return is TRUE
        if($return === TRUE){
            return $model;
        }
        
        //return related rows only
        if(array_key_exists($name, $rows)){

            if($activejoin->type != 'many-to-many'){
                $output = $rows[$name]->rows[$this->schema->{$activejoin->localcol}];
            }
            else{

                //get pivots from the related rows
                $pivots = $rows[$name]->rows[$this->schema->{$activejoin->localcol}];

                //add pivots rows to list
                $list = [];
                foreach($pivots as $pivot){

                    //add the actual foreign schema
                    $schema = end($pivot->rows);

                    //unset and set the pivot schema
                    $schema->pivot = $pivot;

                    $list[] = $schema;
                }

                $output = $list;
            }
        }

        if($return === FALSE)
            return $output;
    }
    
    /**
     * Maps the db results to the current model
     * @param ObjectRelationMapperInterface $model
     * @param type $type
     * @param type $results
     */
    private function _mapResultsToModel(ObjectRelationMapperInterface $model, $type, $results){
        
        //start mapping
        switch ($type) {
            
            case 'one-to-one':
                App::make(OneToOne::class, ['model' => $model, 'dbresults' => $results])->map(); 
                break;
            
            case 'one-to-many':
                App::make(OneToMany::class, ['model' => $model, 'dbresults' => $results])->map(); 
                break;
            
            case 'many-to-many':
                App::make(ManyToMany::class, ['model' => $model, 'dbresults' => $results])->map(); 
                break;
        }
    }
    
    /**
     * Extracts the db results from the sent relation
     * @param type $name
     * @return type
     */
    protected function getResultsFromRelations($name, &$activejoin) {
        
        //set up the query
        $record = $this->record;
        $schema = $record->schema;
        
        //set the mapping
        $schema->setJoinType('mapped');
        
        //set the relation into the active record
        if($record->hasActiveRelation() === FALSE){
            
            $queue = $record->getQueue();
            $activejoin = $queue[$name]; 
            
            $record->setActiveRelations([$name => $queue[$name]], TRUE);
        }
        else{
            $activejoin = $record->getActiveRelations($name);
        }
        
        //process condition
        if($activejoin->type == 'one-to-one'){
            
           //get the primary key and condition
           $key = $this->record->getPrimaryKey();
           $condition = Conditions::parse($activejoin->getCondition());
           
           //make sure only one record is retrieved
           $count = 1;
           
           //get the primary key and add the where condition
           if($this->_eager_loading == FALSE){
               
               //check null values
               if(is_null($schema->{$key}) || is_null($schema->{$condition['main']['col']})){
                   return NULL;
               }
               
               $record->where($condition['main']['table'].'.'.$key, $schema->{$key});
               $record->where($condition['main']['table'].'.'.$condition['main']['col'], $schema->{$condition['main']['col']});
           }
        }
        elseif($activejoin->type == 'one-to-many'){
            
            //get the primary key and condition
           $key = $this->record->getPrimaryKey();
           $condition = Conditions::parse($activejoin->getCondition());
           $count = null;
           
           //get the primary key and add the where condition
           if($this->_eager_loading == FALSE){
               
               //check null values
               if(is_null($schema->{$key})){
                   return NULL;
               }
               
               $record->where($condition['main']['table'].'.'.$key, $schema->{$key});
           }
        }
        elseif($activejoin->type == 'many-to-many'){
            
            //get the condition
            $condition = Conditions::parseManyToMany('main', $activejoin->getCondition('plain'));             
            $count = null;
            
            //setup the where condition
            if($this->_eager_loading == FALSE){
               
               //check null values
               if(is_null($schema->{$condition['main']['col']})){
                   return NULL;
               }
               
                $record->where($condition['main']['table'].'.'.$condition['main']['col'], $schema->{$condition['main']['col']});
            }
        }
        
        //get the results
        if($this->_eager_loading == FALSE){
            return $record->bypass()->get();
        }
        else{
            $results =  $record->get();
            return $results;
        }
    }
    
    /**
     * Saves the current record
     * @param SchemaInterface $schema Foreign schema if a relation is attached
     * @param array $pivotcols Any column values to be inserted into the pivot table
     * @return type
     */
    public function save($schema = null, array $pivotcols = null){      
        
        //check context and insert context column value
        if($this->hasContext){
            $this->_addContextSchemaValue();
        }
        
        if(!is_null($schema)){
            
            //check if relation is active
            if($this->_act_on_relation === FALSE){
                App::critical_error('No relationship has been set for the Save action');
            }
            
            //save schema in ActiveRecord
            return $this->record->saveToRelatedSchema($schema, $pivotcols);
        }
        else{
            
            $proceed = $this->execute();
            
            //check if where keyword is included in query
            $where_present = $this->record->getQuery()->isKeywordInQuery('where');

            //if so run an update
            if($where_present){
                return $this->record->update();
            }
            
            //if true, proceed
            if($proceed){
                return $this->record->save();
            }
        }
    }
    
    /**
     * Save the record mapping the sent map array to the schema's columns
     * @param type $array
     * @param type $id
     * @param type $where
     * @return $this
     */
    public function saveUsingArray($array = [], $id = null, $where = null, $ignorecontent = false){
        
        //assign model
        if(!empty($id)){
            $model = $this->find($id);
        }
        else{
            $model = $this;
        }
        
        //ignore context flag
        if($ignorecontent){
            $model->ignoreContext();
        }
        
        //get model columns
        $columns = $model->getFillableColumns();
        
        //loop through map
        foreach($array as $column => $value){
            
            //check if column exists and map
            if(array_key_exists($column, $columns)){
                $model->{$column} = $value;
            }
        }
        
        //check where condition
        if(!is_null($where)){
            
            //if where is ['alias',$alias]
            if(count($where) == 2){
                $model->where($where[0], $where[1]);
            }
            //if where is ['alias', '>', $alias]
            elseif(count($where) == 3){
                $model->where($where[0], $where[1], $where[2]);
            }
        }
        
        //perform the save
        $model->save();      
        return $model;
    }
    
    /**
     * Updates records specified in the where condition
     */
    public function update() {
        $proceed = $this->execute();
        
        if($proceed)
            return $this->record->update();
    }
    
    /**
     * Runs the query directly and returns the query results within the Active Record object
     * 
     * @param type $stmt
     * @param type $args
     */
    public function query($stmt, $args = null) {
        
        $results = $this->getHandle()->rawQuery($stmt, $args);
        
        //set direct to true
        $this->_direct = TRUE;
        $this->_result_type = 'multiple';
        
        $this->_results = $results;
        $this->_parseOutput($this->_results);
        
        return $this;
    }
    
    /**
     * Assign schema builder
     * @param type $driver
     * @return Jenga\App\Models\Interfaces\BuilderInterface
     */
    public function setBuilder($driver) {
        
        $builder = $this->_resolveBuilderClass($driver);
        $this->builder = App::make($builder, ['prefix' => $this->record->getPrefix()]);
        $this->record->setBuilder($this->builder);
        
        //set the model instance
        self::$instance = $this;
    }
    
    /**
     * Sets the model using the model
     * @param type $model
     */
    public function setModel($model){
        $this->model = $model;
    }
    
    /**
     * Returns the table schema
     * @return SchemaInterface
     */
    public function getSchema() {
        return $this->schema;
    }
    
    /**
     * Returns the set Schema Builder
     * @return Jenga\App\Models\Interfaces\BuilderInterface
     */
    public static function getBuilder(){
        return self::$instance->builder;
    }
    
    /**
     * Resolve builder class
     * @param type $driver
     * @return type
     */
    private function _resolveBuilderClass($driver){
        return 'Jenga\App\Database\Systems\Pdo\Drivers\\'.ucfirst($driver).'\Schema\Builder';
    }
    
    /**
     * Resets the timestamps
     */
    public function clearTimeStamps() {
        $this->schema->resetTimeStamps();
    }
    
    /**
     * Populates the form fields and insert the schema data
     * @param Form $form
     * @param type $colattrs
     */
    public function hydrateInputs(Form &$form, $colattrs = []){
        
        $columns = $this->getFillableColumns(true);
        $attrs = '';
        
        foreach($columns as $name => $attrs){
            
            if(array_key_exists('input', $attrs)){
                
                //create form field attributes
                $args = array_values($attrs['input']);
                
                //populate field attributes
                if(strtolower($args[1]) !== 'select')
                    @list($label, $type, $default, $rules, $attrs) = $args;
                else
                    @list($label, $type, $default, $options, $rules, $attrs) = $args;
                
                //calculate the default value
                if($this->{$name} !== '' && !is_null($this->{$name})){
                    
                    $val = $this->{$name};
                    if($val !== '' && $val !== '0' && $val !== null){
                        $default = $val;
                    }
                    else{
                        $default = '';
                    }
                }
                
                //column attributes
                if(count($colattrs) > 0){
                    if(array_key_exists($name, $colattrs)){
                        $attrs = $colattrs[$name];
                    }
                }
                
                //create the form fields
                if(strtolower($type) !== 'hidden' 
                        && strtolower($type) !== 'select' 
                        && strtolower($type) !== 'country'
                        && strtolower($type) !== 'textarea'
                    ){
                    $form->{'add'.ucfirst($type)}($label, $name, $default, $attrs); 
                }
                elseif(strtolower($type) == 'hidden'){
                    $form->addHidden($name, $default);
                }
                elseif(strtolower($type) == 'select'){                
                    $form->addSelect($label, $name, $default, $options, $attrs);
                }
                elseif(strtolower($type) == 'country'){                
                    $form->addCountry($label, $name, $default, $attrs);
                }
                elseif(strtolower($type) == 'textarea'){
                    $form->addTextArea($label, $name, $default, $attrs);
                }
                
                //add validation rules
                if(!is_null($rules) && count($rules) > 0){
                    $form->validate($rules);
                }
            }            
        }
    }
}
