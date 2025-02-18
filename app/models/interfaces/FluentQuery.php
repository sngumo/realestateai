<?php
namespace Jenga\App\Models\Interfaces;

/**
 * Forces the DBse class to be fluent
 * @author stanley
 */
interface FluentQuery {
    
    public function query($stmt, $args);
    
    public function find($id, $select_column='*');
    
    public function all();
    
    public function get($numRows = null, $column = '*');
    
    public function first($column = null);
    
    public function show($numRows = null, $column = '*');
    
    /**
     * Checks if there are records for the specified conditions
     */
    public function exists();
    
    public function delete();
    
    public function hasNoErrors();
    
    public function outputAs($format);
}
