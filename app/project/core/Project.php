<?php
namespace Jenga\App\Project\Core;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Helpers\Help;
use Jenga\App\Request\Input;
use Jenga\App\Request\Session;
use Jenga\App\Project\Security\User;
use Jenga\App\Project\Core\ElementsLoader;
use Jenga\App\Project\EventsHandler\Events;
use Jenga\App\Project\Elements\XmlElements;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;

use Jenga\MyProject\Config;
use Jenga\MyProject\Elements;
use Jenga\MyProject\Users\Acl\Gateway;

class Project implements ArgumentResolverInterface {
    
    public static $instance = null;
    public static $elements;
    
    public $event;    
    public $partials;    
    public $defaults;        
    public $view;
    public $resources;
    public $controller; 
    public $main_method = '';
    public $shell;
    public $user;
    
    /**
     * 
     * @var Gateway
     */
    public $gateway;
    public $current_route;

    public static $olokun;
    public $mode = 'startup';
    private $_myattributes = []; //these are the attributes directly linked to the loaded element
    
    protected $arguments;
    
    /**
     * Start the Project section
     */
    public function boot(){

        //build the project elements
        $this->build();
        
        if(File::exists(APP_PROJECT .DS. 'elements.php')){
                
            self::$elements = unserialize(File::get(APP_PROJECT .DS. 'elements.php'));  
            
            if(count(self::$elements) >= 1){
                
                //set development phase
                if(App::get('_config')->development_environment)
                    $this->mode = 'development';
                else
                    $this->mode = 'production';
                
                $loader = new ElementsLoader(self::$elements);
                spl_autoload_register(array($loader, 'autoLoadElements')); 

                //assign the default element
                $this->_defaultElement();

                //assign the template base url
                $templates = self::getTemplates();
                
                if(!is_null($templates) && defined('RELATIVE_PROJECT_PATH')){
                    define('TEMPLATE_URL', RELATIVE_ROOT .'/'. $templates['path']);
                }
            }
        }
    }
    
    /**
     * Sets the default project element ,MVC and routing defaults
     */
    private function _defaultElement(){
        
        $count = 0;
        $elementNames = array_keys(self::$elements);
        
        foreach(self::$elements as $element){
            
            if(array_key_exists('default',$element)){
                $this->_setElementAsDefault($element, $elementNames, $count);
                break;
            }
            else{                
                $this->defaults['element'] = NULL;
            }
            
            $count++;
        }
        
        //check if default element is set 
        if(is_null($this->defaults['element'])){
            
            //set the first element as default
            $name = $elementNames[0];
            $count = 0;
            
            //check if its the public element i.e. templates
            if($name == 'public'){
                
                //if so pick the next one
                if(count($elementNames) > 1){
                    $name = $elementNames[1];
                    $count = 1;
                }
            }
            
            if($name != 'public'){
                $this->_setElementAsDefault(self::$elements[$name], $elementNames, $count);
            }
        }
    }
    
    /**
     * Sets the sent element as the default element
     */
    private function _setElementAsDefault($element, $names, $count){
        
        $this->defaults['element'] = $names[ $count ];                
        $defaultelement = ucfirst($this->defaults['element']);

        //set default model
        if(in_array($defaultelement, array_keys($element['models']))){
            $this->defaults['model'] = $defaultelement;
        }
        elseif(in_array($defaultelement.'Model', array_keys($element['models']))){
            $this->defaults['model'] = $defaultelement.'Model';
        }

        //set default controller
        if(in_array($defaultelement, array_keys($element['controllers']))){
            $this->defaults['controller'] = $defaultelement;
        }
        elseif(in_array($defaultelement.'Controller', array_keys($element['controllers']))){
            $this->defaults['controller'] = $defaultelement.'Controller';
        }

        //set the default view
        if(in_array($defaultelement, array_keys($element['views']))){
            $this->defaults['view'] = $defaultelement;
        }
        elseif(in_array($defaultelement.'View', array_keys($element['views']))){
            $this->defaults['view'] = $defaultelement.'View';
        }
    }
    
    /**
     * Processes the elements to generate a full namespace for the run command 
     * according to Jenga\MyProject\{element}\{-- folder}\{class} Namespace rule
     * -- if present, if only file omit
     * 
     * @param string $class
     * @return string Fully Qualified Namespaced class name
     */
    public static function generateNamespacedClass($engine, $mvc = 'controllers'){
        
        $nsClass = 'Jenga\MyProject\\';
        $elements = self::$elements; 
        $element_name = null;
        
        $mvc_name = $engine;            
        $elementkeys = array_keys($elements);
        
        foreach($elementkeys as $key){
            
            if(array_key_exists($key, $elements)){

                //check for template element and skip
                if(array_key_exists('function', $elements[$key])){                    
                    if($elements[$key]['function'] == 'templates')
                        continue;
                }
                
                if(array_key_exists($engine, $elements[$key][$mvc])){
                    $element_name = $elements[$key]['name'];
                    break;
                }
            }
        }
        
        if(!is_null($element_name)){
            
            $mvc_array = $elements[$element_name][$mvc][$mvc_name];
            
            if(count($mvc_array) == '1')
                $nsClass .= ucfirst ($element_name) .'\\'. ucfirst ($mvc_name);
            else
                $nsClass .= ucfirst ($element_name) .'\\'. ucfirst ($mvc_array['folder']) .'\\'. ucfirst ($mvc_name);
        }
        else{
            $nsClass = 'not_found';
        }
        
        return $nsClass;
    }
    
    /**
     * Process elements based on the controller and return the full element
     * 
     * @param type $controller
     * @return type
     */
    public static function _getFullElementByController($controller){
        
        $elements = self::elements();
        $elementkeys = array_keys($elements);
        
        foreach($elementkeys as $key){
            
            //check for template element and skip
            if(array_key_exists('function', self::$elements[$key])){                    
                if(self::$elements[$key]['function'] == 'templates')
                    continue;
            }
            
            if(!is_null($elements[$key]['controllers'])){
                
                if(array_key_exists($controller, $elements[$key]['controllers'])){

                    return $elements[$key];
                }
            }
        }
        
        return NULL;
    }
    
    private function _createGateway(Request $request){

        //get the Auth handler and inject the user instance
        $this->gateway = App::$shell->call( 

            function($authorize, $request){
                return $authorize->init($request);
            },
            ['authorize'=> \DI\get('auth'), 'request' => $request]       
        );
   }
    
    /**
     * Initializes the full MVC element based on the Symfony Request
     * @param Request $request
     */
    public function init(Request $request = null, \JRequestEvent &$event = null){
        
        //initialize user defined uthentication
        if(Session::getSecurityToken() === FALSE || Session::has('gateway') === FALSE){      
            $this->_createGateway($request);
        }
        else{
            $this->gateway = unserialize(Session::get('gateway'));
        }
        
        //assign auth element
        $this->gateway->setAuthorizationElement();
        
        $controller = $request->attributes->get('_controller');
        $route = $request->get('_route');
        
        $this->current_route = $route;
        
        $this->resources = App::get('_resources');
        $this->resources->setCurrentRoute($route);
        
        //check for ajax request attribute
        if(!is_null($request->attributes->get('_ajax_request'))){
            App::set('_ajax_request',TRUE);
        }
        
        //convert the route parameters
        $this->_parseRouteParams($request);
        
        //validate the request or die
//        $validate_status = $this->gateway->validateRequest($request);
//        if($validate_status === FALSE){
//            die(json_encode([
//                'status' => 0,
//                'message' => 'Invalid Request'
//            ]));
//        }
//        elseif(is_array($validate_status)){
//            die(json_encode($validate_status));
//        }
        
       //set the entire Authorization mechanism into the shell        
        App::set('gateway',  $this->gateway);
        
        //overwrite the gateway in the Session
        Session::add('gateway', serialize($this->gateway));
        
        //commit the user session
        self::user()->commit();
        
        //start assigning the MVC components
        if($controller !== '_dynamic' && $controller !== '_static'){
            
            $request = explode('::',$controller);
            
            if(strpos($controller, 'Jenga\App') !== 0){
                
                $rq = explode('\\', $request[0]);
                $ctrl = end($rq);
                
                $element = self::_getFullElementByController($ctrl); 
                $mvc = $this->makeMvcEngine($element, $ctrl);
                
                //set the primary route for the route
                $this->resources->setMainPanel($mvc['controller'], $request[1]);

                //assign controller
                $this->controller = $mvc['controller'];
                $this->main_method = $request[1];
                
                //set the primary element
                App::$shell->set('primaryelement', $element['name']);            
                $this->assignMVC($mvc['controller'], $mvc['model'], $mvc['view'], $request[1]);                
            }
            else{
                
                //process the ajax controller
                $this->controller = $request[0];
                $this->main_method = $request[1];
            }
        }
        /**
         * This is for pages which have been linked directly from the route with direct absolute addresses
         */
        elseif($controller == '_static'){
            
            /**
             * Fire the on:request and before route events
             * 
             * This is done here since the static section bypasses the main/primary controller
             * and only renders any controllers from the secondary panels
             */
             Events::fireOnRoute($this->current_route, KernelEvents::CONTROLLER); 
            
            $engine = $this->resources->returnStaticRouteEngine();
            
            App::set('__view',\DI\object('Jenga\App\Views\View')->constructor($this->resources)); 
            App::set('primaryelement', 'static');
            
            $view = App::get('__view');
            $view->renderStaticPage($engine);
            
            $event->setResponse($view->render());
        }
        /**
         * This is for routes which are assigned dynamically from the element placeholder {element} 
         * being used in the route as opposed to direct element naming. 
         * 
         * This routing captures a wide scope of routes thus allowing for easy routing of simpler applications 
         * or for initial application scaffolding of routes
         */
        elseif($controller == '_dynamic'){
            
            if(!is_null($request->get('element'))){
                
                $element_name = $request->get('element');                
                $mvc = $this->makeMvcEngine(self::$elements[$element_name]);
                
                $controller = ($request->get('controller') == null ? $mvc['controller'] : $request->get('controller'));
                $action = ($request->get('action') == null ? 'index' : $request->get('action'));
                
                //set the primary route for the _dynamic route
                $this->resources->setMainPanel($controller, $action);
                
                $this->controller = $mvc['controller'];
                $this->main_method = $action;
                
                App::$shell->set('primaryelement', $element_name);            
                $this->assignMVC($mvc['controller'], $mvc['model'], $mvc['view'], $action);
                
                //set the controller
                $request->attributes->set('_controller', $mvc['controller'].'::'.$action);
            }
            else{
                App::critical_error('Please add the required element using the {element} place holder in the route');
            }
        }
    }
    
    /**
     * Assigns the element mvc classes into the Application shell
     * @param type $controller
     * @param type $model
     * @param type $view
     */
    public function assignMVC($controller, $model, $view, $method = null, $params = null){
        
        //the core MVC components
        App::set( '__controller', $controller);
        
        //model
        $shell = App::$shell;
        $dbal = self::getDatabaseConnector();  
        
        App::set('__model', \DI\factory(function() use($model, $dbal, $shell){
            
            //initialize the __map method to inject the connector and active record objects
            $modelobj = $shell->get($model);        
            return call_user_func_array([$modelobj,'__map'], [$shell->get($dbal)]);
        }));
        
        //view
        App::set( '__view', \DI\object($view)->constructor($this->resources) );
        
        //add the primary element method into the App shell
        if(is_null($this->main_method)){
            App::set('method', $method);
        }
        else{
            App::set('method','_main');
        }
        
        //add the element parameters into the App shell
        App::set('params', $params);
    }
    
    /**
     * Get database connector class
     */
    public static function getDatabaseConnector() {
        
        $connector = App::get('connector');
        
        //load the connections file
        $conns = require $connector;
        $dbal = ucfirst(strtolower($conns['dbal']));
        
        $class = 'Jenga\App\Database\Systems\\'.$dbal.'\Connections\DatabaseConnector';
        
        return $class;
    }
    
    /**
     * Generates fully namespaced mvc classes from element
     * 
     * @param type $element
     */
    public function makeMvcEngine($element, $controller = null) {
        
        $ctrposition = false;
        
        if(!is_null($element)){
            
            $elm  = ucfirst($element['name']);
            
            $ckey = array_keys($element['controllers']);
            $mkey = array_keys($element['models']);
            $vkey = array_keys($element['views']);

            //set correct controller
            if(!is_null($controller)){

                if(in_array($controller, $ckey)){
                    
                    $ctrposition = array_search($controller, $ckey);
                    $controllername = $controller;
                }
                else{
                    
                    if(in_array($elm, $ckey))
                        $controllername = $elm;
                    
                    elseif(in_array($elm.'Controller', $ckey))
                        $controllername = $elm.'Controller';
                }
            }
            else{
                
                if(in_array($elm, $ckey)){
                    $controllername = $elm;
                }
                elseif(in_array($elm.'Controller', $ckey)){
                    $controllername = $elm.'Controller';
                }
            }
            
            //set correct model                
            if(in_array($elm, $mkey)){
                $modelname = $elm;
            }
            elseif(in_array($elm.'Model', $mkey)){
                $modelname = $elm.'Model';
            }
            else{
                $modelname = $mkey[0];
            }
            
            //set correct view
            if(in_array($elm, $vkey)){
                $viewname = $elm;
            }
            elseif(in_array($elm.'View', $vkey)){
                $viewname = $elm.'View';
            }
            else{
                $viewname = $vkey[0];
            }

            $mvc['controller'] = self::generateNamespacedClass($controllername, 'controllers');
            $mvc['model'] = self::generateNamespacedClass($modelname, 'models');
            $mvc['view'] = self::generateNamespacedClass($viewname, 'views');
            
            return $mvc;
        }
    }
    
    /**
     * Creates the MVC components for the on demand calling of elements
     * @param type $element
     * @param type $controller
     * @return string
     */
    public function makePartialMvcEngine($element, $controller){
        
        $nsClass = 'Jenga\MyProject\\';
        
        //create the element name
        $nsClass .= ucfirst($element['name']);
        
        //check if controller has 'Controller' appended
        if(strpos($controller, 'Controller')){
            
            $cname = str_replace('Controller', '', $controller);
            $model = $cname.'Model';
            $view = $cname.'View';
        }
        else{
            
            $cname = $controller; 
            $model = $cname;
            $view = $cname;
        }
        
        //create the controller
        if(count($element['controllers'][$controller]) == 1)
            $engine['controller'] = $nsClass .'\\'. $controller;
        else
            $engine['controller'] = $nsClass .'\\'. ucfirst($element['controllers'][$controller]['folder']) .'\\'. $controller;
        
        //create the model
        if(count($element['models'][$model]) == 1)
            $engine['model'] = $nsClass .'\\'. $model;
        else
            $engine['model'] = $nsClass .'\\'. ucfirst($element['models'][$model]['folder']) .'\\'. $model;
        
        //create the view
        if(count($element['views'][$view]) == 1)
            $engine['view'] = $nsClass .'\\'. $view;
        else
            $engine['view'] = $nsClass .'\\'. ucfirst($element['views'][$view]['folder']) .'\\'. $view;
        
        return $engine;
    }
    
    /**
     * Converts the route parameters into the sent standard request method 
     * i.e. GET, POST, PUT etc
     */
    private function _parseRouteParams(Request $request){
        
        //get all variables within the request method
        $route_params = $request->attributes->all()['_route_params'];
        
        if(count($route_params) >= 1){
            
            foreach($route_params as $param_name => $param_value){

                //filter the internal Jenga variables
                if(strpos($param_name, '_') !== 0){

                    //set the variable into the standard request methods
                    if(!Input::has($param_name)){
                        
                        Input::set($param_name, $param_value);
                        $this->arguments[$param_name] = $param_value;
                    }
                }
            }
        }
    }
    
    /**
     * Assigns each element's attributes into a single array
     * @param type $element
     */
    private function _assignElementAttrs($element) {
        
        $avoidkeys = ['models','controllers','views','schema'];
        
        if(count($element) > 0){
            
            foreach($element as $key=>$value){
                if(!in_array($key, $avoidkeys)){
                    $this->_myattributes[$key] = $value;
                }
            }
        }
    }
    
    /**
     * Returns the attributes for each element
     * @return type
     */
    public function attrs($element = 'primary'){
        
        if($element == 'primary')
            $pri_element = self::elements()[App::get('primaryelement')];
        else
            $pri_element = self::elements()[$element];
        
        $this->_assignElementAttrs($pri_element);
        return $this->_myattributes;
    }
    
    /**
     * Returns current registered user
     * @return User
     */
    public static function user(){
        
        $token = Session::getSecurityToken();
        if($token !== FALSE){            
            
            $user = unserialize(Session::get('user_'.$token));            
            if($user instanceof User){
                return $user;
            }
        }
        
        return NULL;
    }
    
    /**
     * Checks if its the console environment
     * @return type
     */
    public static function isCli(){
        return is_null(self::user());
    }
    
    /**
     * Processes the project XML and integrates the elements into the framework
     */
    public static function build(){
        
        //clear the XML file cached in memory 
        clearstatcache();
            
        if(!is_null(App::get('_config')) && App::get('_config')->development_environment){  
            
            $xml = App::$shell->get(XmlElements::class);   
            
            if($xml->loadXml('map.xml', PROJECT_PATH)){
                
                if(App::$config->development_environment)
                   self::elements();
                else
                   App::message ('Project elements successfully built', 'valid');
            }
        }        
    }
    
    /**
     * Returns the project elements if retrieved from the console
     */
    public function consoleBuild() {
        
        self::$elements = unserialize(File::get(APP_PROJECT .DS. 'elements.php'));
        return self::$elements;
    }
    
    /**
     * Returns instance of own class
     * @return type
     */
    public static function instance() {
        
        if(is_null(self::$instance)) {            
            self::$instance = new Project();                
        }
        
        return self::$instance;	
    }
    
     /**
     * Dumps the project elements
     * @return type
     */
    public static function elements($mvc = null, $type = null, array $filter = null){
        
        $element_name = null;
        
        if(!is_null(self::$elements)){
            $elementkeys = array_keys(self::$elements);
        }
        
        if(is_null($mvc) && is_null($type)){
            
            if(is_null($filter)){
                return self::$elements;
            }
            else{
                
                //get the filter keys
                $filterkeys = array_keys($filter);
                
                foreach($elementkeys as $ekey){
                    
                    //check filter
                    foreach($filterkeys as $filkey){
                        
                        //if filter key is present
                        if(!is_null(self::$elements[$ekey])){
                            
                            if(array_key_exists($filkey, self::$elements[$ekey])){

                                //if filter values align
                                if($filter[$filkey] == self::$elements[$ekey][$filkey]){

                                   //remove from list
                                   unset(self::$elements[$ekey]);
                                }
                            }
                        }
                    }
                }
                
                return self::$elements;
            }
        }
        elseif(!is_null($mvc)){
            
            if(is_null($type))
                $type = 'controllers';
            
            //if fully namespaced
            if(strpos($mvc, '\\') !== false){
                $m = explode('\\', $mvc);
                $mvc = end($m);
            }

            foreach($elementkeys as $key){

                //check for template element and skip
                if(array_key_exists('function', self::$elements[$key])){                    
                    if(self::$elements[$key]['function'] == 'templates')
                        continue;
                }

                if(!is_null(self::$elements[$key][$type])){

                    if(array_key_exists($mvc, self::$elements[$key][$type])){
                        $element_name = self::$elements[$key]['name'];
                        break;
                    }
                }
            }
            
            if(!is_null($element_name)){
                return self::$elements[$element_name];
            }
        }
    }
    
    /**
     * Shows pretty arrays
     * 
     * @param type $var
     */
     private static function _debug($var = false) {
         
        echo "\n<pre style=\"background: #fff; color:#000; font-size: 14px;\">\n";

        $var = print_r($var, true);
        echo $var . "\n</pre>\n";
    }
    
    /**
     * Returns the project templates
     * @return array 
     */
    public static function getTemplates(){
        
        $keys = array_keys(self::$elements);
        
        foreach($keys as $name){
            
            if(array_key_exists('function', self::$elements[$name])){
                
                if(self::$elements[$name]['function'] == 'templates')
                    return self::$elements[$name];
            }
        }
        
        return NULL;
    }
    
    /**
     * If the classname is called as a string, it inserts the class instance
     * 
     * @return type
     */
    public function __toString(){        
        return self::instance();
        
    }
    
    /**
     * Returns the current class via late binding
     * @return type
     */
    public function getController(){
        return $this->controller;
    }
    
    /**
     * Calls any element within the project
     * @param type $element
     */
    public static function call($element, $terminal = false) {
        
        //parse schema
        if(strpos($element, '/') === FALSE){
            $engine = ucfirst($element);
            $engine = $engine.'/'.$engine.'Controller';
        }
        else{
            $engine = $element;
        }
        
        return Elements::call($engine, $terminal);
    }
    
    public function getArguments(Request $request, $cntrl) {
        
        $ctrl = new \ReflectionClass($cntrl);
        $controller = $ctrl->newInstanceWithoutConstructor();
        
        $resolver = App::get('Symfony\Component\HttpKernel\Controller\ArgumentResolver');
        return $resolver->getArguments($request, $controller);
    }
    
    /**
     * Helper function which returns current route
     * @return type
     */
    public function getCurrentRoute() {        
        return App::get('_resources')->getCurrentRoute();
    }
    
    /**
     * Helper function which returns project configurations
     * @return  Jenga\MyProject\Config
     */
    public static function getConfigs() {
        return App::get('_config');
    }
    
    /**
     * Helper function returns Symfony Request Object;
     * @return Request
     */
    public static function getRequest(){
        
        $gateway = self::getGateway();
        return $gateway->getValidator()->request;
    }
    
    /**
     * Returns the user / request validatror gateway object
     * @return  Gateway
     */
    public static function getGateway(){
        return unserialize(Session::get('gateway'));
    }
    
    public static function getClientIPAddress() {  
        
        //whether ip is from the share internet  
        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {  
            $ip = $_SERVER['HTTP_CLIENT_IP'];  
        }  
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {  
            //whether ip is from the proxy  
             $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];  
        }  
        else{  
            //whether ip is from the remote address  
             $ip = $_SERVER['REMOTE_ADDR'];  
        }
        
        return $ip;  
    } 
    
    /**
     * Loads the different panels called from the main template
     * 
     * @param type $position
     */
    public function loadPanel($position, $panelargs = array()){
        
        if(is_null($this->resources)){
            $this->resources = App::get('_resources');
        }
        
        if($position != 'main'){

            //get only the secondary panels
            $panels = $this->resources->returnRoutePanels()['secondary'];
            
            if(!is_null($panels)){
                
                if(array_key_exists($position, $panels)){

                    $panel = $this->returnPanel($panels[$position]); 
                    $this->processPanel($panel, $panelargs); 
                }
            }
        }
    }
    
    /**
     * Processes the panel position
     * @return type $panel
     */
    public function returnPanel($panel){
        
        //get the element panel
        if(strpos($panel, ':') !== false){
            
            $panelparts = explode(':', $panel);
            $mvc['panel'] = $panelparts[1];
            
            //get the controller and method
            $engine = explode('@', $panelparts[0]);
            
            $mvc['controller'] = $engine[0];
            $mvc['method'] = $engine[1];
        }
        else{
            
            //get the controller and method
            $engine = explode('@', $panel);
            
            $mvc['controller'] = $engine[0];
            $mvc['method'] = $engine[1];
        }
        
        return $mvc;
    }
    
    /**
     * Processes the panel and generate the respective elements
     * @param type $panel
     * @param type $panelargs
     * @return type
     */
    public function processPanel($panel = null, $panelargs = []){    
        
        $element = self::_getFullElementByController($panel['controller']); 
        
        if(array_key_exists('panel', $panel)){
            $partial = $this->generatePartial($element,$panel['controller'],$panel['method'], $panel['panel'], $panelargs);
        }
        else{
            $partial = $this->generatePartial($element,$panel['controller'],$panel['method'], null, $panelargs);
        }
        
        return $partial;
    }
    
    /**
     * Generates the partial panel for each position
     * @param type $element
     * @param type $controller
     * @param type $method
     * @param type $panel
     * @param type $panelargs
     */
    public function generatePartial($element, $controller, $method, $panel, $panelargs = []) {
        
        $mvc = $this->makePartialMvcEngine($element, $controller);
        $controller = self::generateNamespacedClass($controller);
        
        if((int) method_exists($controller, $method)){
            
            //add the View Class into the DI shell to be used later
            App::$shell->set('secondaryelement', $element);
            App::$shell->set('secondaryview', $mvc['view']);
            
            $this->assignMVC($controller, $mvc['model'], $mvc['view']);
            
            //add the ajax key to the panel arguments            
            $parameters['element'] = $element;
            $parameters['panel'] = $panel;
            $parameters['panelargs'] = $panelargs;
            $parameters['type'] = 'secondary';
            $parameters['_ajax'] = true;
        
            $args['action'] = $method;
            $args['params'] = $parameters;
            
            $this->run($controller, $args, 'secondary');
        }
        else{
            App::critical_error('Invalid Method: '.$controller.': '.$method);            
        }
    }
    
    /**
     * Runs the main controller and its resolved arguments
     * @param type $controller
     * @param type $arguments
     * @return type
     */
    public function run($controller, $arguments, $runtype = 'primary'){  
        
        try {
            
            //fire the at:controller route events
            Events::fireOnRoute($this->current_route, KernelEvents::VIEW);             
                        
            if($runtype == 'primary'){
                
                //merge sent Symfony arguments with the Jenga arguments
                if(!is_null($this->arguments)){
                    $arguments = array_merge($arguments, $this->arguments);
                }
                
                /**
                 * This canExecute function counterchecks with the assigned credentials 
                 * to ensure the user can execute the sent method
                 */
                if(App::has('primaryelement')){
                    
                    if($this->user()->canExecute(App::get('primaryelement'),$this->controller,$this->main_method)){

                        $this->execute($this->controller, $this->main_method, $arguments);

                        //call the onAllowed() function
                        if(!is_null($this->user())){
                            $this->user()->role->onAllowed(App::get('primaryelement'), $controller, $this->main_method);
                        }
                        return TRUE;
                    }
                    else{
                        //disable the view set before the main method is executed
                        App::get('view')->disable();

                        //call the onDenial() function
                        $this->user()->role->onDenied(App::get('primaryelement'), $controller,$this->main_method);
                        return FALSE;
                    }
                }
                else{
                    
                    //this is primarily for accessing App Classes directly without an ACL check
                    $this->execute($this->controller, $this->main_method, $arguments);
                }
            }
            elseif($runtype == 'secondary'){
                
                /**
                 * This canExecute function counterchecks with the assigned credentials 
                 * to ensure the user can execute the sent method
                 */
                if($this->user()->canExecute(App::get('secondaryelement')['name'],$controller,$arguments['action'])){
                    
                    //call the onAllowed() function
                    $this->user()->role->onAllowed(App::get('secondaryelement')['name'], $controller, $arguments['action']);
                   
                    $reflect = new \ReflectionClass($controller);
                    $reflect->newInstanceArgs($arguments);
                }
                else{
                    
                    //disable the view set before the main method is executed
                    App::get('view')->disable();

                    //call the onDenied() function
                    $this->user()->role->onDenied(App::get('secondaryelement')['name'], $controller, $arguments['action']);

                    return FALSE;
                }
            }
        }
        catch(\Exception $e){            
            App::exception($e->getMessage(), $e->getCode());
        }
    }
    
    /**
     * Uses reflection to get the arguments for each method and assign them correctly
     * @param type $controller
     * @param type $method
     * @param type $args
     */
    public function execute($controller, $method, $args = null){
        
        if(!is_null($method)){
            
            $reflection = new \ReflectionMethod($controller, $method);
            
            $pass = [];
            
            foreach($reflection->getParameters() as $param){
                
              //check if method is type hinted
              if(!is_null($param->getClass())){
                  
                 $class = $param->getClass();
                 $ctrl = Elements::resolveControllerInArgument($class->name);
                 
                 if(!is_null($ctrl)){
                     $pass[] = $ctrl;
                 }
                 else{
                    $pass[] = App::get($class->name);
                 }
              }//check the App shell to see if a binding has been defined
              elseif (App::has($param->getName())) {
                  $pass[] = App::get($param->getName());
              }
              elseif(isset($args[$param->getName()])){  
                  $pass[] = $args[$param->getName()];
              }
              else{                  
                  $pass[] = $param->getDefaultValue();
              }
            } 
            
            //execute the resolved parameters
            return $reflection->invokeArgs(new $controller, $pass);
        }
    }
    
    /**
     * Renders the Jenga Startup Page
     */
    public static function startPage($message = null){
        include APP_PROJECT .DS. 'html'.DS.'startup'.DS.'index.php';
    }
}
