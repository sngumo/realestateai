<?php
namespace Jenga\App\Project\Security\Acl;

use Jenga\App\Core\App;

/**
 * This class takes the copiled ACL values and parses them for use within a given project DIRECTLY 
 * as opposed to analyzing them through the user
 *
 * @author Stanley Ngumo
 */
class AclReader {
    
    public $current_element = null;
    public $acl;
    
    public function __construct($element = null) {
        
        $arguments = ['--outputto' => 'inline'];
        
        //add the current element
        if(!is_null($element)){
            
            $this->current_element = $element;
            $arguments['--element'] = $element;
        }
        
        $acl = App::terminal('acl:compile', $arguments);
        $this->acl = json_decode($acl);
        
        return $this;
    }    
    
    /**
     * Returns the base ACL of the current element
     */
    public function getBase(){
        
        $base = null;
        
        if(!is_null($this->current_element) && !is_null($this->acl)){
            
            if(property_exists($this->acl, $this->current_element)){
                
                $base = $this->acl->{$this->current_element}->base;
                $gateway = App::get('gateway');

                //if is null set level to zero and role to Guest
                if(is_null($base)){

                   $base = new \stdClass(); 
                   $base->level = 0;
                   $base->role = 'Guest';
                }
                elseif(!property_exists($base, 'level')){

                    //get level if only the role is assigned
                    $role = $gateway->getRoleByAlias($base->role);                
                    $base->level = $role->level;
                }
                elseif(!property_exists($base, 'role')){

                    //get the roles matching the set base level
                    $role = $gateway->getRoleByLevel($base->level);                   
                    $base->role = $role->alias;
                }
            }
        }
        
        return $base;
    }
    
    /**
     * Returns the element ACL actions
     */
    public function getActions(){        
        if(!is_null($this->acl) && property_exists($this->acl, $this->current_element)){
            
            if(property_exists($this->acl->{$this->current_element}, 'actions')){
                return $this->acl->{$this->current_element}->actions;
            }
        }
    }
    
    /**
     * Returns the element ACL roles
     */
    public function getRoles(){
        if(!is_null($this->acl) && property_exists($this->acl, $this->current_element)){
            
            if(property_exists($this->acl->{$this->current_element}, 'role')){
                return $this->acl->{$this->current_element}->role;
            }
        }
    }
    
    /**
     * Returns the element ACL aliases
     */
    public function getAliases(){
        if(!is_null($this->acl) && property_exists($this->acl, $this->current_element)){
            
            if(property_exists($this->acl->{$this->current_element}, 'alias')){
                return $this->acl->{$this->current_element}->alias;
            }
        }
    }
}
