<?php
namespace Jenga\App\Project\Logs;

use Jenga\App\Core\App;
use Jenga\App\Project\Logs\LogHandler;

/**
 * The log class will log all activity and errors within Jenga. 
 * Its designed to be used statically across all Monolog log levels i.e.
 * 
 * Log::debug($message); DEBUG (100): Detailed debug information
 * Log::info($message); INFO (200): Interesting events. Examples: User logs in, SQL logs.
 * Log::notice($message); NOTICE (250): Normal but significant events
 * Log::warning($message); WARNING (300): Exceptional occurrences that are not errors. 
 *                          Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
 * Log::error($message); ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
 * Log::critical($message); CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.
 * Log::alert($message); ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc. 
 *                      This should trigger the SMS alerts and wake you up.
 * Log::emergency($message); EMERGENCY (600): Emergency: system is unusable.
 * 
 * @author Stanley Ngumo
 */
class Log {
    
    public static $handle;
    
    private static function _createHandle(){
        self::$handle = App::get(LogHandler::class);
    }
    
    public static function __callStatic($name, $arguments) {
        
        self::_createHandle();
        self::$handle->logger->{strtolower($name)}($arguments[0]);
    }
    
    /**
     * Returns the actual Monolog instance
     */
    public static function getMonolog(){
        
        self::_createHandle();
        return self::$handle->logger;
    }
}
