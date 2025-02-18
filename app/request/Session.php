<?php
namespace Jenga\App\Request;

use Jenga\App\Core\App;
use Jenga\App\Views\Notifications;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Security\User;
use Jenga\App\Request\Handlers\SessionHandler;

use Symfony\Component\HttpFoundation\Session\Session as SymSession;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Handles Session management for the Jenga Framework
 */
class Session {
    
    /**
     * @var SymSession
     */
    public static $_symsession;
    public static $sanitize;
    public static $filter_rules = NULL;
    
    private static $_keywords = array('event_token');
    private static $_flash;
    public static $instance; 
    
    public static function __callStatic($name, $arguments) {
        
        self::$instance = new static;
        
        if(method_exists(self::$instance, $name)){
            return call_user_func_array([self::$instance, $name], $arguments);
        }
    }
    
    /**
     * Initializes the session
     */
    public static function start() {
        
        //configure the core session settings
        self::configure(App::get('_config'));
        
        if(App::get('_config')->session_storage_type == 'database'){
            
            //replace the native Symfony handler with the custom Jenga Handler
            $sesshandler = App::get(SessionHandler::class);

            $storage = new NativeSessionStorage([], $sesshandler);
            self::init(new SymSession($storage));        
        }        
        else{
            self::init(new SymSession());
        }
        
        //start the session
        self::$_symsession->start();
        
        //session lifetime
        if(App::get('_config')->session_lifetime == ''){
            $session_lifetime = 0;
        }
        else{
            $session_lifetime = App::get('_config')->session_lifetime;
        }
        
        //check session start timestamp and set
        if(self::has('session_start') == FALSE){
            self::add('session_start', time());
        }
        elseif(self::has('session_start') && $session_lifetime != 0 &&  (time() - self::get('session_start')) > $session_lifetime){
            
            //destroy the session
            self::destroy();
            
            //set exipry message
            $notify = App::get(Notifications::class);
            $notify->setMessage('You session has expired. Please login again', 'notice');
        }
    }

    /**
     * Initialize session object
     * @param SymSession $symsession
     */
    public static function init(SymSession $symsession){         
        
        self::$_symsession = $symsession;
        self::$_flash = self::$_symsession->getFlashBag();
    }
    
    /**
     * Initializes the core session functions
     */
    public static function configure($config){
        
        //assign the settings fron the config class
        $session_lifetime = $config->session_lifetime; 
        
        if(property_exists($config, 'gc_probability'))
            $gc_probability = $config->gc_probability; 
        else
            $gc_probability = null;
        
        if(property_exists($config, 'gc_divisor'))
            $gc_divisor = $config->gc_divisor; 
        else
            $gc_divisor = null;
        
        // make sure session cookies never expire so that session lifetime
        // will depend only on the value of $session_lifetime
        ini_set('session.cookie_lifetime', 0);

        // if $session_lifetime is specified and is an integer number
        if ($session_lifetime != '' && is_integer($session_lifetime))

            // set the new value
            ini_set('session.gc_maxlifetime', (int)$session_lifetime);

        // if $gc_probability is specified and is an integer number
        if (!is_null($gc_probability) && is_integer($gc_probability)){

            // set the new value
            ini_set('session.gc_probability', $gc_probability);
        }
        
        // if $gc_divisor is specified and is an integer number
        if (!is_null($gc_divisor) && is_integer($gc_divisor)){

            // set the new value
            ini_set('session.gc_divisor', $gc_divisor);
        }
    }
    
    private static function _put($key, $value){        
        self::$_symsession->set($key,$value);
    }
    
    private static function _retrieve($key){   
        
        if(!is_null(self::$_symsession)){
            
            $chk = self::$_symsession->get($key);  

            //if null also check flash messages
            if(is_null($chk)){
                $chk = self::$_flash->get($key);
            }

            if(is_array($chk)){
                return $chk[0];
            }

            return $chk;
        }
    }

    /**
     * Return the current session id
     * @return type $sessionid
     */
    public static function id(){        
        return self::$_symsession->getId();
    }
    
    /**
     * Used to add new vaues into the session
     * 
     * @param type $key
     * @param type $value
     */
    public static function add($key, $value){
        
        if(!array_key_exists($key, self::$_keywords)){            
            self::_put($key, $value);
        }
        else{
            
            App::critical_error('The session key ['.$key.'] is already in use, please use another');
        }
    }
    
    /**
     * Used to add new vaues into the session
     * 
     * @param type $key
     * @param type $value
     */
    public static function set($key, $value){
        
        if(!array_key_exists($key, self::$_keywords)){            
            self::_put($key, $value);
        }
        else{
            
            App::critical_error('The session key ['.$key.'] is already in use, please use another');
        }
    }
    
    /**
     * Used to retrieve values from the current session
     * 
     * @param type $key
     * @return mixed session values
     */
    public static function get($key){        
        return self::_retrieve($key);
    }
    
    /**
     * Returns the flashbag
     * @return type
     */
    public static function getFlush(){
        return self::$_symsession->getFlashBag();
    }


    /**
     * Returns all the values saved in the session
     * 
     * @return type
     */
    public static function all($include_token = false){        
        
        $sessionvars = self::$_symsession->all();
        
        if(count($sessionvars) > 0){
            
            foreach($sessionvars as $key => $value){

                //remove security token and user class
                if($include_token === FALSE){
                    if(!(@unserialize($value) instanceof User) && $key !== 'token'){
                        $list[$key] = $value;
                    }
                }
                else{
                    $list = $allvalues;
                }
            }

            return $list;
        }
    }
    
    /**
     * Removes a specified key from the session
     * 
     * @param type $key
     */
    public static function delete($key){        
        return self::$_symsession->remove($key);
    }
    
    /**
     * Destroys the current session
     */
    public static function destroy(){        
        
        //clear the symfony session
        self::$_symsession->invalidate();
        
        //destroy the actual session
        session_destroy();
    }
    
    /**
     * Sets up the values for flash data
     * @param type $type
     * @param type $value
     */
    public static function flash($type, $value){        
       self::$_flash->add($type, $value);
    }
    
    /**
     * Converts the flash data into more permanent session information which is kept over many requests
     * @param type $key
     */
    public static function keep($key){
        
        $data = self::get($key);
        
        self::delete($key);
        self::add($key, $data);
    }
    
    /**
     * Checks if the entered session key exists
     * @param type $key
     * @return boolean
     */
    public static function has($key){
        
        $sessionkeys = self::all();
        
        if(!is_null($sessionkeys)){
            if(array_key_exists($key, $sessionkeys)){   

                //check for null session key
                if(!is_null($sessionkeys[$key]))
                    return TRUE;            
            }
        }
        else{    

            //also check the flash massages
            if(self::$_flash->has($key)){
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    
    
    /**
     * Returns the set user security token
     * @return boolean
     */
    public static function getSecurityToken() {
        
        $token = Session::get('token');
        
        if(is_null($token)){
            return FALSE;
        }
        
        return $token;
    }
}