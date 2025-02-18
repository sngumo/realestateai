<?php
namespace Jenga\App\Project\Logs;

use Jenga\App\Core\App;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\BrowserConsoleHandler;

/**
 * This is the default error handling mechanism for Jenga
 * 
 * @author Stanley Ngumo
 * @uses Monolog
 */
class LogHandler {
    
    public $logger; 
    public $logfolder;
    public $logfile;
    public $minloglevel;
    
    private $_config;
    private $_defaulthandlers = [StreamHandler::class, BrowserConsoleHandler::class];
    
    public function __construct($name, $level = Logger::ERROR, $handlers = []) {
        
        $this->_config = App::get('_config');
            
        $this->minloglevel = $level;
        
        //load the logger::class
        $this->logger = new Logger($name);
        
        //process handlers
        if(!is_null($this->_config))
        $this->processHandlers($name,$handlers);        
        
        return $this->logger;
    }
    
    /**
     * Processes the Monolog handlers
     * @param type $handlers
     */
    public function processHandlers($name, $handlers){
        
        if(count($handlers) == 0){
            
            foreach($this->_defaulthandlers as $handler){
                
                //initialize BrowserConsole
                if($handler == BrowserConsoleHandler::class){
                    $handle = App::make($handler, ['level' => $this->minloglevel]);
                }
                else{
                    
                    //load the logpath 
                    if($this->_config->send_log_to == 'file'){

                        $this->logfolder = ABSOLUTE_PATH .DS. $this->_config->logpath;
                        $this->logfile = $this->logfolder .DS. strtolower($name).'.log';
                        
                        $stream = $this->logfile;
                    }
                    else{
                        $stream = 'php://stderr';
                    }
                    
                    $handle = App::make($handler, [
                                            'stream' => $stream,
                                            'level' => $this->minloglevel, 
                                            'bubble' => $this->_config->log_to_console
                                        ]);
                }
                
                //insert handler
                if(!is_null($handle))
                    $this->logger->pushHandler($handle);
            }
        }
    }
}
