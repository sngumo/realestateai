<?php
namespace Jenga\App\Database\Entities;

use Jenga\App\Models\Interfaces\SchemaInterface;

/**
 * Holds the miscellaneous methods used in the ActiveRecord and Resolver
 * @author stanley
 */
trait AuxillaryFunctionsTrait {
    
    /**
     * Calculates difference of new and previous columns
     * @param type $exec
     * @param SchemaInterface $schema
     * @return type
     */
    public function getUpdateDiff($exec, SchemaInterface $schema){
        
        $original = $exec['data'][$schema->getCreationTime()];
        $created_at = $schema->getCreationTime();
        $updated_at = $schema->getUpdateTime();
        
        //remove aux data
        $this->_removeAuxData($exec);
        
        $modified = $exec;
        
        //loop modified array and compare
        $updates = [];
        foreach($modified as $key => $value){
            
            if($value !== $original[$key]){
                $updates[$key] = $value;
            }
        }
        
        return [$original, $created_at, $updated_at, $updates];
    }
    
    /**
     * Aligns the schema data
     * @param type $exec
     * @param type $schema
     * @return type
     */
    public function getInsertData($exec, SchemaInterface $schema){
        
        $primarykey = $this->getPrimaryKey($schema->table);
        $updated_at = $schema->getUpdateTime();
        
        //remove aux data
        unset($exec[$primarykey]);
        $this->_removeAuxData($exec);
        
        //reassign ActiveRecord
        $data[$updated_at] = $exec;
        
        return [$data, $updated_at];
    }
    
    /**
     * Filters auxiliary data from schema
     * @param type $data
     */
    private function _removeAuxData(&$data) {
        
        $keywords = ['data','_created_at','_updated_at','is_joined','_join_type','_join_condition',
                     '_join_direction','_join_data','_join_update','_join_tables'];
        
        foreach($keywords as $keyword){            
            if(array_key_exists($keyword, $data))
                unset($data[$keyword]);
        }
    }
    
}
