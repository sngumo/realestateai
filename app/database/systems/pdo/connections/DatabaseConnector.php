<?php
namespace Jenga\App\Database\Systems\Pdo\Connections;

use Jenga\App\Core\App;

/**
 * This is the full database connector for PDO
 *
 * @author stanley
 */
class DatabaseConnector {
    
    public $pdo;
    public $connections;
    public $activeconn;
    public $db_record_class;
    
    /**
     * Database credentials
     * @var array
     */
    private $_connectionParams = [
        'driver' => '',
        'host' => null,
        'username' => null,
        'password' => null,
        'dbname' => null,
        'port' => null,
        'charset' => null
    ];
    
    /**
     * PDO connection options
     * @var type 
     */
    private $_options;
    
    /**
     * Sets the user friendly terms against technical
     * @var type 
     */
    private $_drivers = [
        'mysql' => 'PDO_MYSQL',
        'sqlserver' => 'PDO_DBLIB',
        'firebird' => 'PDO_FIREBIRD',
        'ibm' => 'PDO_IBM',
        'informix' => 'PDO_INFORMIX',
        'oracle_call_interface' => 'PDO_OCI',
        'odbc' => 'PDO_ODBC',
        'postgre_sql' => 'PDO_PGSQL',
        'sqlite' => 'PDO_SQLITE',
        '4d' => 'PDO_4D'
    ];
    
    public static $instance;
    
    /**
     * @Inject({"connector"})    
     * @return \Jenga\App\Database\DBase
     */
    public function __construct($connector){
        
        $this->connections = require $connector;

        //set to static instance to allow the connections to be made statically
        self::$instance = $this;
    }
    
    /**
     * Executes an explicitly defined connection
     * 
     * @param type $name
     * @return Jenga\App\Database\Connections\DatabaseConnector An instance of the connector
     */
    public static function connect($name = 'default'){
            
        //set up the default connection
        if($name == 'default'){
            $name = self::$instance->activeconn = self::$instance->connections['default'];
        }
        else{
            self::$instance->activeconn = $name;
        }

        //get the other connections
        $connections = self::$instance->connections['connections'];

        if(array_key_exists($name, $connections)){

            self::$instance->setConnectionParams($connections[$name]);

            self::$instance->_connect();
            
            //TODO make sure that this only loads if PDO is assigned
            self::$instance->_assignPdoBuilder();

            return self::$instance;
        }
        else{
            throw new \Exception('Database connection name: '.$name.' not found.');
        }
    }
    
    /**
     * Set the connection parameters for PDO
     * @param type $connectionParams
     */
    public function setConnectionParams($connectionParams){
        
        foreach ($connectionParams as $param => $setting) {
            
            //set the driver for reference
            if($param == 'driver')
                $driver = $setting;
            
            //check the connection options
            if($param == 'persistent'){
                
                $this->_options = [
                    \PDO::ATTR_PERSISTENT => $setting,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION    
                ];
            }                
            
            //set the default mysql port if none is set
            if($driver == 'mysql' && $param == 'port' && $setting == '')
                $this->_connectionParams['port'] = '3306';
            else
                $this->_connectionParams[$param] = $setting;
        }
    }


    /**
     * A method to connect to the database
     *
     * @throws Exception
     * @return void
     */
    private function _connect(){
        
        if (empty($this->_connectionParams['driver'])) {
            throw new Exception('DB Driver is not set.');
        }
        
        $connectionString = $this->_connectionParams['driver'].':';
        $connectionParams = ['host', 'dbname', 'port', 'charset'];
        
        foreach ($connectionParams as $connectionParam) {
            
            if (!empty($this->_connectionParams[$connectionParam])) {
                $connectionString .= $connectionParam.'='.$this->_connectionParams[$connectionParam].';';
            }
        }
        
        $connectionString = rtrim($connectionString, ';');
        
        try{
            $this->pdo = new \PDO($connectionString, $this->_connectionParams['username'], $this->_connectionParams['password'], $this->_options);
        }
        catch(PDOException $e){
            throw $e->getMessage();
        }
    }
    
    /**
     * Assign the correct database handler for the respective PDO driver
     */
    private function _assignPdoBuilder(){
        
        //assign pdo to shell
        App::set('_pdo', $this->pdo);      
        
        //get the db query record
        $driver = $this->_connectionParams['driver'];
         
        //set active record into shell
        $dbrecord = $this->_getActiveDbRecord($driver);      
        App::set('_record', $dbrecord);
        
        return $this;
    }
    
    /**
     * Gets the assigned active db record according to activerecord.php
     * @param type $driver
     * @return type
     */
    private function _getActiveDbRecord($driver){
        
        $pdodriver = $this->_drivers[$driver];
        $dbal = strtolower($this->connections['dbal']);
        $records =  require DATABASE .DS. 'systems' .DS. $dbal .DS. 'config' .DS. 'activerecord.php';

        //initialize the active db record class
        $this->db_record_class = $records[$pdodriver];
        $record = App::make($this->db_record_class);
        
        return $record;
    }
    
    /**
     * Returns default PDO instance
     */
    public function getPdo(){
        return $this->pdo;
    }
    
    /**
     * Returns all the set connections
     */
    public function getConnections(){
        return $this->connections;
    }
    
    /**
     * Returns the active connection
     */
    public function getActiveConnection(){
        return $this->connections['connections'][$this->activeconn];
    }
    
    /**
     * Closes PDO connection
     */
    public function close(){
        $this->pdo = null;
    }
}
