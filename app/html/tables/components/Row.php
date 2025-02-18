<?php
namespace Jenga\App\Html\Tables\Components;

use Jenga\App\Html\Table;
use Jenga\App\Html\Tables\Components\Cell;
use Jenga\App\Html\Tables\Components\Column;

/**
 * Holds the row information
 *
 * @author stanley
 */
class Row {
    
    /**
     * The columns information for each row
     * @var type 
     */
    protected $columns = null;
    
    /**
     * The row attributes
     * @var type 
     */
    protected $attrs;
    
    /**
     * The cells for the row
     * @var type 
     */
    protected $cells = [];

    /**
     * Indicates if row is header
     * @var boolean
     */
    public $isHeader = false;
    
    /**
     * Indicates if row is footer
     * @var type 
     */
    public $isFooter = false;
    
    /**
     * Indicates if row has child
     * @var type 
     */
    public $hasChild = false;
    
    /**
     * The child row
     * @var type 
     */
    protected $child;
    
    /**
     * Holds the shortcuts menu
     * @var type 
     */
    protected $shortcuts = null;
    
    /**
     * Add row id
     * @var type 
     */
    protected $id;

    public function __construct($attrs = null, $isHeader = false, $isFooter = false) {
        $this->attrs = $attrs;
        $this->isHeader = $isHeader;
        $this->isFooter = $isFooter;
    }
    
    /**
     * Sets the row id
     * @param type $id
     */
    public function setId($id){
        $this->id = $id;
    }
    
    /**
     * Returns row id
     * @return type
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Get row attributes
     * @return type
     */
    public function getAttributes(){
        return $this->attrs;
    }
    
    /**
     * Bind column to row
     */
    public function bindColumn(Column $columns){
        $this->columns = $columns;
    }
    
    /**
     * Bind the cells into the row
     * @param array $cells
     */
    public function bindCells(array $cells, $columns = null){
        
        $count = 0;
        foreach($cells as $cell){
            
            if(!$cell instanceof Cell) {
                $cell = new Cell($cell);
                
                //get column attributes and add to cell
                if(!is_null($columns)){
                    
                    if(array_key_exists($count, $columns)){
                        $attrs = $columns[$count]->getAttributes();

                        if(!is_null($attrs) && count($attrs) > 0){
                            $cell->setAttributes($attrs);
                        }
                        elseif($columns[$count]->isHidden){
                            $cell->isHidden = true;
                        }
                    }
                }
            }
            
            //add column name
            if(!is_null($columns)){
                $col = $columns[$count];
                
                //if(!is_null($col)){
                    $cell->setColumnName(strip_tags($col->getData()));
                //}
            }
            
            $this->cells[] = $cell;
            $count++;
        }
        
        return $this;
    }
    
    /**
     * Get row cells
     * @return array Jenga\App\Html\Tables\Cell
     */
    public function getCells(){
        return $this->cells;
    }
    
    /**
     * Add new column cell
     * @param type $data
     * @return type
     */
    public static function cell($data, $attributes = null) {        
        return new Cell($data, $attributes);
    }
    
    /**
     * Indicates that row has children
     * @param type $flag
     */
    public function hasChild($flag){        
        $this->hasChild = $flag;
    }
    
    /**
     * Add child rows
     * 
     * @param type $row
     * @return $this
     */
    public function addChild($row){  
        
        $this->hasChild = true;
        $this->children[] = $row;
        
        return $this;
    }
    
    /**
     * Return row children
     */
    public function getChildren(){
        return $this->children;
    }
    
    /**
     * Adds shortcut menu links
     * @param type $column
     * @param type $list
     * @return $this
     */
    public function addShortcutMenu($column, $list, $is_child = false, $parentcount = 0, $title = null, $divider = true){
        
        $this->shortcuts['column'] = $column;
        $this->shortcuts['list'] = $list;
        
        if(!is_null($title)){
            $this->shortcuts['title'] = $title;
        }
        
        if($is_child){
            $this->shortcuts['child'] = true;
            $this->shortcuts['count'] = $parentcount;
        }
        
        //add divider
        if($divider === FALSE){
            $this->shortcuts['omit_divider'] = true;
        }
        
        return $this;
    }
    
    /**
     * Returns the embedded shortcuts
     * @return type
     */
    public function getShortcuts($tablename, $rowid){
        
        if(!is_null($this->shortcuts)){
            
            //create handle
            $listname = 'shortcutMenu-'.$tablename.'-row-'
                    .(array_key_exists('count',$this->shortcuts) ? $this->shortcuts['count'] : $rowid)
                    .(array_key_exists('child', $this->shortcuts) ? '-child-row-'.$rowid : '');
            
            if(!array_key_exists('child', $this->shortcuts)){                
                $shortcuts['handle'] = '<div class="pull-left" env="desktop" style="margin-right: 10px;">'
                                            . '<i class="shortcut-menu fa fa-bars fa-lg" data-linked-to="#'.$listname.'" aria-hidden="true"></i>'
                                        . '</div>'
                                    . '<div class="pull-left" env="mobile">'
                                            . '<i class="shortcut-menu fa fa-ellipsis-v fa-lg" data-linked-to="#'.$listname.'" aria-hidden="true"></i>'
                                        . '</div>';
            }
            else{                
                $shortcuts['handle'] = '<div class="pull-left" env="desktop" style="margin-right: 10px; margin-left: 15px;">'
                                            . '<i class="child-shortcut-menu fa fa-bars fa-lg" data-linked-to="#'.$listname.'" aria-hidden="true"></i>'
                                        . '</div>'
                                    . '<div class="pull-left" env="mobile">'
                                            . '<i class="shortcut-menu fa fa-ellipsis-v fa-lg" data-linked-to="#'.$listname.'" aria-hidden="true"></i>'
                                        . '</div>';
            }
            
            //build menu
            $count = 0;
            $menu = '<ul id="'.$listname.'" class="dropdown-menu dropdown-menu-list" role="shortcutmenu" style="display: none;">';
            
            //check shortcut title
            if(array_key_exists('title', $this->shortcuts)){
                $menu .= '<li class="dropdown-header" env="mobile">'.$this->shortcuts['title'].'</li>';
            }
            
            //build menu list
            foreach($this->shortcuts['list'] as $link){
                
                if($count > 0 && !array_key_exists('omit_divider', $this->shortcuts)){
                    $menu .= '<li class="divider"></li>';
                }
                
                //for manually added dividers
                if($link == Table::$DIVIDER){
                    $menu .= '<li class="divider"></li>';
                }
                else{
                    $menu .= '<li>'.$link.'</li>';
                }
                
                $count++;
            }            
            
            $menu .= '</ul>';
            
            //add menu to shortcut
            $shortcuts['menu'] = $menu;
            
            //add column
            $shortcuts['column'] = $this->shortcuts['column'];
            
            return $shortcuts;
        }
        
        return NULL;
    }
    
    /**
     * Has shortcut menus
     * @return type
     */
    public function hasShortcuts() {
        return !is_null($this->shortcuts);
    }
}
