<?php
namespace Jenga\App\Database\Entities;

use Jenga\App\Core\App;
use Jenga\App\Database\Build\Query;
use Jenga\App\Database\Systems\Pdo\Handlers\PDOHandler;

use Jenga\App\Models\Relations\Relationships;
use Jenga\App\Models\Interfaces\SchemaInterface;
use Jenga\App\Models\Utilities\ObjectRelationMapper;
use Jenga\App\Models\Interfaces\ActiveRecordInterface;

use Carbon\Carbon;
use PDO;

/**
 * This class will build the precise query that will be executed by the PDO handler
 *
 * @author stanley
 */
class ActiveRecord implements ActiveRecordInterface {
    
    use RelationsHandlerTrait;
    use AuxillaryFunctionsTrait;
    
    /**
     * Flag to define is object is new or loaded from database
     *
     * @var boolean
     */
    public $isNew = true;
    
    /**
     * Contains the full PDO statement and arguments
     * @var type 
     */
    public $mysql;
    
    /**
     * The primary table
     * @var type 
     */
    public $table;
    
    protected $builder;
    
    /**
     * Bypass main table
     * @var type 
     */
    private $_bypass_table = FALSE;
    
    /**
     * The table schema instance
     * @var type 
     */
    public $schema;
    
    /**
     * The record data holder
     * @var type 
     */
    private $_data = [];
    
    /**
     * The database handler class
     * @var Jenga\App\Database\Handlers\PDOHandler 
     */
    private $_dbhandle;
    
    /**
     * Dumps the generated statement and args
     * @var type 
     */
    private $_dumpquery = false;
    
    /**
     * Stored the run query
     * @var type 
     */
    private $_retain_query = null;
    
    /**
     * The table prefix
     * @var type 
     */
    private $_prefix;
    
    /**
     * This holds the query object
     * @var Jenga\App\Database\Drivers\Mysql\Build\Query
     */
    private $_query;
    
    /**
     * The table primary key
     * @var type 
     */
    private $_primarykey;
    
    /**
     * Holds the join data
     * @var type 
     */
    private $_join = [];
    
    /**
     * Join Update flag
     * @var type 
     */
    private $_join_update = false;
    
    /**
     * Holds Join data
     * @var type 
     */
    private $_join_data;
    
    /**
     * Holds the related rows
     * @var type 
     */
    public $related_rows = [];
    
    /**
     * Add user context flag
     * @var type 
     */
    public $hasContext = false;
    
    /**
     * The columns to be applied for the context
     * @var array
     */
    public $contextColumns = null;
    
    public function __clone(){
        $this->_query = clone $this->_query;
    }
    
    /**
     * @param PDOHandler $pdohandler
     * @param Query $query
     */
    public function __construct(PDOHandler $pdohandler, Query $query) {
        
        //assign the handle
        $this->_dbhandle = $pdohandler;
        
        //assign the query
        $this->_query = $query;
    }
    
    /**
     * Get the table primary key
     * @return boolean
     */
    public function getPrimaryKey($table = null){
        
        if(is_null($table)){
            $table = $this->_query->table;
        }
        else{
            $table = $this->_verifyTablePrefix($table);
        }
        
        $sql = "SHOW INDEX FROM ".$table." WHERE Key_name = ?";
        
        $gp = $this->_dbhandle->rawQuery($sql, ['PRIMARY']);  
        $cgp = $this->_dbhandle->count($gp);
        
        if($cgp)            
            return $gp[0]['Column_name'];
        else
            return FALSE;
    }
    
    /**
     * Sets the table prefix
     * @param type $prefix
     */
    public function setPrefix($prefix) {
        
        $this->_prefix = $this->_query->prefix = $prefix;
        
        if(!defined('TABLE_PREFIX'))
            define('TABLE_PREFIX', $prefix);
        
        return $this;
    }
    
    /**
     * Get table prefix
     */
    public function getPrefix(){
        return $this->_prefix;
    }
    
    /**
     * Returns the actual PDO instance
     * @return PDO Pdo instance
     */
    public function getPdo(){
        return $this->_dbhandle->getPdo();
    }
    
    /**
     * Returns the query being built
     * @return type
     */
    public function getQuery(){
        return $this->_query;
    }
    
    /**
     * Sets the main active table
     * @param type $table
     */
    public function setTable($table, $query = null) {
        
        if(is_null($query)){
            $query = $this->_query;
        }
        
        $this->table = $query->table = $this->_prefix.$table;
        $this->_primarykey = $query->primarykey = $this->getPrimaryKey();        
        
        return $this;
    }
    
    /**
     * Set element schema
     * @param type $schema
     */
    public function setSchema(SchemaInterface $schema) {
        
        //assign schema
        $this->schema = $schema;
        
        //add created at string
        $this->schema->setCreationTime(Carbon::now()->toDateTimeString());
        return $this;
    }
    
    /**
     * Sets the builder class into the Active Record
     * @param type $builder
     */
    public function setBuilder($builder){
        $this->builder = $builder;
    }
    
    public function setJoinUpdateVars($updatevars) {     
        
        $this->_join_update = true;
        $this->_join_data = $updatevars;
    }
    
    /**
     * Sets the Select keyword
     * @param type $columns
     */
    public function select($columns = '*') {
        $this->_query->select($columns);
        return $this;
    }
    
    /**
     * Adds a from clause
     * @param type $table
     * @return $this
     */
    public function from($table = null){
        
        if(is_null($table))
            $this->_query->from($this->table);
        else
            $this->_query->from($table);
        
        return $this;
    }
    
    /**
     * Add INSERT keyword
     * @param type $data
     */
    public function insert($data) {
        $this->_query->insert($data);
        return $this;
    }
    
    /**
     * Add UPDATE keyword
     * @param type $data
     */
    public function update() {
        
        $this->_query->update($this->_data, $this->schema->getCreationTime(), $this->updated_at);
        $this->_parseQuery();
        
        return $this->_dbhandle->execute();
    }
    
    /**
     * Sets the select statement by adding a raw expression
     * @param type $expr
     */
    public function selectByExp($expr){        
        $this->_query->selectRaw($expr);
        return $this;
    }
    
    /**
     * Designates the distinct rows
     */
    public function distinct() {
        $this->_query->distinct = true;
        return $this;
    }
    
    /**
     * Performs a check to ascertain if rows are present for the set search conditions
     * @return boolean TRUE or FALSE
     */
    public function exists(){        
        
        $this->selectByExp("COUNT(*) AS `total`");
        
        //reverse statement to move command to front
        $stmt = array_reverse($this->_query->stmt);
        $this->_query->stmt = $stmt;
        
        $this->execute();
        
        $result = $this->_dbhandle->fetchObject();
        
        if($result->total > 0)
            return TRUE;
        else
            return FALSE;            
    }
    
    /**
     * Adds a join condition
     * 
     * @param type $joinTable
     * @param type $joinCondition
     * @param string $joinType
     */
    public function join($joinTable, $joinCondition, $joinType = '', $_fromMapping = FALSE) {
        
        //check for direct joining and mapping        
        if($this->schema->getJoinType() == 'mapped' && $_fromMapping === FALSE){            
            App::warning('Using the join() and foreign key mapping together in one model may create data result overwrites. '
                    . 'Consider mapping the joined table in the model and designating the mapping as lazy using lazy()');
        }
        
        $allowedTypes = array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER');
        $joinType = strtoupper (trim ($joinType));
        $joinTable = filter_var($joinTable, FILTER_SANITIZE_STRING);

        if ($joinType && !in_array ($joinType, $allowedTypes))
            App::critical_error ('Wrong JOIN type: '.$joinType);
        
        //check for prefix
        $this->_join['table']['name'] = $this->_verifyTablePrefix($joinTable);
        
        //add join type
        $this->_join['table']['type'] = $joinType;
        
        //list condition
        list($firsttable, $operator, $secondtable) = $joinCondition;
        return $this->_on($firsttable, $operator, $secondtable);
    }
    
    /**
     * Specify the join condition.Must be specified after a join() function call
     * 
     * @param type $firsttable
     * @param type $operator
     * @param type $secondtable
     * @return $this
     */
    private function _on($firsttable, $operator, $secondtable){
        
        //check for prefix for first and second table
        $fsplit = explode('.', $firsttable);
        $first = $this->_verifyTablePrefix($fsplit[0]);
        
        $ssplit = explode('.', $secondtable);
        $second = $this->_verifyTablePrefix($ssplit[0]);
        
        //tag schema
        $this->schema->is_joined = TRUE;
        
        //set join table
        if($this->schema->isJoinTypeSet()){
            $this->schema->setJoinType('direct');
        }
        
        $this->schema->setJoinTable($this->_join['table']['name']);
        $this->schema->setJoinDirection($this->_join['table']['type']);
        $this->schema->setJoinCondition($this->_join['table']['name'], $first.'.'.$fsplit[1], $operator, $second.'.'.$ssplit[1]);
        
        $this->_query->join(
                $this->_join['table']['name'], 
                $first.'.'.$fsplit[1].' '.$operator.' '.$second.'.'.$ssplit[1], 
                $this->_join['table']['type']);
        
        return $this;
    }
    
    /**
     * Adds the where keyword, when chained they amount to an AND operator i.e. WHERE ... AND WHERE ..
     * 
     * @param type $whereProp
     * @param type $operator
     * @param type $whereValue
     */
    public function where($whereProp, $operator = '', $whereValue = null){
        $this->_query->where($whereProp, $operator, $whereValue, 'AND');
        return $this;
    }
    
    /**
     * Adds the where keyword, when chained they amount to an AND operator i.e. WHERE ... OR WHERE ..
     * 
     * @param type $whereProp
     * @param type $operator
     * @param type $whereValue
     */
    public function orWhere($whereProp, $operator = '', $whereValue = null ) {
        $this->_query->where($whereProp, $operator, $whereValue, 'OR');
        return $this;
    }
    
    /**
     * Adds the IS NULL keyword to the where condition
     * 
     * @param type $whereProp
     */
    public function whereIsNull($whereProp){
        $this->_query->where($whereProp, 'IS NULL', 'NULL', 'AND');
        return $this;
    }
    
    /**
     * Adds the IS NOT NULL keyword to the where condition
     * 
     * @param type $whereProp
     */
    public function whereIsNotNull($whereProp){
        $this->_query->where($whereProp, 'IS NOT NULL', 'NULL', 'AND');
        return $this;
    }
    
    /**
     * Add the groupBy keyword
     * @param type $groupByField
     */
    public function groupBy($groupByField) {
        $this->_query->groupBy($groupByField);
        return $this;
    }
    
    /**
     * Adds an orderBy keyword
     * @param type $orderByField
     * @param type $orderbyDirection
     */
    public function orderBy($orderByField, $orderbyDirection = "DESC"){
        
        $allowedDirection = Array ("ASC", "DESC");
        $orderbyDirection = strtoupper (trim ($orderbyDirection));
        $orderByField = preg_replace ("/[^-a-z0-9\.\(\),_]+/i",'', $orderByField);

        if (empty($orderbyDirection) || !in_array ($orderbyDirection, $allowedDirection))
            App::critical_error('Wrong order direction: '.$orderbyDirection);
        
        $this->_query->orderBy($orderByField, $orderbyDirection);
        return $this;
    }
    
    /**
     * Adds a having condition
     * @param type $condition
     * @return $this
     */
    public function having($condition) {        
        $this->_query->having($condition);
        return $this;
    }
    
    /**
     * Limit query results
     * @param type $limit
     * @param type $offset
     */
    public function limit($limit, $offset = null){        
        $this->_query->limit($limit, $offset);
        return $this;
    }

    /**
     * Returns count of the executed query
     * @return int
     */
    public function count() {
        return $this->_dbhandle->count();
    }
    
    /**
     * Sets the isNew flag
     * @param type $flag
     */
    public function isNew($flag){
        $this->isNew = $flag;
    }
    
    /**
     * Sets the data variable
     * @param type $data
     */
    public function setData($data){
        $this->_data = $data;
    }
    
    /**
     * Delete the set records
     * @param type $numRows
     */
    public function delete($numRows = null) {
        
        //set the primary key in where condition
        $key = $this->getPrimaryKey(); 
        if(!is_null($this->$key)){
            $this->where($key, $this->$key);
        }
        
        //check context
        if($this->hasContext){            
            
            foreach($this->contextColumns as $column => $value){
            
                //check if column exists
                if(property_exists($this->schema, $column)){
                    //add the context column condition
                    $this->where($column, $value);
                }
            }
        }
        
        //add numrows limit
        if(!is_null($numRows)){

            if(is_array($numRows)){

                //filter numrows
                if(count(array_filter($numRows)) > 1){

                    list($limit, $offset) = array_filter($numRows);
                    $this->limit($limit, $offset);
                }
                else{
                    $limit = $numRows;
                    $this->limit($limit[0]);
                }                           
            }
            else{
                $this->limit($numRows);
            }
        }
        
        $this->_query->delete($numRows);
        
        $this->_parseQuery();
        return $this->_dbhandle->execute();
    }
    
    /**
     * Sets dumpQuery flag
     * @param type $flag
     */
    public function dumpQuery(){
        $this->_dumpquery = TRUE;
        return $this;
    }
    
    /**
     * Parses the query object and returns the full query
     */
    private function _parseQuery($retain_query = false, $query = null){
        
        if(is_null($query)){
            $query = $this->_query;
        }
        
        $fullquery = $query->runProcessor($retain_query);
        $this->mysql = $fullquery;
        
        //dump query
        if($this->_dumpquery){
            dump(['query' => $fullquery['stmt'],'arguments' => $fullquery['args']]);
        }
        
        //prepare the query
        $this->_dbhandle->query($fullquery['stmt']);
        
        //bind the params
        if(!is_null($fullquery['args'])){
            
            foreach ($fullquery['args'] as $param => $value) {
                $this->_dbhandle->bind($param, $value);
            }
        }
    }
   
    /**
     * Execute the parsed query
     */
    public function execute() {     
        
        $this->_parseQuery();
        $this->_dbhandle->execute();
    }
    
    /**
     * Sorts through all the attached relations for a given criteria
     * 
     * @param type $relations
     * @param type $criteria
     * @return Relationships An array on Relationship instances
     */
    public function filterRelations($relations, $criteria){
        
        $filter = NULL;
        foreach ($relations as $name => $relation){
            
            foreach($criteria as $condition => $value){
                if($relation->{$condition} == $value){
                    $filter[$name] = $relation;
                }
            }
        }
        
        return $filter;
    }
    
    /**
     * Bypasses adding the main table to the select clause
     */
    public function bypass(){        
        $this->_bypass_table = TRUE;
        return $this;
    }
    
    /**
     * Store the run query
     */
    public function retainQuery(){
        $this->_retain_query = true;
        return $this;
    }
    
    /**
     * Returns the executed query & arguments
     * @param mixed $numRows if INT specify the number of rows to query, if array specify in [$no_of_rows, $offset] format
     * @param type $column
     * @return type
     */
    public function get($numRows = null, $column = '*'){
        
        if(is_null($this->_retain_query)){
            $retain_query = false;
        }
        else{
            $retain_query = $this->_retain_query;
        }
        
        //force-verify table to confirm the query and schema tables match
        if($this->_query->table !== $this->_prefix.$this->schema->table){
            $this->_query->table = $this->_prefix.$this->schema->table;
        }
        
        //if is joined evaluate duplicate columns        
        if($this->schema->is_joined){            
            
            //specify the the query should be retained
            $tables = null;
            
            //build the join tables
            $this->_buildJoinTables($tables, $retain_query);
            
            //evaluate duplicates of select section not set
            if(!is_null($tables)){
                
                $duplicates = $this->_checkForDuplicateCols($tables);

                if($duplicates !== FALSE && !array_key_exists('select', $this->_query->stmt) 
                    && !array_key_exists('selectRaw', $this->_query->stmt)){

                    //loop through the duplicates
                    foreach($duplicates as $table => $cols){

                        //add all the columns in table
                        $this->select($table.'.*');

                        //loop thru the table columns
                        foreach($cols as $col => $alias){
                            $this->select($table.'.'.$col.' as '.$alias);
                        }
                    }
                }      
            }
            
            //add the primary table
            if(!$this->_bypass_table){
                $this->select($this->_query->table.'.*');
            }
        }
        
        //parse the columns param
        if(!array_key_exists('select', $this->_query->stmt) 
                && !array_key_exists('selectRaw', $this->_query->stmt)){
            $this->select($column);
        }
        
        //add numrows limit
        if(!is_null($numRows)){

            if(is_array($numRows)){

                //filter numrows
                if(count(array_filter($numRows)) > 1){

                    list($limit, $offset) = array_filter($numRows);
                    $this->limit($limit, $offset);
                }
                else{
                    $limit = $numRows;
                    $this->limit($limit[0]);
                }                           
            }
            else{
                $this->limit($numRows);
            }
        }
        
        //process the query
        $this->_parseQuery($retain_query); 
        
        //process the num rows
        if($numRows === 1 || $numRows === '1'){        
            $results = $this->_dbhandle->fetchOne();
        }
        else{
            $results = $this->_dbhandle->fetchAll();
        }
        
        if(count($this->_dbhandle->errorlog) === 0){
            return $results;
        }
        else{
            return $this->_dbhandle->errorlog;
        }
    }
    
    /**
     * Builds the linked join tables
     */
    private function _buildJoinTables(&$tables, &$retain_query){
        
        //direct join
        if($this->schema->getJoinType() == 'direct'){                
            $tables = $this->schema->getJoinTables();
        }
        //queued relations
        elseif($this->schema->getJoinType() == 'queued'){
            $retain_query = TRUE;
        }
        //mapped join
        elseif($this->schema->getJoinType() == 'mapped'){

            //set for query retention
            $retain_query = TRUE;

            //filter for only the eager loads or the active relation
            if(!$this->hasActiveRelation()){       

                $mapped = $this->getActiveRelations();
                $relations = $this->filterRelations($mapped, ['is_eager' => true]);      
            }
            else{
                $relations = $this->getActiveRelations();
            }

            //get the filtered table
            if(!is_null($relations)){
                
                $count = 0;
                foreach($relations as $relation){

                    //set table
                    $table = $relation->foreign->table;

                    //check for many to many
                    if($relation->type == 'many-to-many'){

                        $conditions = $relation->getCondition('plain');

                        //add pivot table 
                        $tables[] = $relation->pivot->table;

                        //loop through the conditions
                        foreach($conditions as $table => $params){
                            $this->join($table, [$params['left'], '=', $params['right']], "LEFT", TRUE);
                        }
                    }                    
                    else{

                        //set the join condition for the tables
                        $condition = $relation->getCondition();
                        $this->join($table, $condition, "LEFT", TRUE);
                    }

                    $tables[] = $table;
                    $count++;
                }
            }
        }
    }
    
    /**
     * Checks if errors are present in query
     * @return boolean
     */
    public function hasNoErrors(){
        
        if(count($this->_dbhandle->errorlog) == 0)
            return TRUE;
        
        return FALSE;
    }
    
    /**
     * Returns all logged PD errors
     * @return type
     */
    public function getErrors() {
        return $this->_dbhandle->errorlog;
    }
    
    /**
     * Returns last executed prepared statement and arguments as array
     * @return array
     */
    public function getLastQuery(){
        return $this->mysql;
    }
    
    /**
     * Returns last logged error
     */
    public function getLastError() {
        return end($this->_dbhandle->errorlog);
    }
    
    /**
     * Gets the last insert id
     * @return type
     */
    public function getLastInsertId(){
        return $this->_dbhandle->getPdo()->lastInsertId();
    }
    
    /**
     * Returns the PDO Handler
     * @return type
     */
    public function getHandle(){
        return $this->_dbhandle;
    }
    
    public function getCloneHandle(){
        return clone $this->_dbhandle;
    }
    
    /**
     * Returns logged errors
     * @return type
     */
    public function errors(){
        return $this->_dbhandle->errorlog;
    }
    
    /**
     * Saves Active Record
     */
    public function save(){
        
        if($this->isNew) {
            $action = 'insert';
            $this->_query->insert($this->_data, $this->schema->getUpdateTime());
        }
        else{
            $action = 'update';
            $this->_query->update($this->_data, $this->schema->getCreationTime(), $this->schema->getUpdateTime());
        }
        
        //if join update is set start pdo transaction
        if($this->_join_update){            
            $this->saveJoinUpdate();
        }
        else{
            
            $this->_parseQuery(); 
            $this->_dbhandle->execute();
            
            //check result
            if($this->hasNoErrors() && $action == 'insert'){
                
                //hydrate the schema after new insert
                $id = $this->getPdo()->lastInsertId();                
                $this->schema->id = $id;
                
                return $id;
            }
            elseif($this->hasNoErrors() && $action == 'update'){
                return $this->count();
            }
        }
    }
    
    /**
     * Checks if the table name has the prefix
     * @param type $tablename
     * @return boolean
     */
    private function _verifyTablePrefix($tablename){
        
        $prefix = $this->_prefix;
        
        if(strpos($tablename, $prefix) === 0){
            return $tablename;
        }
        
        return $prefix.$tablename;
    }
    
    /**
     * Check for duplicate columns in the various tables
     */
    private function _checkForDuplicateCols($tables){
        
        $list = $index = $colslist = [];
        
        //get primary table cols
        $cols = $this->builder->table($this->_query->table)->getColumns(['Field']);
        $mainlist = array_merge($list,$cols);        
        
        foreach($tables as $table){
            
            //check table columns
            $table = $this->_verifyTablePrefix($table);
            
            $cols = $this->builder->table($table)->getColumns(['Field']);
            $intersect = array_intersect($mainlist, $cols);
            
            //generate new names for intersect
            if(count($intersect) > 0){
                
                foreach($intersect as $col){
                    
                    $newname = $table.'_'.$col;                    
                    $index[$table][$col] = $newname;
                }
            }
        }
        
        //add everything to list
        if(count($index) > 0){
            return $index;
        }
        
        return FALSE;
    }
}
