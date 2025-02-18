<?php
namespace Jenga\App\Project\Core;

use Jenga\App\Views\HTML;

class Resources {
    
    public $currentroute;
    public $resources;
    
    /**
     * Sets the current route alias
     * @param type $route_alias
     */
    public function setCurrentRoute($route_alias){
        $this->currentroute = $route_alias;
    }
    
    /**
     * Returns current calculated route
     * @return type
     */
    public function getCurrentRoute(){
        return $this->currentroute;
    }
    
    /**
     * Assigns panel related to route
     * @param type $panels
     * @param type $alias
     * @param string $type Set whether its the primary or secondary
     */
    public function setRoutePanel(array $panels, $alias = null, $type = 'secondary'){  
        
        if(!array_key_exists('_ajax', $panels)){
            
            $this->resources[(!is_string($alias) ? $this->currentroute : $alias)]['panels'][$type] = $panels;        
        }
        else{
            $this->resources[(!is_string($alias) ? $this->currentroute : $alias)]['panels']['_ajax'] = $panels['_ajax'];        
        }
    }
    
    /**
     * Sets the main view panel based on the controller and method
     * @param type $controller
     */
    public function setMainPanel($controller, $method = '', $panel = '', $alias = null){                    
        
        $element = Project::elements($controller);
        
        //if fully namespaced
        if(strpos($controller, '\\') !== false){
            
            $ctr = explode('\\', $controller);
            $controller = end($ctr);
        }
        
        /**
         * Assigns the main panel based on the following convention
         * if method is blank - assign the index method
         * if panel is blank - assign the name of the element in lowercase
         */
        $mainpanel = $controller.'@'
                .($method == '' ? 'index' : $method).':'
                .($panel == '' ? strtolower($element['name']) : $panel);
        
        if(!isset($this->resources[$this->currentroute]['panels']['primary'])){
            
            $this->setRoutePanel(['_main' => $mainpanel], (!is_null($alias) ?: $this->currentroute), 'primary');
        }
    }
    
    /**
     * Assigns resources to route
     * @param type $resources
     * @param type $alias
     */
    public function setRouteResources($resources = null, $alias = null){  
        $this->resources[(!is_string($alias) ? $this->currentroute : $alias)]['resources'] = $resources;
    }
    
    /**
     * Assigns static engine to resources
     * @param type $engine
     * @param type $alias
     */
    public function setStaticRouteEngine($engine, $alias = null) {
        
        $this->resources[(!is_string($alias) ? $this->currentroute : $alias)]['engine'] = $engine;
    }
    
    /**
     * Assigns template to route
     * @param type $template
     * @param type $alias
     */
    public function setRouteTemplate($template = null, $alias = null){  
        
        $this->resources[(!is_string($alias) ? $this->currentroute : $alias)]['template'] = $template;
    }
    
    /**
     * Returns route template based on alias or the current route
     * @param type $alias
     * @return type
     */
    public function returnRouteTemplate($alias = null) {
        
        return $this->resources[(!is_string($alias) ? $this->currentroute : $alias)]['template'];
    }
    
    /**
     * Returns route static engine based on alias or the current route
     * @param type $alias
     * @return type
     */
    public function returnStaticRouteEngine($alias = null) {        
        return $this->resources[(!is_string($alias) ? $this->currentroute : $alias)]['engine'];
    }
    
    /**
     * Returns route panels based on alias or the current route
     * @param type $alias
     * @return type
     */
    public function returnRoutePanels($alias = null){        
        return $this->resources[(!is_string($alias) ? $this->currentroute : $alias)]['panels'];
    }
    
    /**
     * Returns route resources based on alias or the current route
     * @param type $alias
     * @return type
     */
    public function returnRouteResources($alias = null){
        
        $resource = $this->resources[(!is_string($alias) ? $this->currentroute : $alias)];
        
        if(array_key_exists('resources', $resource)){
            return $resource['resources'];
        }
    }
    
    /**
     * Processes the assigned resources to the route
     * and registers them to be used in the HTML head section
     */
    public function registerResources(){   
        
        $resources = $this->returnRouteResources();
        HTML::register($resources);
    }
}