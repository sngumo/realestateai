<?php
namespace Jenga\App\Database\Systems\Pdo\Schema;

use Jenga\App\Core\App;
use Jenga\App\Project\Core\Project;
use Jenga\App\Models\Interfaces\SchemaInterface;
use Jenga\App\Models\Utilities\ObjectRelationMapper;
use Jenga\App\Models\Interfaces\ObjectRelationMapperInterface;

/**
 * This class handles the initial scaffolding tasks for schema
 * @author stanley
 */
class Tasks extends ObjectRelationMapper {
    
    /**
     * Holds the sent schema
     * @var type 
     */
    public $schema;
    
    /**
     * the actual model to perform all the tasks
     * @var ObjectRelationMapperInterface
     */
    public static $model;
    
    public static function __callStatic($name, $arguments) {
        return call_user_func_array([static::$model, $name], $arguments);
    }
    
    public function __construct(SchemaInterface $schema) {            
        $this->schema = $schema;
        self::$model = $this->defaultMapping($this);
    }
    
    /**
     * Maps resources to the default model object
     * @param type $modelobj
     * @return $this
     */
    protected function defaultMapping($modelobj){
        
        $dbal = Project::getDatabaseConnector();
        return call_user_func_array([$modelobj,'__map'], [App::get($dbal)]);
    }
    
    /**
     * Inserts multiple rows into the set schema
     * @param array $rows
     * @return boolean
     */
    public static function seed(array $rows){
        
        $errors = [];
        foreach($rows as $row){
            foreach($row as $column => $value){
                self::$model->{$column} = $value;
            }
            
            //save the created row
            self::$model->save();
            
            //check for errors
            if(self::$model->hasNoErrors() === FALSE)
                $errors = self::$model->errors();
        }
        
        if(count($errors) == 0){
            return TRUE;
        }
        else{
            return $errors;
        }
        
        return FALSE;
    }
    
    /**
     * Performs raw query on schema
     * @param type $statement
     */
    public function query($statement, $args = null){
        return self::$model->query($statement);
    }
}
