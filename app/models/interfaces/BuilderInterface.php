<?php
namespace Jenga\App\Models\Interfaces;

/**
 * Interface for the schema builder class
 * @author stanley
 */
interface BuilderInterface {
    
    /**
     * Adds a new column
     *
     * @param type $column
     * @param type $attrs
     */
    public function addColumn($column, $attrs);
    
    /**
     * Adds foreign key constraint to table
     *
     * @param type $referredcolumn
     * @param type $referredtable
     * @param type $ondelete
     * @param type $onupdate
     *
     * @return boolean
     */
    public function addForeignKey($referredcolumn, $referredtable, $ondelete = NULL, $onupdate = NULL);
    
    /**
     * Adds the Unique constraint to an existing table column
     *
     * @param string $column
     * @return boolean TRUE or FLASE id column not present
     */
    public function addUniqueConstraint($column);
    
    /**
     * Compiles the tables and sent columns and runs the respective database table creation query
     * @author: Sam Maosa
     * @changes:
     */
    public function build();
    
    /**
     * Sets the table collation type
     * @param type $type
     * @return $this
     */
    public function collation($type);
    
    /**
     * Assign the columns to created along with their attributes
     * @param type $name
     * @param type $attributes
     */
    public function column($name, $attributes = []);
    
    /**
     * Sets the flag to drop the existing table on table creation
     */
    public function dropifExists($boolean = true);
    
    /**
     * Drops an existing table
     * 
     * @param type $table
     * @return boolean
     */
    public function dropTable($table);
    
    /**
     * Sets the table engine type
     * @param type $engine
     * @return $this
     */
    public function engine($engine);
    
    /**
     * Return the set errors
     */
    public function errors();
    
    /**
     * Assigns foreign key
     * @param string $name
     */
    public function foreign($name);
    
    /**
     * Check if column exists
     * @param type $column
     * @return boolean
     */
    public function hasColumn($column);
    
    /**
     * Checks for error information from executed query
     * @return mixed
     */
    public function hasNoErrors();
    
    /**
     * Assigns reference table
     * @param type $table
     */
    public function on($table);
    
    /**
     * Sets the ON DELETE options namely: NO ACTION,RESTRICT,SET NULL, CASCADE, SET DEFAULT
     * @param type $options
     */
    public function onDelete($options);
    
    /**
     * Sets the ON UPDATE options namely: NO ACTION,RESTRICT,SET NULL, CASCADE, SET DEFAULT
     * @param type $options
     */
    public function onUpdate($options);
    
    /**
     * Designates the table primary column
     * @param type $column
     */
    public function primary($column);
    
    /**
     * Assigns referenced column
     * @param string $refcol
     */
    public function references($refcol);
    
    /**
     * Removes an existing table column
     * @param type $column
     */
    public function removeColumn($column);
    
    /**
     * Renames table column
     *
     * @param string $before
     * @param string $after
     * @param array $attrs
     *
     * @return boolean
     */
    public function renameColumn($before, $after, $attrs);
    
    /**
     * Disables or enables foreign key checks
     * @param boolean $check
     * @return boolean
     */
    public function setForeignKeyCheck($check = 1);
    
    /**
     * Assigns the table to be created
     * @param type $table
     */
    public function table($table);
    
    /**
     * Designates columns as UNIQUE
     * @param type $column
     */
    public function unique($column);
}
