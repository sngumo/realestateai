<?php
namespace Jenga\MyProject\Users\Controllers\Traits;

use Jenga\App\Request\Input;
use Jenga\App\Views\Redirect;
use Jenga\App\Request\Cookie;
use Jenga\App\Views\Notifications;
use Jenga\App\Project\Core\Project;

/**
 * Handles all the cookie related functions of the user
 * @author stanley
 */
trait UserCookieTrait {
    
    /**
     * Get User data for cookie
     */
    public function getUserCookieData(){
        
        $this->view->disable();
        
        //get attributes
        echo json_encode([
            'user' => $this->user()->id,
            'login' => $this->user()->loggedin,
            'lastlogin' => $this->user()->lastlogin,
            'date' => date('l, jS  F Y H:i a', $this->user()->lastlogin)
        ]);
    }
    
    /**
     * Set the user cookie and update the db user record
     */
    public function setUserCookie($username = '', $password = ''){
        
        $this->view->disable();
        
        //get agency
        $agency = $this->call('Agency')->model->agents();
        
        //check user
        $user = $agency->where('username', $username)
                        ->where('password', md5($password))
                        ->first();  
        
        //if null
        if(is_null($user)){
            die(json_encode([
                'status' => false
            ]));
        }
        else if(!is_null($user) && $user->enabled === '0'){
            die(json_encode([
                'status' => false
            ]));
        }
        
        //set the user key
        $userkey = rand(0, 1000000);
        
        //set remember cookie data
        $keydata = json_encode([
            'username' => $username,
            'userkey' => $userkey,
            'remote_ip' => Project::getClientIPAddress()
        ]);
        
        $cfg = Project::getConfigs();
        $cookie = Cookie::set('user-remember', $keydata, time() + (86400 * 30), "/", $cfg->cookie_domain); // duration 30 days
        
        if($cookie){
            
            //save the key
            $user->userkey = $userkey;

            //set the remote address
            $user->remote_address = Project::getClientIPAddress();
            $user->save();

            if($user->hasNoErrors() == false){
                die([
                    'status' => 0,
                    'message' => Notifications::Alert(print_r($user->getLastError(), true),'success', true)
                ]);
            }
            
//            die(json_encode([
//                'status' => 1,
//                'remember' => 1,
//                'userkey' => $userkey,
//            ]));
            
            Redirect::to('/inline/loginbykey/'.$username.'/'.$userkey);
        }
    }
    
    /**
     * Set the user key separately
     * @param type $username
     * @param type $password
     * @return boolean
     */
    public function setUserKey($username = '', $password = ''){
        
        //get agency
        $agency = $this->call('Agency')->model->agents();
        
        //check user
        $user = $agency->where('username', $username)
                        ->where('password', md5($password))
                        ->first();  
        
        //if null
        if(is_null($user)){
            return false;
        }
        else if(!is_null($user) && $user->enabled === '0'){
            return false;
        }
        
        //set the user key
        $userkey = rand(0, 1000000);
        //save the key
        $user->userkey = $userkey;

        //set the remote address
        $user->remote_address = Project::getClientIPAddress();
        $user->save();
        
        if($user->hasNoErrors()){
            return $userkey;
        }
        else{
            
            print_r($user->getLastError());
            return false;
        }
    }
    
    /**
     * Login via the cookie user key
     * @param type $username
     * @param type $userkey
     */
    public function inlineLoginViaRememberKey($username, $userkey){
        
         //set the variables into the POST variable
        Input::set('usertype', 'smeowner', "POST");
        Input::set('username', $username, "POST");
        Input::set('userkey', $userkey, "POST");
        
        //login user
        $this->loginUser();
    }
}
