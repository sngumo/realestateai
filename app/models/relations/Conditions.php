<?php
namespace Jenga\App\Models\Relations;

/**
 * Holds the relations condition methods
 *
 * @author stanley
 */
class Conditions {
    
    /**
     * Parses the relation conditions
     * @param type $condition
     * @return type
     */
    public static function parse($condition){
        
        $new = [];
        
        $main = explode('.', $condition[0]);
        $new['main']['table'] = $main[0];
        $new['main']['col'] = end($main);
        
        $foreign = explode('.', $condition[2]);
        $new['foreign']['table'] = $foreign[0];
        $new['foreign']['col'] = end($foreign);
        
        return $new;
    }
    
    /**
     * Parses the many to many condition
     * 
     * @param type $section Options: main, pivot or foreign
     * @param type $condition
     */
    public static function parseManyToMany($section = null, $condition = null){
        
        $params = [];
        
        //if section is null, pick the pivot
        if($section == 'pivot' || $section == 'main'){
            $table = array_keys($condition)[0];
        }
        elseif($section == 'foreign'){
            $table = array_keys($condition)[1];
        }
        
        //get the foreign params
        $foreign = explode('.',$condition[$table]['left']);
        
        $params[$section == 'main' ? 'main' : 'foreign']['table'] = $foreign[0];
        $params[$section == 'main' ? 'main' : 'foreign']['col'] = $foreign[1];
        
        //get the pivot params
        $pivot = explode('.',$condition[$table]['right']);
        
        $params['pivot']['table'] = $pivot[0];
        $params['pivot']['col'] = $pivot[1];
        
        return $params;
    }
}
