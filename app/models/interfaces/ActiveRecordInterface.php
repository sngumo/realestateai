<?php
namespace Jenga\App\Models\Interfaces;

use Jenga\App\Models\Interfaces\SchemaInterface;

/**
 * The structure for the query builder
 * 
 * @author stanley
 */
interface ActiveRecordInterface {
    
    public function get();
    
    /**
     * Sets the table prefix
     * @param type $prefix 
     */
    public function setPrefix($prefix);
    
    /**
     * Sets the primary table name
     * @param type $table
     */
    public function setTable($table);
    
    /**
     * Set element schema
     * @param type $schema
     */
    public function setSchema(SchemaInterface $schema);
    
    /**
     * Adds the select binding
     * @param type $columns
     */
    public function select($columns = '*');
    
    /**
     * Takes raw expression and arguments
     * @param type $stmt
     */
    public function selectByExp($stmt);
    
    /**
     * Designates the distinct rows
     */
    public function distinct();
    
    /**
     * Adds a join condition
     * 
     * @param type $joinTable
     * @param type $joinCondition
     * @param type $joinType
     */
    public function join($joinTable, $joinCondition, $joinType = '');
    
    /**
     * This adds the where keyword, when chained they amount to an AND operator i.e. WHERE ... AND WHERE ..
     * 
     * @example $dbrecord->where('id', 7)->orWhere('title', 'MyTitle');
     * 
     * @param type $whereProp Name of the column(s)
     * @param type $operator Set to AND by default, allows for BETWEEN / NOT BETWEEN, IN / NOT IN, <=> (and its other combinations) <br/> NOTE: this can be left blank, if none is provided, as in example below
     * @param type $whereValue Value of the column(s)
     * 
     */
    public function where($whereProp, $operator = '' , $whereValue = null);
    
    /**
     * This adds the where keyword, when chained they amount to an AND operator i.e. WHERE ... OR WHERE ..
     * 
     * @uses $MySqliDb->where('id', 7)->orWhere('title', 'MyTitle');
     * 
     * @param type $whereProp
     * @param type $operator this is as explained above
     * @param type $whereValue
     * 
     */
    public function orWhere($whereProp, $operator = '', $whereValue = null );
    
    /**
     * Adds the IS NULL keyword to the where condition
     * @param type $whereProp
     */
    public function whereIsNull($whereProp);
    
    /**
     * Adds the IS NOT NULL keyword to the where condition
     * @param type $whereProp
     */
    public function whereIsNotNull($whereProp);
    
    /**
     * Returns count of the executed query
     */
    public function count();
    
    /**
     * Add the groupBy keyword
     * @param type $groupByField
     */
    public function groupBy($groupByField);
    
    /**
     * Adds an orderBy keyword
     * @param type $orderByField
     * @param type $orderbyDirection
     */
    public function orderBy($orderByField, $orderbyDirection = "DESC");
    
    /**
     * Adds an orderBy keyword
     * @param type $condition
     */
    public function having($condition);
    
    /**
     * Adds delete keyword
     * @param type $numRows
     */
    public function delete($numRows = null);
    
    /**
     * Checks if errors are present in query
     * @return boolean
     */
    public function hasNoErrors();

    /**
     * Returns last executed prepared statement and arguments as array
     * @return array
     */
    public function getLastQuery();
    
    /**
     * Returns last logged error
     */
    public function getLastError();
    
    /**
     * Gets the last insert id
     * @return type
     */
    public function getLastInsertId();
}
