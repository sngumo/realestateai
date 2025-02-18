<?php
/**
 * This class assigns the panels in a single page
 */
namespace Jenga\App\Views;

use Jenga\App\Core\App;
use Jenga\App\Project\Core\Project;

class PanelHandler{
    
    public $route;
    public $positions;
    public $resources;
    
    protected $_url;    
    protected $_defaultpanels;
    protected $_route;
    protected $_template;

    /**
     * This functions registers the panel positions for each template
     * 
     * @param type $template
     * @param type $positions
     */
    public function assignPanelPositions($template, $positions = array()){
        
        $tplpositions[$template] = $positions;
        return $tplpositions;
    }
    
    /**
     * Adds panels to specified route
     * 
     * @param type $route
     * @param type $panels
     */
    public function assignPanelstoRoute($route, $panels){
        
        $this->positions['route'][$route] = $panels();
    }
    
    /**
     * Adds panels to specified URL
     * 
     * @param type $url
     * @param type $panels
     */
    public function assignPanelstoURL($url, $panels){
       
        $this->positions['url'][$url] = $panels();
    }
    
    /**
     * Sets the required resources for each route
     * 
     * @param type $route
     * @param type $resources
     */
    public function assignResourcesToRoute($route, array $resources){
        
        $htmlresources = array_map('htmlentities',$resources);
        $this->resources['route'][$route] = $htmlresources;
    }
    
    /**
     * Sets the required resources for a specific URL
     * 
     * @param type $url
     * @param type $resources
     */
    public function assignResourcesToURL($url, $resources){
        
        $this->resources['url'][$url] = $resources;
    }

    /**
     * Returns the panel postions created by the user
     * @return type
     */
    public function returnPositions(){
        
        return $this->positions;
    }
    
    /**
     * Returns the specific route for the current URL
     * @return type
     */
    public function returnRouteFromUrl(){
        
        $this->_url = App::$shell->get('url');
        $this->_processTemplate();
        
        $route = $this->returnRoute();
        
        return $route;
    }
    
    /**
     * Processes all the panel declarations and send the right one onwards for processing
     * 
     * @param type $tpl
     */
    private function _processTemplate(){
        
        $this->_defaultpanels = $this->returnPositions();
        
        $routepanels = $this->_defaultpanels['route'];
        $urlpanels = $this->_defaultpanels['url'];
        
        //if its at the homepage, go straight to the route panels
        if(count($this->_url) == 0){
            
            $this->_processRoutePanels($routepanels);
        }
        else{
            
            //process the specific URL panel declarations
            $urlset = $this->_processUrlPanels($urlpanels);
            
            if($urlset == FALSE){                
                $this->_processRoutePanels($routepanels);
            }
        }
    }

    /**
     * Processes the specific Url panels sent
     * 
     * @param type $urlpanels
     * @return boolean
     */
    private function _processUrlPanels($urlpanels){
        
        $urlkeys = array_keys($urlpanels);
        $fullurl = '/'.join('/',$this->_url);
        
        $urlset = FALSE;
        foreach($urlkeys as $key){

            if(strcasecmp($fullurl, $key) == 0){

                $urlset = TRUE;
                $this->setRoute($key);
                $this->setRightPanel($urlpanels[$key]);
            }
        }
        
        return $urlset;
    }
    
    /**
     * Processes the route panels and determines which panel 
     * declaration to use for the sent URL
     * 
     * @param type $routepanels
     */
    private function _processRoutePanels($routepanels){
        
        $routepanelkeys = array_keys($routepanels); 
        $route = App::$shell->get('route')['url'];
        
        if(in_array($route,$routepanelkeys)){
            
            $routestore = $route;
        }
        
        if($routestore != ''){
            
            $this->setRoute($routestore);
            $this->setRightPanel($routepanels[$routestore]);
        }
    }
    
    public function setRightPanel($routepanel){
        
        $this->routepanel =  $routepanel;
    }
    
    public function setRoute($name){
        
        $this->_route = $name;
    }
    
    public function returnPanelAssignments(){
        
        $this->_url = App::$shell->get('url');        
        $this->_processTemplate();
        
        return $this->routepanel;
    }
    
    public function returnAssignmentsSetings(){
        
        $panel = $this->returnPanelAssignments();
        $panelkey = array_keys($this->returnPanelAssignments());
        
        return $panel[$panelkey[0]];
    }

    public function returnRoute() {
        
        return $this->_route;
    }
}
