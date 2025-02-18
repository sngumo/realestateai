<?php
namespace Jenga\App\Models\Interfaces;

/**
 * Fool-proofing for element schema
 * @author stanley
 */
interface SchemaInterface {
    
    /**
     * This must be declared for the table
     */
    //public final $table;
    
    /**
     * This will be run when seeding table and during migrations
     */
    public function seed();
    
    /**
     * This will be run when exporting during migrations
     */
    public function export();
    
    /**
     * This is for running advanced operations on the table
     */
    public function run();
    
    /**
     * Returns created at time
     * @return type
     */
    public function getCreationTime();
    
    /**
     * Returns updated at time
     * @return type
     */
    public function getUpdateTime();
    
    /**
     * Resets the schema timestamps
     */
    public function resetTimeStamps();
    
    /**
     * Returns join data
     * @return type
     */
    public function getJoinData($table = null);
    
    /**
     * Set join data
     * @param type $table
     * @param type $col
     * @param type $value
     */
    public function setJoinData($table, $col, $value);
    
    /**
     * Sets Join data
     * @param type $table
     */
    public function setJoinTable($table);
    
    /**
     * Get the joined tables
     */
    public function getJoinTables();
    
    /**
     * Sets join type
     * @param type $type
     */
    public function setJoinType($type);
    
    /**
     * Sets the join condition
     * @param type $joined_table
     * @param type $lefttablecol
     * @param type $operator
     * @param type $righttablecol
     */
    public function setJoinCondition($joined_table, $lefttablecol, $operator, $righttablecol);
    
    /**
     * Returns the join type
     * @return type
     */
    public function getJoinType();
    
    /**
     * Get MySql join condition
     * @param type $table
     * @return type
     */
    public function getJoinCondition($table);
}
