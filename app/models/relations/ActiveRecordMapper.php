<?php
namespace Jenga\App\Models\Relations;

use Jenga\App\Models\Relations\Types\Commons;
use Jenga\App\Models\Interfaces\ObjectRelationMapperInterface;

/**
 * Basically maps the database results into an the active Record
 *
 * @author stanley
 */
class ActiveRecordMapper extends Commons {
    
    /**
     * @var Jenga\App\Models\Interfaces\ObjectRelationMapperInterface
     */
    protected $model;
    
    /**
     * These are the raw PDO results
     * @var type 
     */
    protected $dbresults;
    
    /**
     * The query object
     * @var type 
     */
    protected $query;

    public function __construct(ObjectRelationMapperInterface $model, $results = null){      
        $this->model = $model;
        $this->dbresults = $results;
    }
    
    public function handle(){
        
        foreach($this->dbresults as $results){
            
            $model = clone $this->model;
            
            //overwrite the foreign schema for each relation
            $relations = $model->record->getQueue();  
            $model->record->clearQueue();
            
            foreach($relations as $name => $relation){
                
                //clone the relation and the foreign schema
                $relation = clone $relation;
                $foreign = clone $relation->foreign;
                $relation->foreign = $foreign;
                
                //set to queue
                $model->record->queueRelations([$name => $relation]);
            }
            
            //run filter to assign columns to the respective schemas
            $props = $this->_getAllSchemaColumns($model->builder, $model->schema);
            
            //loop througn the results
            foreach(array_keys($props) as $column){
                $model->schema->{$column} = $results[$column];
            }
            
            //set the active record schema too
            $model->record->setSchema($model->schema);
            
            //set result to data
            $created_at = $model->schema->getCreationTime();
            $model->schema->data = [ $created_at => $results ];
            
            //set to array
            $mappedresults[] = $model;
        }
        
        return $mappedresults;
    }
}
