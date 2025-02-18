<?php
namespace Jenga\App\Models\Utilities;

use Jenga\App\Core\App;
use Jenga\App\Request\Url;
use Jenga\App\Helpers\Help;
use Jenga\App\Models\Relations\ResultsMapper;

/**
 * This class will serve as the main collections point to the model
 */
class Collector {
    
    /**
     * The fully rendered rows
     * @var type 
     */
    public $rows = [];
    
    /**
     * Output results as
     * @var type 
     */
    public $output = 'entity';
    
    /**
     * Sets the available output formats
     * @var type 
     */
    private $_output_formats = ['entity','array'];
    
    /**
     * Indicates when a query is run directly
     * @var boolean
     */
    private $_direct = FALSE;
    
    /**
     * All database results re assigned to this
     * @var type 
     */
    private $_results;
    
    /**
     * This is the model object instance to be replicated
     * @var type 
     */
    protected $model;
    
    /**
     * Prevent default mapping before other operations are done
     * @var type 
     */
    protected $defer_mapping = false;
    
    /**
     * @var Paginator
     */
    protected $paginator = null;
    
    /**
     * Holds the isNew value during deferment
     * @var type 
     */
    private $_isNew;
    
    /**
     * The deferred results to be manipulated
     * @var type 
     */
    private $_deferredResults = null;
    
    /**
     * 
     * @param type $model
     * @param type $results
     */
    public function __construct($model, $results = []) {       
        
        $this->model = $model;
        
        //disable eager loading
        $this->model->isLazy();
        
        //map to results
        $this->_results = $results;
    }
    
    /**
     * Call the collected functions not in the collector
     * @param type $name
     * @param type $arguments
     */
    public function __call($name, $arguments) {
        
        //see if function is in paginator
        if(!is_null($this->paginator) && method_exists($this->paginator, $name)){
            return call_user_func_array([$this->paginator, $name], $arguments);
        }
    }
    
    /**
     * Encapsulate results into array
     * @return type
     */
    private function _setResults(){
        
        if(is_null($this->_deferredResults)){
            
            //if plain db result object, encapsulate in array
            if(Help::isAssoc($this->_results)){
                $results = [$this->_results];
            }        
            else{
                $results = $this->_results;
            }
        }
        else{
            $results = $this->_deferredResults;
        }
        
        return $results;
    }
    
    /**
     * Set defer mapping flag
     * @param type $map
     */
    public function setDeferMapping($map = true){
        $this->_deferredResults = $this->_setResults();
        $this->defer_mapping = $map;
    }
    
    /**
     * Assign results to ActiveModel
     * @param type $isNew
     * @return $this
     */
    public function assignOutputToModel($isNew = FALSE) {
        
        $results = $this->_setResults();
        
        //check for mapped relations
        if($this->model->hasQueuedRelations()){
            
            //run the ResultsMapper
            $this->model->isNew($isNew);
            $mappings = App::make(ResultsMapper::class, [
                                'model' => $this->model,
                                'dbresults' => $results
                            ]);
            
            $this->rows = $mappings->returnBasicMap();
        }
        //or else just continue
        else{
            
            //Assign results to ActiveModel
            foreach ($results as $result){

                if($this->output == 'array'){
                    $this->rows[] = $result;
                }
                elseif($this->output == 'entity'){
                    $model = clone $this->model;

                    //interate through reults
                    foreach ($result as $column => $data) {

                        //assign value to model
                        $model = $this->_assignResultsToModel($model, $column, $data);

                        //change the isNew flag
                        $model->isNew($isNew);
                    }

                    //set result to data
                    $created_at = $model->schema->getCreationTime();
                    $model->schema->data = [ $created_at => $result ];

                    //set schema back into active model :-(
                    $model->setSchema($model->schema);
                    
                    //set the results to rows
                    $this->rows[] = $model;
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Assign the db results to the Active Model
     * @param type $model
     * @param type $column
     * @param type $data
     * @return type
     */
    private function _assignResultsToModel($model, $column, $data){
        
        //assign value to model
        $model->{$column} = $data;     
        return $model;
    }
    
    /**
     * Returns the assigned rows
     */
    public function getRows(){
        return $this->rows;
    }
    
    /**
     * Returns specifically listed rows count positions from queried results
     * @param type $pointers the row number to be extracted count from 0
     * @return type
     */
    public function pluck($pointers = null){
        
        if(!is_null($pointers)){
            
            if(is_int($pointers)){
                
                $results = $this->_deferredResults;
                
                //set the pointed result into the deferred variable
                $this->_deferredResults = $results[$pointers];
                $this->closeDefferment();
                
                //return first row
                return $this->rows[0];
            }
            elseif(is_array($pointers)){

                $results = $this->_deferredResults;
                
                $list = [];
                foreach ($pointers as $pointer) {
                    $list[$pointer] = $results[$pointer];
                }
                
                //set the pointed result into the deferred variable
                $this->_deferredResults = $list;
                $this->closeDefferment();
                
                return $this->rows;
            }
            return NULL;
        }
        
        return $this->rows;
    }
    
    /**
     * Returns the results count
     * @return type
     */
    public function count(){
        return count($this->_deferredResults);
    }
    
    /**
     * Sets the output format to be returned
     * @param text $format Options are entity, array, object
     */
    public function outputAs($format){
        
        if(in_array($format, $this->_output_formats)){
            $this->output = $format;
        }
        
        return $this;
    }
    
    /**
     * Close the deferment process
     */
    protected function closeDefferment(){
        $this->assignOutputToModel();
    }
}