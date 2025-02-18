<?php
namespace Jenga\App\Models\Applies;

use Carbon\Carbon;

/**
 * Attaches the soft delete methods to the model
 * @author stanley
 */
trait SoftDelete {
    
    /**
     * Designates the trash column
     * @var string
     */
    public $trashcol = 'trashed_at';
    
    /**
     * Include trash flag
     * @var type 
     */
    private $_include_trash = false;
    
    /**
     * Select only trash flag
     * @var type 
     */
    private $_pluck_trash = false;
    
    /**
     * Implements the soft delete get()
     * @param type $numRows
     * @param type $column
     * @param type $_defer_mapping
     * @return type
     */
    public function get($numRows = null, $column = '*', $_defer_mapping = false){
        
        //include trash
        if($this->_include_trash === false && $this->_pluck_trash === false){
            $this->whereIsNull($this->schema->table.'.'.$this->trashcol);
        }
        
        //only trash
        if($this->_pluck_trash === true){
            $this->whereIsNotNull($this->schema->table.'.'.$this->trashcol);
        }
        
        return parent::get($numRows, $column, $_defer_mapping);
    }
    
    /**
     * Include the trashed rows
     */
    public function withTrash(){
        $this->_include_trash = true;
        return $this;
    }
    
    /**
     * Select only the trashed columns
     * @return $this
     */
    public function onlyTrash(){
        $this->_pluck_trash = TRUE;
        return $this;
    }
    
    /**
     * Trashes the select rows
     * @param type $numRows
     */
    public function delete($numRows = null){
        
        //set the primary key in where condition
        $key = $this->getPrimaryKey(); 
        if(!is_null($this->$key)){
            $this->where($key, $this->$key);
        }
        
        $rows = $this->get($numRows);
        
        //set the trashed columns
        if(!is_null($rows) && count($rows) > 0){
            
            $responses = [];
            foreach($rows as $row){

                //set the trashed_at column
                $row->{$this->trashcol} = Carbon::now()->toDateTimeString();

                //save trash column
                $responses[] = $row->save();
            }

            //check resonses
            if(count($responses) == 1){
                return $responses[0];
            }
            
            return $responses;
        }
        
        return FALSE;
    }
    
    /**
     * Restores the trashed columns
     * @param type $numRows
     * @return type
     */
    public function restore($numRows){
        
        $rows = $this->get($numRows);
        
        //set the trashed columns
        if(count($rows) > 0){
            
            $responses = [];
            foreach($rows as $row){

                //set the trashed_at column to null
                $row->{$this->trashcol} = NULL;

                //save trash column
                $responses[] = $row->save();
            }

            //check resonses
            if(count($responses) == 1){
                return $responses[0];
            }
            
            return $responses;
        }
        
        return FALSE;
    }
    
    /**
     * Forces a permanent delete
     * @param type $numRows
     * @return type
     */
    public function forceDelete($numRows){
        return parent::delete($numRows);
    }
}
