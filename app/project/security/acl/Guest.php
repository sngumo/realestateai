<?php
namespace Jenga\App\Project\Security\Acl;

use Jenga\App\Project\Security\Permissions;

class Guest {

    public $name;
    public $alias;
    public $description;
    public $level;
    public $permissions;

    public function __construct(){

        $this->name = 'guest';
        $this->alias = 'guest';
        $this->description = '';
        $this->level = 0;
        
        $this->setPermissions();
        
        return $this;
    }
    
    /**
     * This function sets the level permissions into the user role
     */
    public function setPermissions(){        
        $this->permissions = new Permissions($this->level);
    }
    
    /**
     * This function will be called after the designated user has been denied access to a class method
     * 
     * @param type $element
     * @param type $controller
     * @param type $method
     */
    public function onDenied($element, $controller, $method){
    }
    
    /**
     * 
     * This function will be called after the designated user has been allowed access to a class method
     * 
     * @param type $element
     * @param type $controller
     * @param type $method
     */
    public function onAllowed($element, $controller, $method) {        
    }
}