<?php
namespace Jenga\App\Project\Routing;

use Jenga\App\Core\App;
use Jenga\App\Helpers\Help;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\EventsHandler\Events;

class Route {
    
    private static $_eventstack = [];
    private static $_routes = [];
    private static $_currentroute;    
    private static $_instance;
    private static $_anyflag = 0;
    
    /**
     * This holds the secondary page positions and resources for each route
     * @var type 
     */
    protected static $page = [];
    
    /**
     * Handles the unlisted functions
     * @param type $name
     * @param type $arguments
     */
    public function __call($name, $arguments) {
        
        //check for pinTo prefix
        if(strpos($name, 'pinTo') == 0){
            $this->pinTo(strtolower(str_replace('pinTo', '', $name)));
        }
        
        return $this;
    }
    
    public static function init ($routesfile){
        
        if (self::$_instance === null) {
            self::$_instance = new self;
        }
        
        //this will be used to override the default Route
        require $routesfile;
        return new static;
    }   
    
    /**
     * Processes the routes inside group
     * @param array $groupname
     * @param \Closure $closure
     */
    public static function group(array $groupname, \Closure $closure){
        
        if(Events::keywordGroupCheck($groupname)){            
            self::_setEventStack($groupname);
        }
        
        App::call($closure, new static);
        
        if(!is_null(self::$_eventstack))
            array_pop(self::$_eventstack);
        
        return new static;
    }
    
    /**
     * Holds the respective panel positions and resources to later be assigned to multiple routes
     * @param type $id The identification name for each page
     * @attributes Organize your page attributes into the 
     *             following order: $template = null, array $panel_positions = [], array $resources = []
     * @param string $template the template to be attached to the page
     * @param array $panel_positions
     * @param array $resources
     */
    public static function page($id, $attributes = null){
        
        $type = '';
        $args = func_get_args();
        
        if(func_num_args() < 2){
            App::warning('The page id and attributes MUST be assigned');
        }
        
        //loop through the sent arguments
        foreach($args as &$a){            
            $type .= gettype($a).'|';
        }
        
        //trim type string
        $type = rtrim($type,'|');
        
        //switch by arrangement to know what attributes are assigned
        $attrs = [];
        switch ($type) {
            case 'string|string':
                //id and template assigned
                $attrs['id'] = $args[0];
                $attrs['template'] = $args[1];
                break;

            case 'string|string|array':
                //id, template and positions assigned
                $attrs['id'] = $args[0];
                $attrs['template'] = $args[1];
                $attrs['positions'] = $args[2];
                break;
            
            case 'string|string|array|array':
                //id, template, positions and resources assigned
                $attrs['id'] = $args[0];
                $attrs['template'] = $args[1];
                $attrs['positions'] = $args[2];
                $attrs['resources'] = $args[3];
                break;
            
            case 'string|array':
                //id and positions assigned
                $attrs['id'] = $args[0];
                $attrs['positions'] = $args[1];
                break;
            
            case 'string|array|array':
                //id, positions and resources assigned
                $attrs['id'] = $args[0];
                $attrs['positions'] = $args[1];
                $attrs['resources'] = $args[2];
                break;
        }
        
        //assign attributes to page
        if(!array_key_exists($id, self::$page)){

            self::$page[$id]['attributes'] = $attrs;
            return self::$_instance;
        }
        else{
            App::critical_error('A page named '.$id.' already exists');
        }
    }
    
    /**
     * Extends an existing page's panels and dependencies
     * @param type $page
     */
    public function extendsPage($page){
        
        $name = strtolower($page);
        if(array_key_exists($name, self::$page)){
            
            //get the previous page attributes
            $attrs = self::$page[$name]['attributes'];
            
            //add to the new page
            $page = array_keys(self::$page);
            $current = end($page);
            $current_attrs = self::$page[$current]['attributes'];
            
            //merge id, positions and resources separately
            $newattrs = [];
            foreach($current_attrs as $key => $curattrs){
                switch ($key) {
                    
                    //retain the page id
                    case 'id':
                        $newattrs['id'] = $current;
                        break;
                    
                    //merge all the assigned positions
                    case 'positions':
                        $newattrs['positions'] = array_merge($attrs['positions'], $curattrs);
                        break;
                    
                    //merge all the assigned resources
                    case 'resources':
                        $newattrs['resources'] = array_merge($attrs['resources'], $curattrs);
                        break;
                }
            }
            
            self::$page[$current]['attributes'] = $newattrs;
        }
        else{
            App::critical_error('Error: Page named '.$name.' not found');
        }
    }
    
    /**
     * Attaches the set route to a given page
     * @param type $pagename
     */
    public function pinTo($pagename) {
        
        $name = strtolower($pagename);
        if(array_key_exists($name, self::$page)){
            
            $attrs = self::$page[$name]['attributes'];
            $keys = array_keys($attrs);
            
            foreach($keys as $key){
                switch($key){
                    
                    case 'template':
                        $this->attachTemplate($attrs['template']);
                        break;
                    
                    case 'positions':
                        $this->assignPanels($attrs['positions']);
                        break;
                    
                    case 'resources':
                        //assign to body by default
                        $this->assignResources($attrs['resources'])->toBody();
                        break;
                }
            }
        }
        else{
            App::critical_error('Error: Page named '.$name.' not found');
        }
    }


    /**
     * Processes routes with the GET request method
     * @param type $url
     * @param type $engine
     * @return new static
     */
    public static function get($url, $engine, array $defaults = null, $return = true){
        
        $alias = self::_createAlias($url,'GET');
        
        if(substr($url, -1) == '/'){
            $url = rtrim($url,'/');
        }
        
        self::$_routes[$alias]['url'] = $url;
        
        if(Help::is_closure($engine)){
            self::$_routes[$alias]['engine'] = $engine();
        }
        else
            self::$_routes[$alias]['engine'] = $engine;
        
        //process the defaults
        self::$_routes[$alias]['defaults'] = $defaults;
        
        //add the event into the route
        if(count(self::$_eventstack) >= 1){                
            self::$_routes[$alias]['event'] = self::$_eventstack;
        }  
        
        if(self::$_anyflag !== 0 && $return == false){
            
            if(!is_array(self::$_currentroute)){
                self::$_currentroute = [];
            }
            
            self::$_currentroute[ self::$_anyflag ][] = $alias;
        }
        elseif(self::$_anyflag !== 0 && $return == true){
            
            if(!is_array(self::$_currentroute)){
                self::$_currentroute = '';
            }
            
            self::$_currentroute = $alias;
        }
        else{
            self::$_currentroute = $alias;
        }
        
        return new static;
    }
    
    /**
     * Processes routes with the POST request method
     * @param type $url
     * @param type $engine
     */
    public static function post($url, $engine, array $defaults = null, $return = true){
        
        $alias = self::_createAlias($url,'POST');
        
        if(substr($url, -1) == '/'){
            $url = rtrim($url,'/');
        }
        
        self::$_routes[$alias]['url'] = $url;
        
        if(Help::is_closure($engine))
            self::$_routes[$alias]['engine'] = $engine();
        else
            self::$_routes[$alias]['engine'] = $engine;
        
        //process the defaults
        self::$_routes[$alias]['defaults'] = $defaults;
        
        //add the event into the route
        if(count(self::$_eventstack) >= 1){                
            self::$_routes[$alias]['event'] = self::$_eventstack;
        } 
        
        if(self::$_anyflag !== 0 && $return == false){
            
            if(!is_array(self::$_currentroute)){
                self::$_currentroute = [];
            }
            
            self::$_currentroute[ self::$_anyflag ][] = $alias;
        }
        elseif(self::$_anyflag !== 0 && $return == true){
            
            if(!is_array(self::$_currentroute)){
                self::$_currentroute = '';
            }
            
            self::$_currentroute = $alias;
        }
        else{
            self::$_currentroute = $alias;
        }
        
        return new static;
    }
    
    /**
     * Processes dual routes for the GET & POST request methods
     * 
     * @param type $url
     * @param type $engine
     * @param type $defaults
     */
    public static function any($url, $engine, array $defaults = null){
        
        //set the any flag to true, for multiple processing
        self::$_anyflag = rand(0, 10000000);
        
        //process the GET method
        self::get($url, $engine, $defaults, false);
        
        //process the POST method
        self::post($url, $engine, $defaults, false);
        
        return new static;
    }
    
    /**
     * Processes routes with the PUT request method
     * 
     * @param type $url
     * @param type $engine
     */
    public static function put($url, $engine, $defaults = null){
        
        $alias = self::_createAlias($url,'PUT');
        
        if(substr($url, -1) == '/'){
            $url = rtrim($url,'/');
        }
        
        self::$_routes[$alias]['url'] = $url;
        
        if(Help::is_closure($engine))
            self::$_routes[$alias]['engine'] = $engine();
        else
            self::$_routes[$alias]['engine'] = $engine;
        
        //process the defaults
        self::$_routes[$alias]['defaults'] = $defaults;
        
        //add the event into the route
        if(count(self::$_eventstack) >= 1){                
            self::$_routes[$alias]['event'] = self::$_eventstack;
        } 
        
        self::$_currentroute = $alias;        
        return new static;
    }
    
    /**
     * Processes routes with the DELETE request method
     * 
     * @param type $url
     * @param type $engine
     */
    public static function delete($url, $engine, $defaults = null){
        
        $alias = self::_createAlias($url,'DELETE');
        self::$_routes[$alias]['url'] = $url;
        
        if(Help::is_closure($engine))
            self::$_routes[$alias]['engine'] = $engine();
        else
            self::$_routes[$alias]['engine'] = $engine;
        
        //process the defaults
        self::$_routes[$alias]['defaults'] = $defaults;
        
        //add the event into the route
        if(count(self::$_eventstack) >= 1){                
            self::$_routes[$alias]['event'] = self::$_eventstack;
        } 
        
        self::$_currentroute = $alias;        
        return new static;
    }
    
    /**
     * Processes routes with the HEAD request method
     * 
     * @param type $url
     * @param type $engine
     */
    public static function head($url, $engine, $defaults = null){
        
        $alias = self::_createAlias($url,'HEAD');
        
        if(substr($url, -1) == '/'){
            $url = rtrim($url,'/');
        }
        
        self::$_routes[$alias]['url'] = $url;
        
        if(Help::is_closure($engine))
            self::$_routes[$alias]['engine'] = $engine();
        else
            self::$_routes[$alias]['engine'] = $engine;
        
        //process the defaults
        self::$_routes[$alias]['defaults'] = $defaults;
        
        //add the event into the route
        if(count(self::$_eventstack) >= 1){                
            self::$_routes[$alias]['event'] = self::$_eventstack;
        } 
        
        self::$_currentroute = $alias;        
        return new static;
    }
    
    /**
     * Processes routes with static URL regardless of request method
     * 
     * @param type $url
     * @param type $engine
     * @param type $defaults
     * @return type
     */
    public static function url($url, $engine){
        
        $alias = self::_createAlias($url,'ANY');
        self::$_routes[$alias]['url'] = $url;
        
        if(Help::is_closure($engine))
            self::$_routes[$alias]['engine'] = $engine();
        else
            self::$_routes[$alias]['engine'] = $engine;
        
        //add the event into the route
        if(count(self::$_eventstack) >= 1){                
            self::$_routes[$alias]['event'] = self::$_eventstack;
        } 
        
        self::$_currentroute = $alias;        
        return new static;
    }
    
    /**
     * Allows for specifying of certain conditions for a route
     * 
     * @param array $regex The format should be ['placeholder'=>'Regular Expression']
     */
    public function where(array $regex){
        
        if(is_array(self::$_currentroute) && self::$_anyflag !== 0){            
            if(count(self::$_currentroute[ self::$_anyflag ]) >= 1){                
                foreach (self::$_currentroute[ self::$_anyflag ] as $route) {
                    self::$_routes[$route]['regex'] = $regex;
                }
            }
        }
        else{
            self::$_routes[self::$_currentroute]['regex'] = $regex;
        }
        
        return $this;
    }
    
    /**
     * Assigns resources to sent url
     * @param type $url
     * @param type $resources
     */
    public static function resources($url,$resources){
        
        $alias = self::_createAlias($url,'ANY');  
        self::$_routes[$alias]['resources'] = $resources;
    }
    
    /**
     * Same function as self::resources but applies to current route
     * @param type $url
     * @param type $resources
     */
    public function assignResources($resources){
        
        if(is_array(self::$_currentroute) && self::$_anyflag !== 0){            
            if(count(self::$_currentroute[ self::$_anyflag ]) >= 1){     
                
                foreach (self::$_currentroute[ self::$_anyflag ] as $route) {
                    
                    //check if resource is present, if so merge all the resources
                    if(!array_key_exists('resources', self::$_routes[$route])){
                        self::$_routes[$route]['resources'] = $resources;
                    }
                    else{
                        $newresource = array_merge(self::$_routes[$route]['resources'], $resources);
                        unset(self::$_routes[$route]['resources']);
                        
                        self::$_routes[$route]['resources'] = $newresource;
                    }
                }
            }
        }
        else{
            
            //check if resource is present, if so merge all the resources
            if(!array_key_exists('resources', self::$_routes[self::$_currentroute])){
                self::$_routes[self::$_currentroute]['resources'] = $resources;
            }
            else{
                $newresource = array_merge(self::$_routes[self::$_currentroute]['resources'], $resources);
                unset(self::$_routes[self::$_currentroute]['resources']);
                
                self::$_routes[self::$_currentroute]['resources'] = $newresource;
            }
        }
        
        return $this;
    }
    
    /**
     * Assigns dependencies needed to run each route
     * @param array $dependants
     * @return type
     */
    public function assignPageDependencies(array $dependants){        
        $this->assignResources($dependants);
        return $this;
    }
    
    /**
     * Reassign route resources to body
     * @return \Jenga\App\Project\Routing\Route
     */
    public function toBody(){
        
        if(is_string(self::$_currentroute) && !is_null(self::$_routes[self::$_currentroute]['resources'])){
            
            //move head resources to body
            self::$_routes[self::$_currentroute]['resources']['body'] = self::$_routes[self::$_currentroute]['resources'];

            //unset resources
            $resources = array_keys(self::$_routes[self::$_currentroute]['resources']);

            foreach($resources as $key){

                if($key != 'body'){
                    //remove entry to avoid duplicate entries
                    unset(self::$_routes[self::$_currentroute]['resources'][$key]);
                }
            }

            return $this;
        }
    }
    
    /**
     * Assign the template to current route
     * @param type $template
     */
    public function attachTemplate($template) {
        
        if(is_array(self::$_currentroute) && self::$_anyflag !== 0){
            
            if(count(self::$_currentroute[ self::$_anyflag ]) >= 1){
                
                foreach (self::$_currentroute[ self::$_anyflag ] as $route) {
                    self::$_routes[$route]['template'] = $template;
                }
            }
        }
        else{
            self::$_routes[self::$_currentroute]['template'] = $template;
        }
        return $this;
    }
    
    /**
     * Assign the route panels to current route
     * @param type $template
     * @param type $panels
     * @return type
     */
    public function assignPanels($template ,$panels = []) {
       
        if(is_string($template)){
            $this->attachTemplate($template);
        }
        elseif(is_array($template)){
            $panels = $template;
        }
       
        if(is_array(self::$_currentroute) && self::$_anyflag !== 0){
            
            if(count(self::$_currentroute[ self::$_anyflag ]) >= 1){
                
                foreach (self::$_currentroute[ self::$_anyflag ] as $route) {
                    self::$_routes[$route]['panels'] = $panels;
                }
            }
        }
        else{            
            self::$_routes[self::$_currentroute]['panels'] = $panels;
        }
        
        return $this;
    }

    /**
     * Processes the event stack
     * 
     * @param type $event
     */
    private static function _setEventStack($event){        
        self::$_eventstack = $event;
    }
    
    /**
     * Creates a route alias to be used to identify the route
     */
    private static function _createAlias(&$url, $method){
        
        //remove url variables
        if(is_string($url)){
            
            $url1 = str_replace('{', '', $url);
            $url2 = str_replace('}', '', $url1);

            if($url2 == '/'){
                $url2 .= 'none';
            }

            //replace forward slash with underscore
            $alias = strtolower($method).str_replace('/', '_', rtrim($url2, '/'));
        }
        elseif(is_array($url)){
            
            $key = array_keys($url)[0];
            
            $url = $url[$key];
            $alias = strtolower($method).'_'.$key;
        }
        
        return $alias;
    }
    
    /**
     * Creates a route alias to be used to identify the route
     * @uses Url::route Used for URL generation
     */
    public static function generateAlias($url){
        
        //remove url variables
        $url1 = str_replace('{', '', $url);
        $url2 = str_replace('}', '', $url1);
        
        if($url2 == '/'){
            $url2 .= 'none';
        }
           
        //replace forward slash with underscore
        $alias = str_replace('/', '_', rtrim($url2, '/'));
        $alias = ltrim($alias, '_');
        
        return $alias;
    }
    
    /**
     * Collects all the routes embedded in each element
     * @param type $name
     */
    public static function collect($name){
        
        $name = strtolower($name);
        
        $elements = Project::elements();
        $path = $elements[$name]['path'];
        
        //check if the file exists
        if(file_exists(ABSOLUTE_PROJECT_PATH .DS. $path .DS. 'routes.php')){
            require ABSOLUTE_PROJECT_PATH .DS. $path .DS. 'routes.php';
        }
        else{
            App::critical_error('Element route file not found in: '.$name);
        }
    }
    
    /**
     * Returns the processed routes
     * @return type
     */
    public function process() {        
        return self::$_routes;
    }
}