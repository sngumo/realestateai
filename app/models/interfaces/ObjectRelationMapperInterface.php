<?php
namespace Jenga\App\Models\Interfaces;

/**
 * Interface for the abstract ORM class
 * @author stanley
 */
interface ObjectRelationMapperInterface {
    
    public function boot(SchemaInterface $schema, ActiveRecordInterface $record);
    
    /**
     * Selects the first row of the queried results
     * @param type $column
     * @return $this
     */
    public function first($column = '*');
    
    /**
     * Synonym for get() function
     * @param type $numRows
     * @param type $column
     * @return type
     */
    public function show($numRows = null, $column = '*');
    
    /**
     * Returns all the rows within  given table
     */
    public function all();
    
    /**
     * Returns specified row pointer by count 
     * @param type $pointers
     */
    public function pluck($pointers);
    
    /**
     * Allows the joined tables to be accessed separately
     * @param type $table
     */
    public function on($table = null);
    
    /**
      * This function directly loads a specific row based on the id of the primary or search column
     * 
     * @param mixed $id if array the array key should be the search column to be used and its value eg ['name'=> $name],
     *                  if string, the value will be compared against the table primary key
     * @param type $select_column to be returned in result
     * 
     * @return mixed
     */
    public function find($id, $select_column='*');
    
    /**
     * This is the function to just retrieve the rows linked to the set table
     * 
     * @param type $numRows
     * @param type $column
     * @return ActiveRecordInterface
     */
    public function get($numRows = null, $column = '*');
    
    /**
     * Extracts any expressions data in the data section
     * @param type $exp
     */
    public function getExp($exp);
    
    /**
     * Saves the current record
     * @param SchemaInterface array $schema Foreign schema if a relation is attached
     * @param array $pivotcols Any column values to be inserted into the pivot table
     * @return type
     */
    public function save($schema = null, array $pivotcols = null);
}
