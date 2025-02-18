<?php
namespace Jenga\App\Models\Applies;

/**
 * Applies user level context functions
 * @author stanley
 */
trait UserContext {
    
    protected $hasContext = true;
    
    /**
     * Allows for the user context to be ignored
     * @var type 
     */
    protected $ignoreContext = false;
    
    /**
     * Register the context db table columns
     * @param type $columns
     * @return $this
     */
    public function registerContextColumns($columns){
        
        //set the context columns in the record
        $this->contextColumns = $columns;
        
        //set the active record 
        if(!is_null($this->record)){
            
            $this->record->hasContext = true;
            $this->record->contextColumns = $columns;
        }
        
        return $this;
    }
    
    /**
     * Returns the set context columns
     */
    public function getContext(){
        return $this->contextColumns;
    }
    
    /**
     * Ignores user defined context in model
     * @return $this
     */
    public function ignoreContext(){
        
        $this->ignoreContext = true;
        return $this;
    }
}
