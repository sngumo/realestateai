<?php
namespace Jenga\App\Models\Relations\Types;

use Jenga\App\Helpers\Help;
use Jenga\App\Models\Relations\Types\Commons;
use Jenga\App\Models\Interfaces\ObjectRelationMapperInterface;

/**
 * Handles all the OneToMany functions
 *
 * @author stanley
 */
class OneToMany extends Commons {
    
    /**
     * @var Jenga\App\Models\Utilities\ObjectRelationMapper
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
        $condition = $this->_parseCondition($relation->getCondition());
        $schemas = $this->_writeToForeignSchema($relation, $condition);
        
        //demote or unset relation
        if(!is_null($schemas)){
            
            //get the linked id
            $id = $this->model->schema->{$condition['main']['col']};
            
            //save and demote
            $this->model->saveToRelationRows($name, $id, $schemas);
            $this->model->demoteActiveRelation($name);
        }
        else{
            $this->model->unsetActiveRelation($name);
        }
    }
    
    private function _writeToForeignSchema($relation, $condition){       
        
        $linked_schemas = [];
        foreach($this->dbresults as $results){
                
            //sort and assign db column to schema column
            $write_tos = $this->_sortSchemaAgainstResults($this->query, array_keys($results), [$this->model, $relation]);
            
            //start writing
            if(!is_null($write_tos)){
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

                    //move the written schema to the rows section
                    if(!is_null($write_to['to']->{$condition['foreign']['col']}))
                        $linked_schemas[] = clone $write_to['to'];
                }
            }
        }
        
        return count($linked_schemas) > 0 ? $linked_schemas : null;
    }
    
    public function map2($results) {
        
        //compare to find repeated main schemas
        $list = $this->_generateSchemaList($results);
        
        $sorted = $this->_sortOneToManyModelsBySameSchema($results, $list);
        list($linked_schemas, $linked_models, $counter, $foreign) = $sorted;
        
        //merge the linked schema into a single model
        if(count($linked_schemas) > 0){
            
            $count = 0;
            foreach($linked_schemas as $id => $schemas){
                
                //get the model
                $model = $linked_models[$id];
                
                //get the active relation name
                $relation = $model->getActiveRelations($foreign);
                
                //unset existing foreign schem
                unset($relation->rows[$id]);
                
                //and add the new one
                $relation->rows[$id] = $schemas; 
                
                //add model to results
                $pos = $counter[$count];
                Help::insert_at_index($results, $pos, $model);
                $count++;
            }
            
            //reset the results array keys
            $results = array_values($results);
            unset($linked_schemas);
        }
        
        return $results;
    }
    
    /**
     * Sort the result models into the ones with the same main schema in a one-to-meny relationship
     * 
     * @param type $results
     * @param type $list
     * @return type
     */
    private function _sortOneToManyModelsBySameSchema(&$results, $list){
        
        //loop through results to isolate recurring main schemas
        $count = 0;
        $linked_schemas = $linked_models = [];
        foreach($results as $model){
            
            //get the schema properties
            $schema_vals = $this->_getSchemaPropertyValues($model);
            
            //compare with all the existing schema in the results
            $compare = $this->_runComparison($schema_vals, $list);
            
            //get the same main schema
            if(count($compare) > 1 && in_array($count, $compare)){
                
                //get the foreign relation name and join condition
                $foreign = $this->current_relation->foreign->table;
                
                //get the schema id
                $condition = $this->_parseCondition($this->current_relation->getCondition());
                $idcol = $condition['main']['col'];
                
                //set the id
                $id = $model->schema->{$idcol};
                
                //link all the same model and schemas
                $linked_schemas[$id][] = $model->getActiveRelations($foreign)->foreign;
                $linked_models[$id] = $model;
                $counter[] = $count;
                
                //remove model from results
                unset($results[$count]);
            }
            
            $count++;
        }
        
        return [$linked_schemas, $linked_models, $counter, $foreign];
    }
}
