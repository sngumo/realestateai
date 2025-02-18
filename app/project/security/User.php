<?php
namespace Jenga\App\Project\Security;

use Jenga\App\Request\Url;
use Jenga\App\Views\Redirect;
use Jenga\App\Request\Session;
use Jenga\App\Project\Core\Project;

class User {
    
    public $attributes = [];
    public $role;
    
    /**
     * This magic function will allow to set any number of getter and setter functions
     * linked the user
     * @example $this->user->getUsername() returns the entered username by computing from 
     *          the function name and the arguments sent
     * @param type $name
     * @param type $arguments
     * @return type
     */
    public function __call($name, $arguments = null) {
        
        //all the get functions will be processed here
        if(strpos($name, 'get') === 0){
            
            $getvar = strtolower(explode('get', $name)[1]);
            
            if(!is_null($arguments)){
                
                if(count($arguments) == 1){
                    return $this->{$getvar}[$arguments[0]];
                }
            }
            else{
                return $this->{$getvar};
            }
        }
        //all the set functions will be processed here
        elseif(strpos($name, 'set') === 0){
            
            $setvar = strtolower(explode('set', $name)[1]);
            
            if(count($arguments) == 1){
                return $this->setAttribute($setvar, $arguments[0]);
            }
            else{
                return $this->setAttribute($setvar, $arguments);
            }
            
            $this->updateUserSession();
        }
    }
    
    /**
     * Sets user defined attributes into an array
     * @param array $attributes
     */
    public function setAttributes(array $attributes){  
        
        $this->attributes = $attributes;
        
        foreach($attributes as $attr){    
            
            if(property_exists($this, $attr) == FALSE){
                $this->{$attr} = null;
            }
        }
        
        $this->updateUserSession();
    }
    
    /**
     * Sets the user attribute one by one
     * @param type $name
     * @param type $value
     */
    public function setAttribute($name, $value) {
        
        $this->{$name} = $value;
        $this->updateUserSession();
    }
    
    /**
     * Assigns the various user attribues based on sent values
     * @param array $attr_values
     */
    public function mapAttributes(array $attr_values){
        
        foreach($attr_values as $attribute => $value){
            
            $attr = strtolower($attribute);
            $this->setAttribute($attr, $value);
        }
    }
    
    /**
     * Returns the user attributes
     * @return type
     */
    public function getAttributes(){
        return $this->attributes;
    }
    
    /**
     * Attach role and calculated permissions to user
     * @param type $role
     */
    public function attachRole($role){
        
        $this->role = $role;
        $this->updateUserSession();
    }
    
    /**
     * Add new user permissions and reassigns permissions
     * @param type $perms
     */
    public function addPermissions($perms) {
        $this->role->permissions->addUserPermissions($perms);
        $this->updateUserSession();
    }
    
    /**
     * Get calculated permissions for user
     * @return type
     */
    public function getPermissions(){       
        return $this->role->permissions->calculatedPermissions();
    }
    
    public function updateUserSession(){ 
        
        $token = Session::getSecurityToken();
        if(!is_null($token) && $token !== false){
            
            //update the user object
            $userupdate = Session::add('user_'.$token,serialize($this));
            
            return $userupdate;
        }
    }
    
     /**
     * Commits all the changes to the user session to the request gateway user instance
     */
    public function commit(){

        //set user into session
        $this->updateUserSession();

        //update the gateway
        $gateway = unserialize(Session::get('gateway'));
        $gateway->user = Project::user();

        //update the session gateway
        Session::add('gateway', serialize($gateway));
    }
    
    /**
     * An extraction of the Permissions can() method
     * @param type $action
     * @param type $element
     * @return type
     */
    public function can($action, $element, $ctrl = 'primary', $debug = TRUE){        
        return $this->role->permissions->can($action, $element, $ctrl, $debug);
    }
    
    /**
     * Checks the role alias to verify user role
     * 
     * @param type $alias
     * @return boolean TRUE or FALSE
     */
    public function is($alias){
        return $this->role->permissions->is($alias);
    }
    
    /**
     * An extraction of the Permissions canExecute() method
     * @param type $action
     * @param type $element
     * @return type
     */
    public function canExecute($element, $controller, $method = 'index'){ 
        
        $current = Url::current();
        
        if(!is_null($this->role->permissions) && method_exists($this->role->permissions, 'canExecute')){
            return $this->role->permissions->canExecute($element, $controller, $method);
        }
        else{            
            //destroy session for it to be recreated
            Session::destroy();
            Redirect::to ($current);
        }
    }
    
    /**
     * Update the current user definition
     * @return type
     */
    public function __destruct(){           
        $this->updateUserSession();
    }
}