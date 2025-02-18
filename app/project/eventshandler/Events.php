<?php
/**
 * This is where the system events are processed
 */
namespace Jenga\App\Project\EventsHandler; 

use Jenga\App\Core\App;
use Jenga\App\Core\File;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Events {
    
    protected $rawevents;
    
    public static $events;
    public static $dispatch;
    public static $route_events;  
    public static $route_aliases;
    public static $_routeeventkeys = [
            'before:request','on:request','before','at:controller','on:response','after','on:complete','on:exception'
        ];

    public function __construct() {        
        
        if(File::exists(App::get('_jevents')))
        $this->rawevents = require_once App::get('_jevents');  
        
        self::$dispatch = new EventDispatcher(); 
    }
    
    /**
     * Process the events array
     */
    public function process(){
        
        if(!is_null(self::$route_events)){
            foreach(self::$events as $eventid => $listener){    
                self::add($eventid, $listener[0], $listener[1]);
            }
        }
        return self::$dispatch;
    }

    /**
     * Adda an new event to the system event dispatcher
     * 
     * @param type $eventName
     * @param type $listener
     * @param type $priority
     */
    public static function add($eventName, $listener, $priority = 0){        
        self::$dispatch->addListener($eventName, $listener, $priority);
    }


    /**
     * Add an event subscriber instance which lists a group of events which will be called in sequence
     * @param type $subscriber
     * @return \Jenga\App\Project\Core\Events
     */
    public static function addQueue( $subscriber ){         
        return self::$dispatch->addSubscriber($subscriber);
    }
    
    /**
     * Fires off the event from the event name
     * 
     * @param type $eventname
     * @return type
     */
    public static function fire($eventname){             
        return self::$dispatch->dispatch($eventname);
    }
    
    /**
     * Fires event based on specific route
     * @param type $routealias
     * @param type $eventtag
     */
    public static function fireOnRoute($routealias, $eventtag) {
        
        if(!is_null(self::$route_aliases)){
            
            $aliases = array_keys(self::$route_aliases);

            if(in_array($routealias, $aliases)){

                $event = self::$route_events[$routealias];
                $key = self::_translateEventKey(array_keys($event)[0]);

                if($key == $eventtag){

                    Events::fire($eventtag);
                }
            }
        }
    }


    /**
     * Adds the Route Events into events class
     * @param type $route_events
     */
    public function addRouteEvents($route_events) {   
        
        self::$route_events = $route_events->list;
        
        //insert the route events into the full events list
        $this->integrateEvents();
    }
    
    /**
     * Registers the registered kernel events into event dispatcher
     * @param type $eventname
     */
    public function registerKernelEvents() {
        
        if(!is_null(self::$route_events)){
            
            foreach(self::$events as $event){   

                if(count($event) == 3){
                    $this->_addEventByKey($event[2], $event);
                }
            }
        }
    }
    
    public function integrateEvents(){
        
        if(!is_null(self::$route_events)){
            
            foreach(self::$route_events as $alias => $routeevents){

                $eventkey = array_keys($routeevents)[0]; 
                $listener = $routeevents[$eventkey];

                //add the event key into its correct event
                foreach ($this->rawevents as $eventname => $event){

                    if($eventname == $listener){

                        //designate the route events from standard request cycle events
                        if(count($event) == 2){
                            array_push($event, $eventkey);
                        }

                        self::$events[] = $event;
                    }

                    //link alias and event key
                    self::$route_aliases[$alias] = $this->_translateEventKey($eventkey);
                }
            }
        }
    }
    
    /**
     * Translates the Jenga Request Cycle keys into the Symfony event names
     * @param type $key
     * @return type
     */
    private static function _translateEventKey($key) {
        
        switch ($key) {
            
            case 'before:request':
                $eventkey = KernelEvents::REQUEST;
                break;
            
            case 'on:request':
            case 'before':
                $eventkey = KernelEvents::CONTROLLER;
                break;
            
            case 'at:controller':
                $eventkey = KernelEvents::VIEW;
                break;
                
            //ANGALIA - still not handled in Jenga
            case 'on:response':
                $eventkey = KernelEvents::RESPONSE;
                break;
                
            //ANGALIA - still not handled in Jenga
            case 'after:response':
                $eventkey = KernelEvents::FINISH_REQUEST;
                break;
                
            case 'on:complete':
            case 'after':
                $eventkey = KernelEvents::TERMINATE;
                break;
                
            case 'on:exception':
                $eventkey = KernelEvents::EXCEPTION;
                break;
        }
        
        return $eventkey;
    }
    
    /**
     * Adds Event based on Kernel Event key
     * 
     * @param type $key
     * @param type $event
     */
    private function _addEventByKey($key, $event){
        
        switch ($key) {

            case 'before:request':
                Events::add(KernelEvents::REQUEST,$event[0],$event[1]);
                break;

            case 'on:request':
                Events::add(KernelEvents::CONTROLLER,$event[0],$event[1]);
                break;

            case 'before':                
                Events::add(KernelEvents::CONTROLLER,$event[0],$event[1]);
                break;

            case 'at:controller':
                Events::add(KernelEvents::VIEW,$event[0],$event[1]);
                break;

            case 'on:response':
                Events::add(KernelEvents::RESPONSE,$event[0],$event[1]);
                break;

            case 'after:response':
                Events::add(KernelEvents::FINISH_REQUEST,$event[0],$event[1]);
                break;

            case 'on:complete':
                Events::add(KernelEvents::TERMINATE,$event[0],$event[1]);
                break;

            case 'after':
                Events::add(KernelEvents::TERMINATE,$event[0],$event[1]);
                break;

            case 'on:exception':
                Events::add(KernelEvents::EXCEPTION,$event[0],$event[1]);
                break;
        }
    }
    
    /**
     * Creates aliases for the Symfony class in Jenga
     */
    public function translateKernelEventClasses() {
        
        class_alias('Symfony\Component\HttpKernel\Event\GetResponseEvent', 'JRequestEvent');
        class_alias('Symfony\Component\HttpKernel\Event\FilterControllerEvent', 'JControllerEvent');
        class_alias('Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent', 'JViewEvent');
        class_alias('Symfony\Component\HttpKernel\Event\FinishRequestEvent', 'JResponseEvent');
        class_alias('Symfony\Component\HttpKernel\Event\PostResponseEvent', 'JTerminateEvent');
        class_alias('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent', 'JExceptionEvent');
    }
    
    /**
     * Check the sent route group key against set route keys
     * 
     * @param array $group
     * @return boolean
     */
    public static function keywordGroupCheck($group){
        
        foreach(self::$_routeeventkeys as $key){            
            if(array_key_exists($key, $group))
                return true;
        }
        
        return false;
    }
    
    /**
     * 
     * @param type $eventname
     * @return type
     */
    public static function getEvents($eventname){
        
        return self::$dispatch->getListeners($eventname);
    }
}
