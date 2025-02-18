<?php
namespace Jenga\App\Project\Security;

use Jenga\App\Project\Security\Acl\AclReader;

/**
 * This class handles the user defined permissions for any specific user
 *
 * @author Stanley Ngumo
 */
class UserPermissions {
    
    private $_perms;
    
    public function __construct($perms) {
        $this->_perms = json_decode($perms, TRUE);
    }
    
    /**
     * Evaluates the sent elements aginst the set user permissions
     * @param type $element
     * @param type $controller
     * @param type $method
     */
    public function evaluate($element, $controller, $method){
        
        $reader = new AclReader($element);        
        
        $perms = $this->_getPermsByElement($element);
        $indices = get_object_vars($reader->getActions());
        
        $ctrl = end(explode('\\', $controller));
        
        //loop through the actions
        foreach($indices as $index => $function){
            
            if($function == $ctrl.'@'.$method){
                $selected_index = $index;
            }
        }
        //dump($perms);
        if(!is_null($selected_index)){
            
            $key = $element.'_'.$selected_index.'_access';
            
            //just check for denied setting
            if(array_key_exists($key, $perms) && $perms[$key] == 'no')
                return FALSE;
        }
        
        return TRUE;
    }
    
    /**
     * Gets the specific permissions linked to the element
     * @param type $element
     */
    private function _getPermsByElement($element){
        
        $permkeys = array_keys($this->_perms);
        
        foreach($permkeys as $key){
            
            $elm = substr($key, 0, strlen($element));
            
            if($elm == $element){
                $list[$key] = $this->_perms[$key];
            }
        }
        
        return $list;
    }
    
    /**
     * Returns the parsed permissions
     * @return type
     */
    public function returnPerms() {
        return $this->_perms;
    }
    
    /**
     * 
     * @param type $element
     * @param type $action
     */
    public function evaluatePerm($element, $action) {
        
        $key = $element.'_'.$action.'_access';
            
        //just check for denied setting
        if(array_key_exists($key, $this->_perms[$element])){
            return $this->_perms[$element][$key];
        }
        
        return FALSE;
    }
}
