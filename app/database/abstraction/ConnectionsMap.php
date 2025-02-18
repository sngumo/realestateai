<?php
namespace Jenga\App\Database\Abstraction;

use Jenga\App\Core\App;

use Jenga\App\Models\Utilities\ObjectRelationMapper;
use Jenga\App\Models\Interfaces\ActiveRecordInterface;
use Jenga\App\Database\Systems\Pdo\Connections\DatabaseConnector;

/**
 * Database connection mapping
 *
 * @category  Database Schema Access
 * @package   Database
 * @author    Stanley Ngumo
 * 
 * @property-read ActiveRecordInterface $record
 * 
 **/
abstract class ConnectionsMap {

    /**
     * Uncommitted instance of the DBase class for static use
     * @var Jenga\App\Database\Systems\Pdo\DBase
     */
    protected static $instance;
    
    /**
     * Map database connection
     */
    public function __map(DatabaseConnector $connector){
        
        //assign the active connection
        $this->activeconnection = $connector::connect($this->connection);
        
        //assign the query record
        return call_user_func_array([$this, '__setDefaultMapping'],[App::get('_record')]);
    }
    
    /**
     * Set up all the fundamental mapping required for operations
     * @param type $_record
     */
    private function __setDefaultMapping(ActiveRecordInterface $_record) {
        
        //map active record
        $this->boot($this->schema, $_record);
        
        //get the prefix
        $active_conn_name = $this->activeconnection->activeconn;
        $prefix = $this->activeconnection->connections['connections'][$active_conn_name]['prefix'];

        //set the prefix and table
        $this->setPrefix($prefix);
        
        //set the schema builder
        $aconn = $this->activeconnection->getActiveConnection();
        $this->setBuilder($aconn['driver']);
        
        //set instance before table name and schema are set
        self::$instance = $this;
        
        //set the table name
        $this->setTable($this->schema->table);
        
        return $this;
    }
}
