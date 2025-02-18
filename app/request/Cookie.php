<?php
namespace Jenga\App\Request;

use Jenga\App\Core\App;
use Jenga\App\Project\Core\Project;

use Symfony\Component\HttpFoundation\Cookie as SymCookie;
use Symfony\Component\HttpFoundation\Response;

class Cookie {
    
    public static $request;
    public static $response;
    public static $instance; 
    public static $expire;

    public static function __callStatic($name, $arguments) {
        
        //initialise the dependecies
        self::_init();
        self::$instance = new static;
        
        if(method_exists(self::$instance, $name)){
            return call_user_func_array([self::$instance, $name], $arguments);
        }
    }
    
    /**
     * Initializes all the required objects for the cookie
     */
    private static function _init(){
        
        self::$request = App::get('_request');
        self::$response = new Response();
        self::$expire = time()+86400; //set the default time to 1 day
    }
    
    /**
     * Sets the cookie into the request
     * @param type $name
     * @param type $value
     * @param type $expire
     * @param type $path
     * @param type $domain
     * @param type $secure
     * @param type $httpOnly
     * @param type $raw
     * @param type $sameSite
     * @return type
     */
    public static function set($name, $value, $expire = '', $path = '/', $domain = null, $secure = false, $httpOnly = true) {
        
        self::_init();
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        
//        if($expire == ''){
//            $cookie = new SymCookie($name, $value, self::$expire, $path = '/', $domain = null, $secure = false, $httpOnly = true, $raw = false, $sameSite = null);
//        }
//        else{
//            $cookie = new SymCookie($name, $value, $expire, $path = '/', $domain = null, $secure = false, $httpOnly = true, $raw = false, $sameSite = null);
//        }
//        
//        self::$response->headers->setCookie($cookie);
//        self::$response->send();
//                
//        return self::$response;
        
    }
    
    /**
     * Gets the set cookie
     * @param type $name
     */
    public static function get($name) {
        
        $cookies = $_COOKIE;
        return $cookies[$name];
    }
    
    /**
     * Checks if a cookie has been set
     * 
     * @param type $name
     * @return boolean
     */
    public static function has($name){
        
        $cookies = $_COOKIE;
        
        if(is_null($cookies[$name])){
            return FALSE;
        }
        else{
            return TRUE;
        }
    }
    
    /**
     * Deletes a cookie
     * @param type $name
     */
    public static function delete($name) {
        
//        self::_init();
//        self::$response->headers->clearCookie($name);
//        
//        return self::$response->send();
        
        $cfg = Project::getConfigs();
        
        // set the expiration date to one hour ago
        return setcookie($name, "", time() - 3600, '/', $cfg->cookie_domain);
    }
    
    /**
     * Returns all the set cookies
     * @return type
     */
    public static function all() {
        
        self::_init();
        return self::$request->cookies->all();
    }
}
