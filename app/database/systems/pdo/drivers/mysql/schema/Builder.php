<?php
namespace Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Schema;

use Jenga\App\Helpers\Help;
use Jenga\App\Database\Systems\Pdo\Handlers\PDOHandler;
use Jenga\App\Models\Interfaces\BuilderInterface;

/**
 * Schema class is designed to create, manage and delete database tables and columns in MYSQL
 * @author Stanley Ngumo
 */

class Builder implements BuilderInterface{

    public $handle;
    public $table = null;
    public $prefix;
    public $output;
    public $errors;

    private $_sqlquery;
    private $_sqlcols = [];
    private $_primary = null;
    private $_unique = [];
    private $_comment = [];
    private $_fk = [];
    private $_dropifexists = false;
    private $_collation = null;
    private $_engine = null;

    public function __construct(PDOHandler $handle, $prefix) {        
        $this->handle = $handle;
        $this->prefix = $prefix;
    }

    /**
     * Assigns the table to be created
     * @param type $table
     */
    public function table($table) {

        if(strstr($table, $this->prefix))
            $this->table  = $table;
        else
            $this->table = $this->prefix.$table;

        return $this;
    }
    
    /**
     * Checks if the table name has the prefix
     * @param type $tablename
     * @return boolean
     */
    private function _verifyTablePrefix($tablename){
        
        $prefix = $this->prefix;
        
        if(strpos($tablename, $prefix) === 0){
            return $tablename;
        }
        
        return $prefix.$tablename;
    }

    /**
     * @author: Sam Maosa
     * Assigns the database prefix to the ref table in FK relationships
     * @param $table
     * @return string
     */
    public function ref_table($table) {

        if(strstr($table, $this->prefix))
            return $table;
        else
            return $this->prefix.$table;
    }
    
    /**
     * Checks for error information from executed query
     * @return mixed
     */
    public function hasNoErrors(){
        
        //get the errorInfo
        $errors = $this->output->errorInfo();
        
        if(is_null($errors[2]))
            return TRUE;
        else
            $this->errors = $errors;
    }
    
    /**
     * Return the set errors
     */
    public function errors(){
        return $this->errors;
    }

    /**
     * Sets the dropifexists marker to drop the existing table on table creation
     */
    public function dropifExists($boolean = true) {
        
        $this->_dropifexists = $boolean;
        return $this;
    }

    /**
     * Drops an existing table
     * 
     * @param type $table
     * @return boolean
     */
    public function dropTable($table) {

        if($this->hasTable($this->prefix.$table)){
            
            $query = "DROP TABLE ".$this->prefix.$table;
            
            $this->_sqlquery = $query;
            $this->output = $this->handle->rawQuery($this->_sqlquery);
        }
        
        if($this->hasNoErrors() >= 1)
            return TRUE;
        else
            return FALSE;
    }
    
    /**
     * Drops an existing foreign key
     * 
     * @param type $table
     * @return boolean
     */
    public function dropForeignKey($table, $key) {
        
        $this->_sqlquery = "ALTER TABLE ".$table." DROP FOREIGN KEY IF EXISTS ".$key;        
        $this->output = $this->handle->rawQuery($this->_sqlquery);

        if($this->hasNoErrors())
            return TRUE;
        else 
            return $this->errors();
    }

    /**
     * Assign the columns to created along with their attributes
     * @param type $name
     * @param type $attributes
     */
    public function column($name, $attributes = []){

        $attrs = $this->_processAttributes($attributes);

        $this->_sqlcols[$name] = $attrs;
        return $this;
    }

    /**
     * Converts array of attributes into SQL variables
     * @param type $attributes
     * @return type
     */
    private function _processAttributes($attributes){

        if(Help::isAssoc($attributes)){

            foreach($attributes as $key => $value){
                $attrs[] = $key.'('.$value.')';
            }
        }
        else{
            $attrs = $attributes;
        }

        return $attrs;
    }

    /**
     * Designates the table primary column
     * @param type $column
     */
    public function primary($column) {

        $this->_primary = $column;
        return $this;
    }
    
    /**
     * Sets the table engine type
     * @param type $engine
     * @return $this
     */
    public function engine($engine){
        
        $this->_engine = $engine;
        return $this;
    }
    
    /**
     * Sets the table collation type
     * @param type $type
     * @return $this
     */
    public function collation($type) {
        
        $this->_collation = $type;
        return $this;
    }

    /**
     * Designates columns as UNIQUE
     * @param type $column
     */
    public function unique($column){

        if(is_array($column)){
            $this->_unique[] = join(',', $column);
        }
        else{
            $this->_unique[] = $column;
        }
        
        return $this;
    }
    
    /**
     * Adds a comment to the table
     * @param type $comment
     */
    public function comment($column, $comment){
        $this->_comment[$column] = $comment;
        return $this;
    }

    /**
     * Assigns foreign key
     * @param string $name
     */
    public function foreign($name) {
        $this->_fk['name'] = $name;
        return $this;
    }

    /**
     * Assigns referenced column
     * @param string $refcol
     */
    public function references($refcol) {
        $this->_fk['refcolumn'] = $refcol;
        return $this;
    }

    /**
     * Assigns reference table
     * @param type $table
     */
    public function on($table){
        $this->_fk['reftable'] = $this->ref_table($table);/*Changes: Added the ref_table function*/
       return $this;
    }

    /**
     * Sets the ON DELETE options namely: NO ACTION,RESTRICT,SET NULL, CASCADE, SET DEFAULT
     * @param type $options
     */
    public function onDelete($options) {
        $this->_fk['ondelete'] = strtoupper($options);
        return $this;
    }

    /**
     * Sets the ON UPDATE options namely: NO ACTION,RESTRICT,SET NULL, CASCADE, SET DEFAULT
     * @param type $options
     */
    public function onUpdate($options){
        $this->_fk['onupdate'] = strtoupper($options);
        return $this;
    }

    /**
     * Process the foreign key constraints
     * @param type $fk
     */
    private function _parseFKConstraints($fk){
        /**
         * @author: Sam Maosa
         * @changes: Changed the return variable from $fk to $_fk to avoid overwriting the param $fk;
         * Changed the column to which the key is to be attached from $fk['refcolumn'] to $ref['name']
         *
         */
        $_fk = ' FOREIGN KEY fk_'.str_replace($this->prefix, '', $this->table)
                .'_'.str_replace($this->prefix, '', $fk['reftable']).'('.$fk['name'].')'; /*Changes: changed $fk['refcolumn'] to $fk['name']*/
        $_fk .= ' REFERENCES '.$fk['reftable'].'('.$fk['refcolumn'].') ';
        
        //on delete
        if(array_key_exists('ondelete', $fk))
            $_fk .= 'ON DELETE '.$fk['ondelete'].' ';

        //on update
        if(array_key_exists('onupdate', $fk))
            $_fk .= 'ON UPDATE '.$fk['onupdate'];

        return $_fk;
    }

    /**
     * Checks if sent table already exists
     * @param type $table
     */
    public function hasTable($table) {

        $this->_sqlquery = "SHOW TABLES LIKE '".$table."'";
        
        $this->output = $this->handle->rawQuery($this->_sqlquery);
        $this->output->execute();
        
        if(count($this->output->fetchAll()) >= 1)
            return TRUE;
        else
            return FALSE;
    }
    
    /**
     * Returns all the created tables
     */
    public function getAllTables(){
        
        $this->_sqlquery = "SHOW TABLES";
        $this->output = $this->handle->rawQuery($this->_sqlquery);
        $results = $this->output->fetchAll();
        
        $list = [];
        foreach($results as $table){
            $list[] = array_values($table)[0];
        }
        
        return $list;
    }
    
    /**
     * Returns the table columns
     * @param type $filters
     */
    public function getColumns($filters = []){
        
        $this->_sqlquery = "DESCRIBE ".$this->table;
        $this->output = $this->handle->rawQuery($this->_sqlquery);
        
        $results = $this->output->fetchAll();
        
        if(count($filters) > 0){
            
            foreach ($results as $result) {
                
                foreach ($filters as $filter) {
                    
                    if(array_key_exists(ucfirst($filter), $result)){
                        $filterlist[] = $result[ucfirst($filter)];
                    }
                }
            }
        }
        else{
            $filterlist = Help::getArrayCopy($results);
        }
        
        return $filterlist;
    }
    
    /**
     * Gets PDO handle
     * @return PDOHandler
     */
    public function getHandle(){
        return $this->handle;
    }
    
    /**
     * Returns the set sql query
     * @return type
     */
    public function getSQLQuery(){
        return $this->_sqlquery;
    }

    /**
     * Check if column exists
     * @param type $column
     * @return boolean
     */
    public function hasColumn($column){

        $this->_sqlquery = "SHOW COLUMNS FROM ".$this->table." LIKE '".$column."'";
        $this->output = $this->handle->rawQuery($this->_sqlquery);        
        $this->output->execute();
        
        if(count($this->output->fetchAll()) >= 1)
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Add primary key
     * @param type $column
     * @return boolean
     */
    public function addPrimaryKey($column) {

        if($this->hasColumn($column) == TRUE){
            
            $this->_sqlquery = 'ALTER TABLE `'.$this->table.'` ADD PRIMARY KEY '.$column;
            $this->output = $this->handle->rawQuery($this->_sqlquery);
            
            if($this->hasNoErrors()){
                return TRUE;
            }
            else {
                return $this->errors();
            }
        }

        return FALSE;
    }
    
    /**
     * Delete primary key
     * @param type $column
     * @return boolean
     */
    public function deletePrimaryKey($column) {

        if($this->hasColumn($column) == TRUE){
            
            $query = 'ALTER TABLE `'.$this->table.'` DROP PRIMARY KEY';
            
            $this->_sqlquery = $query;
            $this->output = $this->handle->rawQuery($this->_sqlquery);
            
            if($this->hasNoErrors()){
                return TRUE;
            }
            else {
                return $this->errors();
            }
        }

        return FALSE;
    }
    
    /**
     * Adds the Unique constraint to an existing table column
     *
     * @param string $column
     * @return boolean TRUE or FLASE id column not present
     */
    public function addUniqueConstraint($column) {

        if($this->hasColumn($column) == TRUE){
            
            $query = 'ALTER TABLE `'.$this->table.'` ADD CONSTRAINT uc_'.$column.' UNIQUE ('.$column.')';
            
            $this->_sqlquery = $query;
            $this->output = $this->handle->rawQuery($this->_sqlquery);
            
            if($this->hasNoErrors()){
                return TRUE;
            }
            else {
                return $this->errors();
            }
        }

        return FALSE;
    }
    
    /**
     * Deletes the Unique constraint to an existing table column
     *
     * @param string $column
     * @return boolean TRUE or FALSE id column not present
     */
    public function deleteUniqueConstraint($column) {

        if($this->hasColumn($column) == TRUE){
            $query = 'ALTER TABLE `'.$this->table.'` DROP INDEX uc_'.$column;
            
            $this->_sqlquery = $query;
            $this->output = $this->handle->rawQuery($this->_sqlquery);
            
            if($this->hasNoErrors()){
                return TRUE;
            }
            else {
                return $this->errors();
            }
        }

        return FALSE;
    }

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
    public function addForeignKey($referredcolumn, $referredtable, $ondelete = NULL, $onupdate = NULL){

        $local = str_replace($this->prefix, '', $this->table);
        $foreign = $this->_verifyTablePrefix($referredtable);
        
        $query = 'ALTER TABLE '
                . '`'.$this->table.'` '
                . 'ADD FOREIGN KEY fk_'.$local.'_'.$referredtable.' ('.$referredcolumn.') '
                . 'REFERENCES '.$foreign.'('.$referredcolumn.') '
                . (!is_null($ondelete) ? 'ON DELETE '.$ondelete.' ' : '')
                . (!is_null($onupdate) ? 'ON UPDATE '.$onupdate.' ' : '');
        
        $this->_sqlquery = $query;
        $this->output = $this->handle->rawQuery($this->_sqlquery);

        if($this->hasNoErrors())
            return TRUE;
        else
            return $this->errors();
    }
    
    /**
     * Disables or enables foreign key checks
     * @param boolean $check
     * @return boolean
     */
    public function setForeignKeyCheck($check = 1){
        
        $query = 'SET foreign_key_checks = '.$check;
        
        $this->_sqlquery = $query;
        $this->output = $this->handle->rawQuery($this->_sqlquery);
        
        if($this->hasNoErrors())
            return TRUE;
        else
            return $this->errors();
    }

    /**
     * Adds a new column
     * 
     * @param type $column
     * @param type $attrs
     * @param type $after
     * @return boolean
     */
    public function addColumn($column, $attrs, $after = null){

        $attributes = $this->_processAttributes($attrs);
        
        if($this->hasColumn($column) === FALSE){
            
            $this->_sqlquery = 'ALTER TABLE `'.$this->table.'` ADD COLUMN '.$column.' '.join(' ', $attributes).(!is_null($after) ? ' AFTER '.$after : '');
            $this->output = $this->handle->rawQuery($this->_sqlquery);
            
            if($this->hasNoErrors())
                return TRUE;
            else
                return $this->errors();
        }

        return FALSE;
    }

    /**
     * Removes an existing table column
     * @param type $column
     */
    public function removeColumn($column){

        if($this->hasColumn($column)){
            
            $query = 'ALTER TABLE `'.$this->table.'` DROP '.$column;
            
            $this->_sqlquery = $query;
            $this->output = $this->handle->rawQuery($this->_sqlquery);
            
            if($this->hasNoErrors())
                return TRUE;
            else
                return $this->errors();
        }

        return FALSE;
    }

    /**
     * Renames table column
     *
     * @param string $before
     * @param string $after
     * @param array $attrs
     *
     * @return boolean
     */
    public function renameColumn($before, $after, $attrs){

        $attributes = $this->_processAttributes($attrs);
        
        if($this->hasColumn($before)){            
            
            $query = 'ALTER TABLE '.$this->table.' CHANGE '.$before.' '.$after.' '.join(' ',$attributes);
            
            $this->_sqlquery = $query;
            $this->output = $this->handle->rawQuery($this->_sqlquery);
            
            if($this->hasNoErrors())
                return TRUE;
            else
                return $this->errors();
        }

        return FALSE;
    }
    
    /**
     * Modifies a column's data type
     * @param type $before
     * @param type $after
     * @param type $attrs
     * @return boolean
     */
    public function modifyColumn($column, $attrs){

        $attributes = $this->_processAttributes($attrs);
        
        if($this->hasColumn($column)){        
            
            $query = 'ALTER TABLE '.$this->table.' MODIFY COLUMN '.$column.' '.join(' ',$attributes);
            
            $this->_sqlquery = $query;
            $this->output = $this->handle->rawQuery($this->_sqlquery);
            
            if($this->hasNoErrors())
                return TRUE;
            else
                return $this->errors();
        }

        return FALSE;
    }
    
    /**
     * Renames table
     * @param type $table_name
     * @return boolean
     */
    public function renameTable($table_name){
        
        if(!$this->hasTable($this->prefix . $table_name)){
            
            $query = "RENAME TABLE ".$this->table." to ".$this->prefix . $table_name;
            
            $this->_sqlquery = $query;
            $this->output = $this->handle->rawQuery($this->_sqlquery);
            
            if($this->hasNoErrors())
                return TRUE;
            else
                return $this->errors();
        }
        
        return FALSE;
    }

    /**
     * Compiles the tables and sent coulmns and runs the respective database table creation query
     * @author: Sam Maosa
     * @changes:
     */
    public function build($dropifExists = false){
        
        $columns = '';

        if(!is_null($this->table)){

            //run the drop table query before creating the new one
            if($this->_dropifexists === TRUE || $dropifExists === TRUE){
                $this->_sqlquery .= 'DROP TABLE IF EXISTS '.$this->table.';';
            }
            
            $this->_sqlquery .= 'CREATE TABLE '.$this->table.' ';

            //process columns
            foreach($this->_sqlcols as $column => $attributes){
                $columns .= $column.' '.join(' ', $attributes);
                
                //check for comment
                if(array_key_exists($column, $this->_comment)){
                    $columns .= " COMMENT '". $this->_comment[$column] ."' ";
                }
                
                $columns .= ', ';
            }

            //process primary column
            if(!is_null($this->_primary)){
                $columns .= 'primary key ('.$this->_primary.'),';//@maosa-sam Added a comma at the end
            }

            //process unique columns
            if(count($this->_unique) !== 0){
                $columns .= 'UNIQUE ('.join(',', $this->_unique).'), '; //@maosa-sam added a comma at the end
            }

            //foreign key
            if(count($this->_fk) !== 0){
                $columns .= $this->_parseFKConstraints($this->_fk);
            }

            //trim commas
            $columns = rtrim($columns, ', ');
            $this->_sqlquery .= '('.$columns.')';
        }
        
        //add engine
        if(!is_null($this->_engine))
            $this->_sqlquery .= ' ENGINE = '.$this->_engine.' ';
        
        //add collation
        if(!is_null($this->_collation))
            $this->_sqlquery .= 'COLLATE '.$this->_collation;
        
        return $this->handle->rawQuery($this->_sqlquery);
    }
}
