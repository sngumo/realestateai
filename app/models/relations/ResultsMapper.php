<?php
namespace Jenga\App\Models\Relations;

use Jenga\App\Core\App;
use Jenga\App\Models\Relations\ActiveRecordMapper;
use Jenga\App\Models\Interfaces\ObjectRelationMapperInterface;

/**
 * This class will perform all the various mappings mapped to the model via foreign key relations
 *
 * @author stanley
 */
class ResultsMapper {
    
    /**
     * This is the standard schema model class
     * @var Jenga\App\Models\Interfaces\ObjectRelationMapperInterface
     */
    protected $model;
    
    /**
     * These are the raw PDO results
     * @var type 
     */
    protected $dbresults;
    
    /**
     * The join condition
     * @var type 
     */
    protected $condition;
    
    /**
     * Sets the active relation
     * @var Relationships
     */
    protected $active_relations = null;
    
    /**
     * Sets the queued relation
     * @var Relationships
     */
    protected $relations = null;
    
    /**
     * This is the current relation being modified
     * @var type 
     */
    protected $current_relation = null;
    
    /**
     * Just the current relation name
     * @var type 
     */
    protected $current_relation_name = null;

    /**
     * The fully mapped relations
     * @var type 
     */
    protected $fully_mapped_relations = null;
    
    /**
     * The first one-to-one results
     * @var type 
     */
    protected $initial_results = null;
    
    /**
     * The query object that was executed
     * @var type 
     */
    protected $query;
    
    /**
     * Holds the ActiveRecordMapper
     * @var type 
     */
    protected $map;

    public function __construct(ObjectRelationMapperInterface $model, $dbresults) {
        
        $this->model = $model;
        $this->dbresults = $dbresults;
        $this->relations = $this->model->record->getQueue();        
        
        //set the builder
        $this->model->builder->table($this->model->schema->table);
        
        //start mapping
        $this->startBasicMapping();
    }
    
    /**
     * Map main schema to database results
     */
    public function startBasicMapping(){   
        $this->map = App::make(ActiveRecordMapper::class, ['model'=>$this->model, 'results'=> $this->dbresults]);
    }
    
    /**
     * Returns the basic map
     * @return type
     */
    public function returnBasicMap(){
        return $this->map->handle();
    }
}