<?php
namespace Jenga\App\Html\Tables\Components;

/**
 * Holds the data and attributes for each cell
 *
 * @author stanley
 */
class Cell {
    
    /**
     * The actual data to display
     * @var type 
     */
    protected  $data;
    
    /**
     * The attributes to add to each cell
     * @var type 
     */
    protected  $attrs;
    
    /**
     * Holds the grid behaviors
     * @var type 
     */
    protected $grid;

    /**
     * If cell should be hidden from display
     * @var type 
     */
    public $isHidden = false;

    /**
     * For evaluation as a column
     * @var type 
     */
    protected $isColumnCell;
    
    /**
     * The column name linked to cell
     * @var type 
     */
    protected $columnName = null;

    public function __construct($data = null, $attrs = null, $isColumnCell = false) {
        
        $this->data = $data;
        $this->isColumnCell = $isColumnCell;
        
        //check if hidden
        if(!is_null($attrs) && array_key_exists('hidden', $attrs)){
            $this->isHidden = $attrs['hidden'];
            unset($attrs['hidden']);
        }
        
        $this->setAttributes($attrs);
    }
    
    /**
     * Check if cell has a set column name
     */
    public function hasColumnName() {
        return ! is_null($this->columnName);
    }
    
    /**
     * Returns the cell data
     * @return type
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Sets data into cell
     * @param type $data
     */
    public function setData($data){
        $this->data = $data;
    }
    
    /**
     * Returns the cell attributes
     * @return type
     */
    public function getAttributes(){
        return $this->attrs;
    }
    
    /**
     * Flag to indicate column
     * @return type
     */
    public function isColumn(){
        return $this->isColumnCell;
    }
    
    /**
     * Sets the linked column name
     * @param type $name
     * @return $this
     */
    public function setColumnName($name){
        $this->columnName = $name;
        return $this;
    }
    
    /**
     * Returns the cell column name
     * @return type
     */
    public function getColumnName(){
        return $this->columnName;
    }
    
    /**
     * Sets the cell attributes
     * @param type $attrs
     */
    public function setAttributes($attrs){
        $this->attrs = $attrs;
    }
    
    /**
     * Sets the grid behaviors
     * @param array $grid_behaviours
     */
    public function setGrid(array $grid_behaviours){
        $this->grid = $grid_behaviours;
    }
    
    /**
     * Returns the grid classes
     * @return type
     */
    public function getGrid(){
        return $this->grid;
    }
}
