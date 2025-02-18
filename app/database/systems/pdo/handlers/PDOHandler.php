<?php
namespace Jenga\App\Database\Systems\Pdo\Handlers;

use PDO;
use PDOException;

use Jenga\App\Helpers\Help;

/**
 * This class is the initial wrapper class for the PDO
 *
 * @author stanley
 */
class PDOHandler {
    
    public $stmt;
    public $pdo;
    public $errorlog = [];
    
    /**
     * @Inject({"_pdo"}) 
     * @param object $_pdo
     */
    public function __construct(PDO $_pdo){

        //assign pdo instance
        $this->pdo = $_pdo;
    }
    
    /**
     * This allows the PDO instance to be called directly
     * @param type $name
     * @param type $arguments
     * @return type
     */
    public function __call($name, $arguments) {
        return call_user_func_array([$this->pdo, $name], $arguments);
    }
    
    /**
     * Returns the actual PDO instance
     * @return PDO The PDO object
     */
    public function getPdo(){
        return $this->pdo;
    }
    
    /**
     * Assign query to PDO handle
     * @param type $query
     */
    public function query($query){        
        
        $this->stmt = $this->pdo->prepare($query);
        
        if(!$this->stmt)
            $this->errorlog[] = $this->stmt->errorInfo ();            
    }
    
    /**
     * Bind the inputs with the placeholders
     * 
     * @param type $param
     * @param type $value
     * @param type $type
     */
    public function bind($param, $value, $type = null) {
        
        $pdotype = $this->_determineTypeFromValue($value, $type);

        //add the 1-index position
        if(is_int($param)) {
            $param = $param + 1;
        }
        
        $this->stmt->bindValue($param, $value, $pdotype);
    }
    
    /**
     * Bind the inputs with the named placeholders
     * 
     * @param type $param
     * @param type $value
     * @param type $type
     */
    public function bindParam($param, $value, $type = null){
        
        $pdotype = $this->_determineTypeFromValue($value, $type);        
        $this->stmt->bindParam($param, $value, $pdotype);
    }
    
    /**
     * Determine type from sent value
     * @param type $value
     * @param type $type
     * @return type
     */
    private function _determineTypeFromValue($value, $type){
        
        if(is_null($type)){
            
            switch (TRUE) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;

                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                
                case is_double($value):
                case is_float($value):
                    $type = PDO::PARAM_INT;
                    break;
                
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        return $type;
    }
    
    /**
     * Execute the prepared query statement
     */
    public function execute(){
        
        try {
            
            $this->stmt->execute();
            return TRUE;
        } 
        catch (\Exception $ex) {
            $this->errorlog[] = $ex->getMessage();
        }        
    }
    
    /**
     * Return result set as array
     */
    public function fetchAll() {
        
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Returns single result
     */
    public function fetchOne(){
        
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function fetchObject(){
        
        $this->execute();
        return $this->stmt->fetchObject();
    }
    
    /**
     * Return the row count
     * @return type
     */
    public function count(){
        return $this->stmt->rowCount();
    }
    
    /**
     * Get the last insert id
     * 
     * @param type $name
     * @return type
     */
    public function insertId($name = null){
        return $this->pdo->lastInsertId($name);
    }
    
    /**
     * Start database transaction
     */
    public function startTransaction() {
        $this->pdo->beginTransaction();
    }
    
    /**
     * Checks if there's an ongoing transaction
     * @return type
     */
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }
    
    /**
     * End database transaction
     */
    public function commitTransaction() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback all changes linked to transaction
     */
    public function cancelTransaction() {
        $this->pdo->rollBack();
    }
    
    /**
     * Dump an SQL prepared command
     */
    public function dumpQueryParams(){
        return $this->stmt->debugDumpParams();
    }
    
    /**
     * Executes the raw query whether SQL or PDO
     * @param type $stmt
     * @param type $args
     * @return type
     */
    public function rawQuery($stmt, $args = null) {
        
        //if proper SQL
        if (is_null($args)){
             return $this->pdo->query($stmt, PDO::FETCH_ASSOC);
        }
             
        //prepare query
        $this->query($stmt);
        
        //process args
        if(Help::isAssoc($args)){
            
            //if the placeholders are named
            foreach ($args as $param => $value) {
                $this->bind($param, $value);
            }
        }
        else{
            
            //if the placeholder are question marks
            $count = 0;            
            foreach ($args as $value) {
                
                $this->bind($count, $value);
                $count++;
            }
        }
        
        $this->execute();
        return $this->fetchAll();
    }
}
