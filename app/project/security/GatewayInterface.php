<?php
namespace Jenga\App\Project\Security;

interface GatewayInterface {
    
    /**
     * This will be used to set the inital attributes for each user
     * @param array $attributes
     */
    public function setUserAttributes();
    
    /**
     * This is the element responsible for manipulating the system users
     * @param type $element
     */
    public function setAuthorizationElement();
}