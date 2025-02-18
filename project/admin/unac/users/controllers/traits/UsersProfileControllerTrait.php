<?php
namespace Jenga\MyProject\Users\Controllers\Traits;

/**
 * Handles the user profiles 
 * @author stanley
 */
trait UsersProfileControllerTrait {
    
    /**
     * Load the profile information
     * @param type $user
     */
    public function loadUserProfile($user){
        
        $id = $user->agenciesid;
        $agency = $this->call('Agency')->model->find($id);
        
        
        if(!$this->view->environment()->isMobile()){
            
            $this->set('user', $user);
            $this->set('agency', $agency->name);
            $this->view->setViewPanel('user-profile-menu');
        }
        else{
            
            echo $this->view->getPanelFile(
                ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'user-profile.php',
                [
                    'user' => $user,
                    'agency' => $agency->name
                ],
                true);
        }
    }
}
