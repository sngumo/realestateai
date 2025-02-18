<?php
namespace Jenga\App\Models\Relations\Types;

use Jenga\App\Models\Relations\Types\Commons;
use Jenga\App\Models\Interfaces\ObjectRelationMapperInterface;

/**
 * Handles all the OneToOne functions
 *
 * @author stanley
 */
class OneToOne extends Commons {
    
    /**
     * @var Jenga\App\Models\Interfaces\ObjectRelationMapperInterface
     */
    protected $model;
    
    /**
     * @var type 
     */
    protected $builder;

    /**
     * the Query object
     * @var type 
     */
    protected $query;
    
    /**
     * @var array
     */
    protected $dbresults;
    
    public function __construct(ObjectRelationMapperInterface $model, array $dbresults) {
        
        $this->model = $model;
        $this->dbresults = $dbresults;
        $this->query = $this->model->record->getQuery()->returnStoredQuery();
    }
    
    /**
     * Performs a OneToOne mapping
     * @return type
     */
    public function map(){
        
        //get relation info
        $fullrelation = $this->model->record->getActiveRelations();
        $relation = array_values($fullrelation)[0];
        $name = array_keys($fullrelation)[0];
        
        //start mapping
        foreach($this->dbresults as $results){
            
            //sort and assign db column to schema column
            $write_tos = $this->_sortSchemaAgainstResults($this->query, array_keys($results), [$this->model, $relation]);
            
            //start writing
            foreach($write_tos as $write_to){
                
                $columns = $write_to['columns'];       
                $data = [];
                foreach($columns as $resultcol => $schemacol){ 
                    $this->_writeToSchemaColumn($write_to['to'], $schemacol, $results[$resultcol]);
                    $data[$schemacol] = $results[$resultcol];
                }         
                
                //set the creation time
                $write_to['to']->setCreationTime();
                $write_to['to']->setInitialSchemaData($data);
            }
        }
        
        //check condition
        $condition = $this->_parseCondition($fullrelation[$name]->getCondition());
        
        //demote or unset relation
        if($this->model->schema->{$condition['main']['col']} === $relation->foreign->{$condition['foreign']['col']}){
            
            //get the linked id
            $id = $this->model->schema->{$condition['main']['col']};
            
            //save and demote
            $this->model->saveToRelationRows($name, $id, [$relation->foreign]);
            $this->model->demoteActiveRelation($name);
        }
        else{
            $this->model->unsetActiveRelation($name);
        }
    }
}
