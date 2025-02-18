<?php
namespace Jenga\App\Controllers;

use Jenga\App\Core\App;
use Jenga\App\Project\Logs\Log;
use Jenga\App\Models\Utilities\ObjectRelationMapper;
use Jenga\App\Project\Core\Project;

class Controller extends Project {
     
    /**
     * @Inject("__model")     
     * @var Jenga\App\Models\Utilities\ObjectRelationMapper
     */
    public $model = null;
    
    /**
     * @Inject("__view")
     */
    public $view = null;
    
    /**
     * @Inject("gateway")
     */
    public $auth;
    
    protected $method = null;
    
    /**
     * @Inject("params")
     */
    protected $params;
    
    /**
     * Checks is the view disable has been called remotely
     * @var boolean
     */
    private $_remote = false;

    public function __construct($method = '', $params = '', $bypass_shell = false, $action = null) { 
        
        if(!is_null($action)){
            $method = $action;
        }
        
        if($bypass_shell === FALSE){
            App::$shell->injectOn($this);
        }
        $panelargs = null;

        if(is_array($params)){

            //for the secondary panel
            extract($params, EXTR_OVERWRITE);

            if(isset($type) && $type == 'secondary'){
                $this->view->configurePanelView($element, $panel, $_ajax);
            }
        }
        
        $this->_execute($method, $panelargs);
    }
 
    private function _execute($method = '', $panelargs = null){
        
        //check method
        if(App::has('method') && App::get('method') != '_main'){
            $this->method = App::get('method');
        }
        
        //called by primary controller and any call from the Elements class
        if(!is_null($this->method) && $method == '' && $this->method != 'none'){
            $this->{$this->method}($this->params);
        }
        //called by secondary controller
        elseif($method != ''){                
            
            if(!is_null($panelargs)){
                call_user_func_array([$this, $method],  array_values($panelargs));    
            }
            else
                $this->{$method}();    
        }
    }
    
    /**
     * This sets the variables into the connected View
     * 
     * @param type $name
     * @param type $value
     */
    public function set($name,$value) {        
        $this->view->set($name,$value);
    }
    
    /**
     * Allow user to push new properties and methods into an existing object
     * 
     * @param type $object
     * @param array [$method => $value]
     */
    public function push(&$object, array $method_value){
        
        if(is_object($object)){            
            foreach($method_value as $method => $value){
                $object->$method = $value;
            }
        }
        elseif(is_array($object)){
            
            foreach($object as $piece){                
                if(is_object($piece)){                    
                    foreach($method_value as $method => $value){
                        $piece->$method = $value;
                    }
                }
            }
        }
    }
    
    /**
     * Disables the element view, if the Controller function is called directly
     */
    public function disableView($remote = false){            
        if($remote == true)
            $this->_remote = $remote;
        
        $this->view->disable();
    }
    
    /**
     * This is designed to bypass invocation of the __construct() which injects the Application shell 
     * @return type
     */
    function __invoke() {        
        $ctrl = new \ReflectionClass(get_class($this));
        return $ctrl->newInstanceWithoutConstructor();
    }
    
    function __destruct() {            
        if($this->_remote == FALSE){            
            if(!is_null($this->view)){
                return $this->view->render();
            }
        }
    }         
}
