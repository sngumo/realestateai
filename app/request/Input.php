<?php
namespace Jenga\App\Request;

use Jenga\App\Core\App;
use Jenga\App\Html\Form;
use Jenga\App\Request\Facade\Sanitize;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * This class processes and sanitize all input inserted into the system via $_GET and $_POST methods
 */
class Input {
    
    public static $sanitize;
    public static $request = null;
    public static $parameters;
    
    /**
     * Uses Symfony Request to process the sent URL
     * 
     * @param type $return_request
     * @return type
     */
    public static function load($return_request = false) {
        
        if(is_null(self::$request))
            self::$request = Request::createFromGlobals();
        
        if(self::$request->isMethod('GET'))
            self::$parameters = new ParameterBag(self::$request->query->all());
        elseif(self::$request->isMethod('POST'))
            self::$parameters = new ParameterBag(self::$request->request->all());
        
        //set the localhost to always be a trusted proxy
        if(!is_null(App::get('_config'))){
            Request::setTrustedProxies(App::get('_config')->trustedips);
        }
        
        //hack to go around the trailing slash oversight in Symfony Routing component
        if(substr(self::$request->getPathInfo(), -1) == '/'){
            
            $url = rtrim(self::$request->getPathInfo(),'/');
            
            //overwrite the previous request, weird but necessary be valid in Symfony
            self::$request = self::$request->duplicate(null, null, null, null, null, array('REQUEST_URI' => $url, null));
        }
        
        //check for /ajax url prefix
        if(strpos(self::$request->getPathInfo(),'/ajax') !== FALSE
                && strpos(self::$request->getPathInfo(),'/ajax') == 0){
           
            $url = str_replace('/ajax', '', self::$request->getPathInfo());
            
            //overwrite the previous request and replace with the new url without the /ajax prefix
            self::$request = self::$request->duplicate(null, null, null, null, null, array('REQUEST_URI' => $url, null));

            //add ajax attribute
            self::$request->attributes->set('_ajax_request',TRUE);
        }
        
        if($return_request == true)
            return self::$request;
    }


    public static function init( Sanitize $sanitize ){
        
        self::$sanitize = $sanitize;       
        return self;        
    }
    
    private static function clean($variable_array, $filter_rules = NULL){    
        
        App::call(  array(__NAMESPACE__.'\Input' , 'init') );
        
        if(is_array($variable_array)){
            
            //insert the filter rules for the Sanitize() class            
            if($filter_rules == NULL){                
                $filters = array('string' => 'sanitize_string');       
            }
            else{                
                $filters = $filter_rules;                
            }
            
            return self::$sanitize->filter($variable_array, $filters);  
        }
        else{            
            App::message('Please send an Input array', 'error');
        }
    }
    
    public static function get($variable='*'){
        
        self::load();        
        if($variable != '*'){
            return self::$request->query->get($variable);    
        }
        else{
            return self::$request->query->all();
        }
    }
    
    /**
     * Returns sent POST variables
     * 
     * @param type $variable
     * @param type  $_collectFormGarbage
     * @return type
     */
    public static function post($variable='*', $_collectFormGarbage = true){
        
        self::load();
        if($variable!='*'){
            $data =  self::$request->request->get($variable);    
        }
        else{
            $data = self::$request->request->all();
        }
        
        if($_collectFormGarbage){
            Form::garbageCollect($data);
        }
        
        return $data;
    }
    
    /**
     * Returns the request object
     * @return Request
     */
    public static function request(){
        
        self::load();
        return self::$request;
    }
    
    /**
     * Returns requested variable regardless of input method i.e. get, post
     * 
     * @param type $variable
     * @param type $filter_rules
     * @return type
     */
    public static function any($variable='*', $filter_rules = NULL){
        
        self::load();        
        if($variable != '*'){
            return self::$parameters->get($variable);   
        }
        else{
            return self::$parameters->all();   
        }
    }
    
    /**
     * Sets the variable based on current request method
     * @param type $varname
     * @param type $varvalue
     * @param type $method
     */
    public static function set($varname, $varvalue, $method = null){
        
        self::load();
        
        if(is_null($method)){
            $method = self::method();
        }
        
        switch ( strtoupper($method) ) {
            
            case 'GET':
                self::$request->query->set($varname, $varvalue);
                break;
            
            case 'POST':
                self::$request->request->set($varname, $varvalue);
                break;
        }
    }
    
    /**
     * returns method used in a request i.e. get, post, put etc
     * @return string 
     */
    public static function method(){
        
        return self::$request->getMethod();        
    }
    
    /**
     * Strips the curly brackets from route variables
     * 
     * @param string $element
     * @return string
     */
    public static function stripCurlyBrackets($value){
        
        $value = ltrim($value, '{');
        $value = rtrim($value, '}');
        
        return $value;
    }
    
    /**
     * Check to see if variable exists based on the request method
     * 
     * @param type $key
     * @param type $request_method
     * 
     * @return boolean
     */
    public static function has($key, $request_method = ''){
        
        if($request_method == '')
            $request_method = strtolower($_SERVER['REQUEST_METHOD']);
        else 
            $request_method = strtolower ($request_method);
        
        $inputkeys = self::{$request_method}();
        
        if(array_key_exists($key, $inputkeys)){  
            
            if(is_null($inputkeys[$key])){
                return FALSE;
            }
            
            return TRUE;
        }
        else            
            return FALSE;
    }
}