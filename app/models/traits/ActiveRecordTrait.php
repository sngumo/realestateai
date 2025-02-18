<?php
namespace Jenga\App\Models\Traits;

/**
 * Adds some redundancy for the Model class for ease of use within the element model since the actual
 * ActiveRecord class is not initiated until the connections are resolved
 * 
 * @author stanley
 */
trait ActiveRecordTrait {
    
    /**
     * Adds SELECT keyword
     * @param type $columns
     * @return $this
     */
    public function select($columns = '*'){
        $this->record->select($columns);
        return $this;
    }
    
    /**
     * This method allows you to concatenate joins for the final SQL statement. 
     *
     * @uses $this->join('table1', 'LEFT')->on('table1.id','=','')
     * 
     * @after on($firsttable, $operator, $secondtable)
     * 
     * @param string $joinTable The name of the table
     * @param array $joinCondition Example ['articles.catid', '=', 'categories.catid']
     * @param string $joinType 'LEFT', 'INNER' etc.
     * 
     * @return Database
     */
    public function join($joinTable, $joinCondition, $joinType = 'LEFT'){
        $this->record->join($joinTable, $joinCondition, $joinType = 'LEFT');
        return $this;
    }
    
    /**
     * Adds the where keyword, when chained they amount to an AND operator i.e. WHERE ... AND WHERE ..
     * 
     * @param type $whereProp
     * @param type $operator - 
     * Allowed operators are 'BETWEEN','NOT BETWEEN','LIKE','NOT LIKE','IN','NOT IN','IS NOT','IS NOT NULL','IS NULL
     * <, <=,=,!=,:=,^,|,<=>,->,>=,>
     * @example $this->where('id','between',[500,600]) for BETWEEN and IN operators, the whereValue can be an array
     * @param mixed $whereValue
     * @return $this
     */
    public function where($whereProp, $operator = '' , $whereValue = null){
        $this->record->where($whereProp, $operator, $whereValue);
        return $this;
    }
    
    /**
     * Adds the where keyword, when chained they amount to an AND operator i.e. WHERE ... OR WHERE ..
     * 
     * @param type $whereProp
     * @param type $operator
     * @param type $whereValue
     * @return $this
     */
    public function orWhere($whereProp, $operator = '', $whereValue = null ){
        $this->record->orWhere($whereProp, $operator, $whereValue);
        return $this;
    }
    
    /**
     * Adds the IS NULL keyword to the where condition
     * @param type $whereProp
     * @return $this
     */
    public function whereIsNull($whereProp){
        $this->record->whereIsNull($whereProp);
        return $this;
    }
    
    /**
     * Adds the IS NOT NULL keyword to the where condition
     * @param type $whereProp
     * @return $this
     */
    public function whereIsNotNull($whereProp){
        $this->record->whereIsNotNull($whereProp);
        return $this;
    }
    
    /**
     * Adds a having condition
     * @param type $condition
     * @return $this
     */
    public function having($condition) {        
        $this->record->having($condition);
        return $this;
    }
    
    /**
     * Adds group by column 
     * @param type $groupByField
     * @return type
     */
    public function groupBy($groupByField) {        
        $this->record->groupBy($groupByField);
        return $this;
    }
    
    /**
     * Adds an orderBy keyword
     * @param type $orderByField
     * @param type $orderbyDirection
     */
    public function orderBy($orderByField, $orderbyDirection = "DESC"){        
        $this->record->orderBy($orderByField, $orderbyDirection);
        return $this;
    }
    
    /**
     * Returns row count
     */
    public function count(){
        return $this->record->count();
    }
    
    /**
     * Performs a check to ascertain if rows are present for the set search conditions
     */
    public function exists(){        
        return $this->record->exists();
    }
    
    /**
     * Checks if errors are present in query
     * @return boolean
     */
    public function hasNoErrors(){
        return $this->record->hasNoErrors();
    }
    
    /**
     * Returns last executed prepared statement and arguments as array
     * @return array
     */
    public function getLastQuery(){
        return $this->record->getLastQuery();
    }
    
    /**
     * Returns last logged error
     */
    public function getLastError(){
        return $this->record->getLastError();
    }
    
    /**
     * Deletes specified records
     */
    public function delete($numRows = null) {
        return $this->record->delete($numRows);
    }
    
    /**
     * Dump MySql query
     */
    public function dumpQuery() {
        $this->record->dumpQuery();
        return $this;
    }
    
    /**
     * Saves data set into the model either an insert or update
     * @return $this
     */
    public function save() {
        return $this->record->save();
    }
    
    /**
     * Updates data set into the model via a particular record
     * @return $this
     */
    public function update() {
        return $this->record->update();
    }
}
