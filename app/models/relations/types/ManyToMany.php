<?php
namespace Jenga\App\Models\Relations\Types;

use Jenga\App\Helpers\Help;
use Jenga\App\Models\Relations\Types\Commons;
use Jenga\App\Models\Interfaces\ObjectRelationMapperInterface;

/**
 * Handles all the ManyToMany functions
 *
 * @author stanley
 */
class ManyToMany extends Commons {
    
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
    
    public function map(){
        
        //get relation info
        $fullrelation = $this->model->record->getActiveRelations();
        $relation = array_values($fullrelation)[0];
        $name = array_keys($fullrelation)[0];
        
        //start mapping
        $pivotlist = [];
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
            
            //save relations to rows
            $maincondition = $this->_parseManyToManyCondition('main', $relation->getCondition('plain'));            
            if($this->model->schema->{$maincondition['main']['col']} === $relation->pivot->{$maincondition['pivot']['col']}){
                
                //get ids
                $schemaid = $this->model->schema->{$maincondition['main']['col']};  
                
                //get the condition for the linked foreign table
                $foreigncond = $this->_parseManyToManyCondition('foreign', $relation->getCondition('plain'));
                $foreignid = $relation->foreign->{$foreigncond['foreign']['col']};
                
                //first add foreign schema to pivot
                if($relation->pivot->{$foreigncond['pivot']['col']} == $relation->foreign->{$foreigncond['foreign']['col']}){
                    
                    $relation->pivot->rows[$foreignid] = clone $relation->foreign;
                    $pivotlist[$relation->foreign->{$foreigncond['foreign']['col']}] = clone $relation->pivot;
                }
                
                //reset the pivot rows
                unset($relation->pivot->rows);
            }         
        }
        
        //unset the pivot schema in the relation
        if(count($pivotlist) > 0){
            
            $this->model->pointRelationPivotToRows($name, array_keys($pivotlist));

            //save and demote the new relations
            $this->model->saveToRelationRows($name, $schemaid, $pivotlist);
            $this->model->demoteActiveRelation($name);
        }
        
        return $this->model;
    }
}
