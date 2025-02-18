<?php
namespace Jenga\MyProject;

use Jenga\App\Core\App;
use Jenga\App\Core\IoC;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Core\Partials;

class Elements extends Project {
    
    public static $elements;
    
    /**
     * DEPRECIATED 
     * 
     * Resolves an element and any sent arguments and sends the data
     * @deprecated use Elements:call() instead
     * 
     * @param type $engine Format: element/controller@method
     * @param type $args Arguments to be used in method
     */
    public static function load($engine, $args = array()){
        
        self::$elements = Project::elements();
        
        $components = explode('/', $engine);
        $select_element = self::$elements[strtolower($components[0])];
        
        $project = self::instance();
        
        if(strpos($components[1], '@') !== false){
            
            $pieces = explode('@', $components[1]);
            $controller = $pieces[0];
            $method = $pieces[1];
        }
        else {
            App::critical_error('Please insert method using @ symbol when using Elements::load');
        }
        
        $mvc = $project->makePartialMvcEngine($select_element, $controller);
        
        //the core MVC components
        $project->assignMVC($mvc['controller'], $mvc['model'], $mvc['view'], $method, $params);
        
        $ctr_object = App::$shell->get($mvc['controller']);
        $ctr_object->disableView(true);
        
        return call_user_func_array([$ctr_object, $method], $args);
    }
    
    /**
     * Calls an element and returns the fully resolved element object
     * 
     * @param type $engine Format: element/controller
     * @param type $args Arguments to be used in method
     */
    public static function call($engine, $terminal = false){
        
        self::$elements = Project::elements();
        
        $components = explode('/', $engine);
        $select_element = self::$elements[strtolower($components[0])];
        
        $project = self::instance();
        $mvc = $project->makePartialMvcEngine($select_element,$components[1]);
        
        $controller = $mvc['controller'];
        $method = 'none';
        
        //the core MVC components
        if($terminal == false){
            $project->assignMVC($controller, $mvc['model'], $mvc['view'], $method);
        
            $ctr_object = App::make($controller);
            $ctr_object->disableView(true);
        
            return $ctr_object;
        }
        else{
            return self::resolveControllerInArgument($controller);
        }
    }
    
    /**
     * BETA - still bringing issues
     * Resolves the full element from a controller loaded as a method argument
     * 
     * @param string $basecontroller
     * @return object
     */
    public static function resolveControllerInArgument($basecontroller){
        
        $basectrl = explode('\\', $basecontroller);
        $ctrl = end($basectrl);
        $element = self::_getFullElementByController($ctrl);    
        
        if(!is_null($element)){
            
            $project = self::instance();
            $mvc = $project->makePartialMvcEngine($element, $ctrl);

            $controller = $mvc['controller'];
            $method = 'none';

            //the core MVC components
            $shell = self::assignOrphanMVC($controller, $mvc['model'], $mvc['view'], $method);
            
            $ctr_object = $shell->make($controller, [
                'method' => '',
                'params' => '',
                'bypass_shell' => true
            ]);
            $ctr_object->disableView(true);

            return $ctr_object;
        }
        
        return NULL;
    }
    
    /**
     * Creates an isolated MVC shell for the terminal or an injected controller
     * @param type $controller
     * @param type $model
     * @param type $view
     * @param type $method
     * @param type $params
     * @return type
     */
    public static function assignOrphanMVC($controller, $model, $view, $method = null, $params = null){
        
        $ioc = new IoC(App::$config);
        $ioc->registerHandlers();
        
        $shell = $ioc->register(TRUE);
        $dbal = self::getDatabaseConnector();  
        
        //the core MVC components
        $shell->set( '__model', \DI\factory(function() use($model, $dbal, $shell){
            
            //initialize the __map method to inject the connector and active record objects
            $modelobj = $shell->get($model);        
            return call_user_func_array([$modelobj,'__map'], [$shell->get($dbal)]);
        }));
        $shell->set( '__controller', $controller);                
        $shell->set( '__view', \DI\object($view));
        
        //add the primary element method into the App shell
        if(is_null(self::instance()->main_method)){
            $shell->set('method', $method);
        }
        else{
            $shell->set('method','_main');
        }
        
        //add the element parameters into the App shell
        $shell->set('params', $params);
        $shell->set('gateway', NULL);
        
        return $shell;
    }
}