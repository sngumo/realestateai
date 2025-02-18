<?php
namespace Jenga\MyProject;

class Config {
    
    //website settings
    public $project = 'Real Estate Docs';
    public $description = "A portal designed to analyze real estate documents and help users to fine tune them";
    public $keywords = 'Payments Portal';
    public $mailfrom = '';
    public $mailadmin = '';

    /****** Development Environment Settings ********/
    public $development_environment = true; //if set to true the db settings will be retrieved from the settings below
                                            //otherwise when in production they will be retrieved from the .ini files in the connections folder
    public $error_reporting = 'simple'; //options are: default | -1, none | 0, simple, maximum, development
    
    /****** Database Connection Settings ***************/
    public $connector = ABSOLUTE_PROJECT_PATH .DS. 'connections.php';
    
    /****** Trusted Proxy UPs ***************/
    public $trustedips = ['127.0.0.1']; //add any other trusted IPs
    
    /****** Secuurity Keys ******************/
    public $public_key = 'L2FwcC9jZXJ0cy9kZWZ1c2UtZ2VuLWtleS50eHQ=';
    
    /******** Default Time Settings **********/
    public $timezone = "Europe/Berlin";
    
    //public $date_of_reg = "00:45 Saturday, 17 June 2023 ";
    
    /*********** Error & Log Handling *******************/
    public $send_log_to = 'console'; //options are: file, console
    public $logpath = 'tmp' . DS . 'logs';
    public $log_to_console = false;
    
    /****** Cache Settings *********************/
    public $cache_files = true;
    
    /***** User State Settings *******************/
    public $session_storage_type = 'file'; //database for db session storage or file if you want to store the session in your computer as a flat file
    public $session_table = ''; /*Name of the MySQL table used by the class. NOTE: the table prefix will be added*/
    public $session_lifetime = 36000; /*(Optional) The number of seconds after which a session will be considered as expired.*/    
    public $lock_to_user_agent = true; /*(Optional) Whether to restrict the session to the same User Agent (or browser) as when the session was first opened.*/
    public $lock_timeout = 12000; /*(Optional) The maximum amount of time (in seconds) for which a lock on the session data can be kept. Default is 60*/
    public $lock_to_ip = false; /*(Optional) Whether to restrict the session to the same IP as when the session was first opened.*/
    public $cookie_domain = 'localhost';
    public $fetch_interval = 30; // Interval in minutes
    
    /**** Safaricom Dev Mode **********************/
    public $safaricom_dev_mode = true;
    
//    public $frontend_url = 'https://platform.followups.co.ke';
    public $frontend_url = 'http://localhost/realestate';
}
