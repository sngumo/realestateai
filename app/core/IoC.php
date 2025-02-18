<?php
namespace Jenga\App\Core;

use DI\ContainerBuilder;
use Jenga\MyProject\Config;

use Jenga\App\Helpers\Help;

/**
 * Database and query classes which need to be loaded in singleton fashion
 */
use Jenga\App\Database\Build\Query;
use Jenga\App\Database\Systems\Pdo\Handlers\PDOHandler;

use DI;
use DI\Scope;


/**
 * This is a facade for PHP-DI to allow for proper integration into the Jenga Framework
 */
class IoC extends ContainerBuilder {
    
    private $_config;
    
    public $build;
    public $autowiring;
    public $definitions;
    
    public static $handlers;
    
    public function __construct(Config $config = null){
        
        parent::__construct();
        
        $this->build = $this;
        $this->_config = $config;
        
        $this->build->useAutowiring(true);
        $this->build->useAnnotations(true);
    }
    
    /**
     * Processes the registered service handlers
     */
    public function registerHandlers(){
        
        self::$handlers = include APP .DS. 'services' .DS. 'services.php';
        
        //process the services
        foreach(self::$handlers['handlers'] as $handle => $service){
            
            $handle = str_replace(' ', '', strtolower($handle));
            
            if(!is_array($service)){                
                $this->definitions[$handle] = $service;
            }
            else{
                
                //check if mode is set
                if(array_key_exists('mode', $service)){

                    switch ($service['mode']) {

                        case 'lazy':
                            $this->definitions[$handle] = \DI\object($service['class'])->lazy();
                            break;

                        default:
                            $this->definitions[$handle] = \DI\object($service['class']);
                            break;
                    }
                }
                
                //unset service mode and class
                unset($service['class'], $service['mode']);
                
                if(count($service)>=1){
                    
                    foreach($service as $servicekey => $value){
                        $this->definitions[$servicekey] = $value;
                    }
                }
            }
        }
        
        //register database connector file
        if(!is_null($this->_config))
            $this->definitions['connector'] = $this->_config->connector;
        
        //register the pdo handler 
        $this->definitions[PDOHandler::class] = DI\object()->scope(Scope::PROTOTYPE);
        $this->definitions[Query::class] = DI\object()->scope(Scope::PROTOTYPE);
        
        //add definitions to IoC shell
        $this->build->addDefinitions($this->definitions);
        
        return $this;
    }
    
    /**
     * Builds the definitions into the IoC
     * 
     * @param type $ignoredocerrors
     * @return type
     */
    public function register($ignoredocerrors = FALSE) {
        
        if($ignoredocerrors == TRUE){
            $this->build->ignorePhpDocErrors($ignoredocerrors);
        }
        
        return $this->build->build();
    }
}

