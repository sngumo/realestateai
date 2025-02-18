<?php
namespace Jenga\App\Project\Security;

use Jenga\App\Models\ORM;
use Jenga\App\Request\Session;
use Jenga\App\Project\Security\Traits\Authentication;
use Jenga\App\Project\Security\GatewayInterface;

class Gateway implements GatewayInterface {
    
    use Authentication;
    
    public function setUserAttributes() {             
        $this->user->setAttributes([]);
    }
    
    public function setAuthorizationElement() {
        
        $this->auth_element = '';
    }
    
    /**
     * Checks if the user is a guest or has been logged in
     * @return boolean
     */
    public static function isLogged(){
        
        if(is_int(self::user()->loggedin))
            return TRUE;
        
        return FALSE;
    }
}