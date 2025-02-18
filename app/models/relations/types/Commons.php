<?php
namespace Jenga\App\Models\Relations\Types;

use Jenga\App\Models\Relations\Conditions;

use ReflectionObject;
use ReflectionProperty;

/**
 * Holds all the methods for use within all the relation type classes
 *
 * @author stanley
 */
abstract class Commons {
    
    /**
     * Checks if two arrays are identical
     * @param type $arrayA
     * @param type $arrayB
     * @return type
     */
    public function _identical_values( $arrayA , $arrayB ) { 

        sort( $arrayA ); 
        sort( $arrayB ); 

        return $arrayA == $arrayB; 
    } 
    
    /**
     * Filters the class properties and only brings the filtered ones
     * @param type $filter
     */
    public function _filterProps($props, $filter) {
        
        $list = [];
        foreach ($props as &$reflectProp) {
            $list[] = $reflectProp->{$filter};            
        }
        
        return $list;
    }
    
    /**
     * Compare two arrays
     * @param type $object
     * @param type $list
     * @return int
     */
    public function _runComparison($object, $list) {
        
        $same = null;
        for($i=0; $i<= count($list)-1; $i++){
            
            $identical = $this->_identical_values($object, $list[$i]);
            
            if($identical){
                $same[] = $i;
            }
        }
        
        return $same;
    }
    
    /**
     * Return schema values for each properties
     * @param type $model
     */
    public function _getSchemaPropertyValues($model){
        
        //get main schema
        $schema = clone $model->schema;

        //unset the data
        unset($schema->data);

        //reflect to get class propeties
        $schemaProps = $this->_getSchemaProperties($schema);

        $attrs = [];
        foreach ($schemaProps as $property){

            //filter public properties
            if($property->isPrivate() === FALSE){

                $property->setAccessible(TRUE);
                $value = $property->getValue($schema);
            }

            $attrs[] = $value;
        }
        
        return $attrs;
    }
    
    /**
     * Filters to assign columns to the respective schemas
     * @param type $query
     * @param type $columns
     * @param type $schemas
     */
    public function _sortSchemaAgainstResults($query, $columns, $schemas){
        
        //map schemas
        $model = $schemas[0];
        $relation = $schemas[1];
        
        //get builder
        $builder = $model->getBuilder();
        
        //get selects array
        $selects = $query['stmt']['select'];
        
        //loop through the selects and use as select the respective cols
        $write_to = [];
        foreach($selects as $select){
            list($phrase) = $select;
            
            $prefix = $model->record->getPrefix();
            
            //if phrase has table prefix then its a table
            if(strpos($phrase, $prefix) === 0){
                $phrase_sections = explode('.', str_replace($prefix, '', $phrase));
                
                //set the foreign table columns
                if(property_exists($relation, 'foreign') && $phrase_sections[0] == $relation->foreign->table){
                    
                    $column = $phrase_sections[1];
                        
                    $this->_setTableSchemaColumns($columns, $builder, $relation, [$prefix, $column], 'foreign');

                    $write_to['foreign']['to'] = $relation->foreign;
                    $write_to['foreign']['columns'] = $columns;
                }
                //set the pivot table columns
                elseif(property_exists($relation, 'pivot') && !is_null($relation->pivot)){
                    
                    if($phrase_sections[0] == $relation->pivot->table){
                        
                        $column = $phrase_sections[1];

                        $this->_setTableSchemaColumns($columns, $builder, $relation, [$prefix, $column], 'pivot');

                        $write_to['pivot']['to'] = $relation->pivot;
                        $write_to['pivot']['columns'] = $columns;
                    }
                }
                
            }
        }
        
        if(count($write_to) === 0)
            return NULL;
        
        return $write_to;
    }
    
    /**
     * Write to set schema column
     * 
     * @param type $col
     * @param type $value
     */
    protected function _writeToSchemaColumn(&$schema, $col, $value) {
        
        $schema_props = $this->_getSchemaProperties($schema);
        
        //filter props
        $props = $this->_filterProps($schema_props, 'name');
        
        //if property is public
        if(in_array($col, $props)){
            $schema->{$col} = $value;
        }
        //if not get the setter method
        elseif(property_exists($schema, $col)){
            $schema->{'set'.ucfirst($col)}($value);
        }
    }
    
    /**
     * Sets the relation columns
     * 
     * @param type $columns
     * @param type $builder
     * @param type $instance the model or relation
     * @param type $prefix_column the prefix column array
     * @param type $map_to
     */
    public function _setTableSchemaColumns(&$columns, $builder, $instance, $prefix_column, $map_to) {
        
        list($prefix, $column) = $prefix_column;
        
        //if all
        if($column == '*'){
            $columns = $this->_getAllSchemaColumns($builder, $instance->{$map_to});
        }
        //look for as keyword
        elseif(stristr($column, ' as ') !== FALSE){

            //case sensitive comparison
            if(strstr($column, ' as ') !== FALSE)
                $split = explode(' as ', $column);
            else
                $split = explode(' AS ', $column);

            //check for the 
            if(in_array($split[0], $columns)){
                $pos = array_search($split[0], $columns);
                unset($columns[$pos]);
            }

            $columns[$prefix.$split[1]] = $split[0];
        }
    }
    
    /**
     * Return all the columns mapped to the columns and properties
     * @param type $builder
     * @param type $schema
     */
    public function _getAllSchemaColumns($builder, $schema){
        
        $props = $this->_getSchemaProperties($schema);
        $schemaprops = $this->_filterProps($props, 'name');
        
        //set table
        $builder->table($schema->table);
        
        //get table columns
        $schemacols = $builder->getColumns(['Field']);
        $verifiedcols = array_intersect($schemacols, $schemaprops);
        
        //loop through the verified columns and map the columns to properties
        $cols = [];
        foreach($verifiedcols as $key => $column){
            $cols[$schemacols[$key]] = $column;
        }
        
        return $cols;
    }
    
    /**
     * Get schema properties
     * @param type $schema
     * @param type $filter
     * @return type
     */
    public function _getSchemaProperties($schema, $filter = ReflectionProperty::IS_PUBLIC){
        
        $reflectedObject = new ReflectionObject($schema);
        
        if(is_null($filter))
            return $reflectedObject->getProperties();
        else
            return $reflectedObject->getProperties($filter);
    }
    
    /**
     * A relation can only have one results set
     */
    public function _clearMergeDuplicates() {
        
        if(array_key_exists($this->current_relation_name, $this->_merged_results)){
            unset($this->_merged_results[$this->current_relation_name]);
        }
    }
    
    /**
     * Generates full schema list
     * @param type $results
     * @return type
     */
    public function _generateSchemaList($results){
        
        $list = [];
        foreach($results as $model){            
            $list[] = $this->_getSchemaPropertyValues($model);
        }
        
        return $list;
    }
    
    /**
     * Split the join condition
     * @param type $condition
     */
    public function _parseCondition($condition) {
        return Conditions::parse($condition);        
    }
    
    /**
     * Process the many to many condition
     * @param type $table_section
     * @param type $condition
     */
    public function _parseManyToManyCondition($table_section = null, $condition = null) {        
        return Conditions::parseManyToMany($table_section, $condition);
    }
}
