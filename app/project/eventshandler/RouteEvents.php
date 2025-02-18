<?php
namespace Jenga\App\Project\EventsHandler;

/**
 * This class process the attached events in the routes
 */
class RouteEvents {
    
    public $list;
    
    /**
     * Schedules the route events according to the route
     * @param type $events
     * @param type $alias
     */
    public function scheduleRouteEvents($events, $alias){

        if(!is_null($events)){
            foreach($events as $eventname => $event){            
                $this->list[$alias][$eventname] = $event;
            }
        }
    }
    
    /**
     * Returns the entered route events
     * @param type $alias
     * @return type
     */
    public function returnRouteEvents($alias = null) { 
        
        if(!is_null($alias))
            return $this->list[$alias];
        else
            return $this->list;
    }
}

