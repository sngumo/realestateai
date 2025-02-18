<?php
namespace Jenga\App\Html;

use Jenga\App\Views\HTML;
use Jenga\App\Request\Url;
use Jenga\App\Helpers\Help;
use Jenga\App\Request\Input;
use Jenga\App\Request\Facade\Sanitize;
use Jenga\App\Models\Utilities\ObjectRelationMapper;

use Jenga\App\Html\Tables\Components\Row;
use Jenga\App\Html\Tables\Components\Cell;

/**
 * Builds table using Bootstrap Grid divs for ultimate flexibility
 *
 * @author stanley
 */
class Table {
    
    public static $DIVIDER = 'DIVIDER';
    
    /**
     * The table name
     * @var type 
     */
    protected $name;
    
    /**
     * The table attributes
     * @var type 
     */
    protected $attrs;
    
    /**
     * the table columns
     * @var type 
     */
    protected $columns = [];

    /**
     * The table rows
     * @var array Jenga\App\Html\Tables\Row
     */
    protected $rows = [];
    
    /**
     * Orientation for main table
     * @var type 
     */
    protected $orientation = null;
    
    /**
     * The table row would be selectable
     * @var type 
     */
    protected $selectable = false;
    
    /**
     * Orientation for child rows
     * @var type 
     */
    protected $suborientation = null;
    
    /**
     * Flag if js resources are attached
     * @var type 
     */
    protected $js_attached = false;
    
    /**
     * Trigger for js shortcut menu
     * @var type 
     */
    protected $js_trigger = null;
    
    /**
     * Holds the shortcuts
     * @var type 
     */
    protected $js_shortcuts = [];

    /**
     * The ordering column
     * @var type 
     */
    protected $orderBy;
    
    /**
     * Tools to be applied to table
     * @var type 
     */
    protected $tools = [];
    
    /**
     * Holds the ordering column
     * @var type 
     */
    private $_orderColumn;
    
    /**
     * Holds the ordering direction
     * @var type 
     */
    private $_orderDirection;
    
    /**
     * Flag to indicate rows with children
     * @var type 
     */
    private $_has_children = true;
    
    /**
     * Show child rows or not 
     * @var type 
     */
    private $_show_children_onload = true;
    
    /**
     * Set rows per page
     * @var type 
     */
    private $_rows_per_page = null;
    
    /**
     * Paginate from model instance
     * @var type 
     */
    private $_paginate_from_model = false;
    
    /**
     * @param type $name
     * @param type $attributes
     */
    public function __construct($name, $attributes = null){        
        $this->name = $name;
        $this->attrs = $attributes;
    }
    
    /**
     * Adds new column
     * @param type $data
     * @param type $attrbutes
     */
    public function addColumn($data, $attrbutes = null){
        return $this;
    }
    
    /**
     * Adds the header row
     * @param type $columns
     * @param type $attrs
     * @return $this
     */
    public function addHeaderRow($columns, $attrs = null){
        
        //add columnrow class
        if(!is_null($attrs) && array_key_exists('class', $attrs)){
            $class = $attrs['class'];
            $attrs['class'] = $class.' columnrow';
        }
        else{
            $attrs['class'] = 'columnrow';
        }
        
        //create row
        $row = new Row($attrs, TRUE);
        
        //bind cells
        $this->rows[] = $row->bindCells($columns);
        $this->columns = $row->getCells();
        
        return $this;
    }
    
    /**
     * Adds a normal row
     * @param type $cells
     * @param type $attributes
     * @return $this
     */
    public function addRow($cells, $attributes = null){
        $row = new Row($attributes); 
        $this->rows[] = $row->bindCells($cells, $this->columns);
        
        return $this;
    }
    
    /**
     * Add rows per page
     * @param type $rows
     * @return $this
     */
    public function addRowsPerPage($rows){
        $this->_rows_per_page = $rows;
        return $this;
    }
    
    /**
     * Create pagination links from the model
     * @param ObjectRelationMapper $model
     */
    public function paginateFromModel(ObjectRelationMapper $model){
        $this->_paginate_from_model = $model->pages()->getPages();
    }
    
    /**
     * Add the new columns for the child row
     * @param type $columns
     * @param type $attributes
     * @return $this
     */
    public function addChildColumns($columns, $attributes = null){
        
        $this->suborientation = 'horizontal';
        
        //create row
        $row = new Row($attributes, TRUE);
        $row->bindCells($columns);
        
        //set child setting
        $last = end($this->rows);
        $last->addChild($row);        
        
        return $this;
    }
    
    /**
     * Show child rows
     * @param type $flag
     * @return $this
     */
    public function showChildRowsOnLoad($flag = true){
        $this->_show_children_onload = $flag;
        return $this;
    }
    
    /**
     * Adds a child row to the last chained row
     * 
     * @param type $cells
     * @param type $attributes
     * @param type $attachshortcut
     * @return $this
     */
    public function addChildRow($cells, $attributes = null){
        
        //check if orientation is horizontal or vertical
        if(Help::isAssoc($cells) && is_null($this->suborientation)){
            $this->suborientation = 'vertical';
        }
        elseif(Help::isAssoc($cells) === FALSE && is_null($this->suborientation)){
            $this->suborientation = 'horizontal';
        }
        
        //create row
        $row = new Row($attributes);
        $row->bindCells($cells);
        
        //bind to parent
        $last = end($this->rows);
        $last->addChild($row);
        
        return $this;
    }
    
    /**
     * Attaches shortcut menu to linked row
     * @param type $column this is the column number to attach the menu to, starts from zero
     * @param type $list
     * @param type $trigger
     */
    public function attachShortcuts($column = 0, $list = [], $trigger = null, $menutitle = null, $divider = true){
        
        //add shortcut menu
        if($this->js_attached === FALSE){
            
            //add trigger
            $this->tools['shortcuts'] = true;
            $this->js_shortcuts['trigger'] = (is_null($trigger) ? 'mouseenter click' : $trigger);
            
            //change flag 
            $this->js_attached = TRUE;
        }
        
        //bind shortcut menu
        $row = end($this->rows);
        $row->addShortcutMenu($column, $list, false, 0, $menutitle, $divider);
        
        return $this;
    }
    
    /**
     * Attach child shortcut menu
     * @param type $column
     * @param type $list
     * @param type $trigger
     */
    public function attachChildMenu($column = 0, $list, $trigger = null){
        
        //bind to most recent child row
        $last = end($this->rows);
        
        $children = $last->getChildren();
        $child = end($children);
        
        $child->addShortcutMenu($column, $list, TRUE, count($this->rows));
        return $this;
    }
    
    /**
     * Adds footer row
     * @param type $cells
     * @return $this
     */
    public function addFooterRow($cells, $attrs = null) {
        
        //add footerrow class
        if(!is_null($attrs) && array_key_exists('class', $attrs)){
            $class = $attrs['class'];
            $attrs['class'] = $class.' footerrow';
        }
        else{
            $attrs['class'] = 'footerrow';
        }
        
        $row = new Row($attrs, FALSE, TRUE);
        $this->rows[] = $row->bindCells($cells);
        
        return $this;
    }
    
    /**
     * Attach tools for batch operations on the table
     * @param array $tools
     */
    public function attachBatchTools(array $tools){ 
        
        //set table to be selectable
        $this->selectable = TRUE;
        
        //loop to get the attached cell
        foreach($tools as $name ){
            $this->tools['bar']['tools'][$name] = '#'.$this->name.'-'.$name;
        }
        
        return $this;
    }
    
    /**
     * The column cell to order the table
     * @param Cell $cell
     * @param type $direction
     * @return $this
     */
    public function orderBy(Cell $cell, $direction = 'desc'){
        
        //set colpos and order
        $this->_orderColumn = $cell;
        $this->_orderDirection = $direction;
        
        //get and remove the header and footer
        $headfoot = $this->_filterHeaderFooterRows();        
        if(count($headfoot) > 0){
            
            //unset header
            if(array_key_exists('header', $headfoot)){
                unset($this->rows[$headfoot['header']['count']]);
            }
            
            //unset footer
            if(array_key_exists('footer', $headfoot)){
                unset($this->rows[$headfoot['footer']['count']]);
            }
        }
        
        //sort $this->rows
        usort($this->rows, [$this, '_compareRows']);
        
        //reattach header and footer       
        if(count($headfoot) > 0){
            
            //attach header
            if(array_key_exists('header', $headfoot)){
                $row = $this->_addOrderIndicators($headfoot['header']['row']);
                array_unshift($this->rows, $row);
            }
            //attach footer
            if(array_key_exists('footer', $headfoot)){
                array_push($this->rows, $headfoot['footer']['row']);
            }
        }
        
        return $this;
    }
    
    /**
     * Add the order arrow to the column cell
     * @param Row $row
     * @return type
     */
    private function _addOrderIndicators($row){
        
        $cells = $row->getCells();
        
        foreach($cells as $cell){
            
            $a_str = strip_tags($cell->getData());
            $b_str = strip_tags($this->_orderColumn->getData());                
            if(strcasecmp($a_str, $b_str) == 0){
                
                //add ordering data to cell
                $data = $cell->getData();

                $orderhtml = '<div class="pull-right">';                    
                if($this->_orderDirection == 'desc' || $this->_orderDirection == 'DESC'){
                    $orderhtml .= '<img src="'.RELATIVE_APP_HTML_PATH.'/tables/img/sort_desc.png">';
                }
                elseif($this->_orderDirection == 'asc' || $this->_orderDirection == 'ASC'){
                    $orderhtml .= '<img src="'.RELATIVE_APP_HTML_PATH.'/tables/img/sort_asc.png">';
                }
                $orderhtml .= '</div>';

                $cell->setData($data.$orderhtml);  
            }
        }
        
        return $row;
    }
    
    private function _filterHeaderFooterRows(){
        
        $count = 0;
        $handle = [];
        foreach($this->rows as $row){
            
            if($row->isHeader){
                $handle['header']['row'] = $row;
                $handle['header']['count'] = $count;
            }
            elseif($row->isFooter){
                $handle['footer']['row'] = $row;
                $handle['footer']['count'] = $count;
            }
            
            $count++;
        }
        
        return $handle;
    }


    /**
     * Compare the a and b rows
     * @param Row $a
     * @param Row $b
     */
    private function _compareRows($a, $b){
        
        //filter header and footer rows
        if(($a->isHeader == FALSE && $a->isFooter == FALSE)
                && ($b->isHeader == FALSE && $b->isFooter == FALSE)){
            
            $a_cells = $a->getCells();
            $b_cells = $b->getCells();

            //get the correct cell position
            $count = $cellpos = 0;
            foreach($a_cells as $cell){

                $a_str = strip_tags($cell->getColumnName());
                $b_str = strip_tags($this->_orderColumn->getData());                
                if(strcasecmp($a_str, $b_str) == 0){
                    $cellpos = $count;                  
                    break;
                }
                $count++;
            }

            //if equal return 0
            if(strip_tags($a_cells[$cellpos]->getData()) == strip_tags($b_cells[$cellpos]->getData())){
                return 0;
            }

            //determine by direction
            if($this->_orderDirection == 'asc'){
                return strip_tags($a_cells[$cellpos]->getData()) > strip_tags($b_cells[$cellpos]->getData()) ? 1 : -1;
            }
            elseif($this->_orderDirection == 'desc'){
                return strip_tags($a_cells[$cellpos]->getData()) < strip_tags($b_cells[$cellpos]->getData()) ? 1 : -1;
            }
        }
    }
    
    /**
     * Renders the created table
     * @param type $return 
     * @return type $attachdependencies
     */
    public function render($return = true, $attachdependencies = false){  
        
        if($attachdependencies == false){
            
            //register grid tools
            HTML::register('gridTableTools');  
            
            //register grid table css
            HTML::register('<link href="'.RELATIVE_APP_PATH.'/html/tables/styles/gridtable.css" rel="stylesheet" />');
        }
        
        $initscript = '$(document).ready( function () {'
                    . '$("#'.$this->name.'").gridTable({';
                    
        //add children
        if($this->_has_children){
            $initscript .= 'siblings: {
                                show: '. ($this->_show_children_onload ? 'true' : 'false').'
                            },';
        }
        
        //add selectable 
        if($this->selectable){
            
            $list = '';
            $tools = $this->tools['bar']['tools'];
            
            foreach($tools as $name => $tool){
                
                $list .= $name.': {
                        id: "'.$tool.'",
                        action: "compile-and-confirm",
                        trigger: "click"
                    },';
            }
            $toolslist = '{'.rtrim($list, ',').'}';
            
            $initscript .= ' selectable: { 
                                attachto: "div.field-row",
                                tools: '.$toolslist.'
                            },';
            unset($toolslist);
        }
        
        //add order by
        if(array_key_exists('orderBy', $this->tools)){
            $initscript .= ' orderby: { 
                                column: "'.$this->tools['orderBy'][0].'",
                                direction: "'.$this->tools['orderBy'][1].'"
                            },';
        }
        
        //add shortcuts
        if(array_key_exists('shortcuts', $this->tools)){
            $initscript .= ' shortcuts: {
                                    menuhandle: "i.shortcut-menu",
                                    childmenuhandle: "i.child-shortcut-menu",
                                    menuSelector: "#shortcutMenu-",
                                    trigger: "'.(is_null($this->js_shortcuts['trigger']) ? 'mouseenter' : $this->js_shortcuts['trigger']).'"
                               },';
        }
        
        //add ajax tabs
        if(!is_null($this->_paginate_from_model)){
            $initscript .= ' tabs: {
                                    ajax: true
                               }';
        }
        
        $initscript .= '});'
            . '});';
        
        //check dependencies
        if($attachdependencies == true){
            $gridjs = '<link href="'.RELATIVE_APP_PATH.'/html/tables/styles/gridtable.css" rel="stylesheet" />';
            $gridjs .= '<script src="'. RELATIVE_APP_PATH .'/html/tables/scripts/jng.gridTableTools.js"></script>';
        
            //attach initilization
            $gridjs .= HTML::script($initscript,'script',TRUE);
        }
        else{
            $gridjs = HTML::script($initscript,'script',TRUE);
        }
        
        $table = $this->buildTable();
        $table .= $gridjs;
        
        if($return)
            return $table;
        else
            echo $table;            
    }
    
    /**
     * Builds Bootstrap v3 Div Table
     * @return string
     */
    protected function buildTable() {
        
        //start table
        $table = '<div id="'.$this->name.'" class="gridtable '
                .$this->attrs['class']
                .(!is_null($this->_rows_per_page) ? ' tabbed' : '')
                .'">';
        
        //loop through rows
        $this->buildRows($table);
        
        //build tab list
        if(!is_null($this->_rows_per_page)){

            $active = '';
            $rowcount = count($this->rows);
            $pages = (int) ceil($rowcount / (int)$this->_rows_per_page);

            $ul = '<ul class="nav nav-tabs-bottom m-b-10 pull-right" id="pages" role="tablist">';
            for($count = 1; $count <= ($pages-1); $count++){

                if($count == 1){
                    $active = ' active';
                }
                
                $ul .= '<li class="nav-item'.$active.'">'
                            . '<a class="nav-link" data-toggle="tab" href="#page-'.$count.'" role="tab" aria-controls="home" aria-expanded="false">'
                                . $count
                            . '</a>'
                       .'</li>';
                
                $active = '';
            }
            $ul .= '</ul>';

            //add to table
            $table .= $ul;
        }
        
        //model based pagination
        if(!is_null($this->_paginate_from_model)){
            $this->buildModelPagination($table);
        }
        
        //close table
        $table .= '</div>';
        
        return $table;
    }
    
    /**
     * Build model pagination
     * @param type $table
     */
    protected function buildModelPagination(&$table){
        
        if($this->_paginate_from_model){
            
            $ul = '<ul class="nav nav-tabs-bottom nav-ajax-tabs pull-right" id="pages" role="tablist">';
            foreach($this->_paginate_from_model as $page){

                $active = '';
                if(Input::has('page')){
                    if(Input::any('page') == $page['num']){
                        $active = ' active';
                    }
                }
                elseif($page['num'] == 1){
                    $active = ' active';
                }

                $ul .= '<li class="nav-item'.$active.'">'
                            . '<a class="nav-link" data-toggle="tab" href="'.Url::link('/ajax'.$page['url']).'" role="tab" aria-controls="home" aria-expanded="false">'
                                . $page['num']
                            . '</a>'
                        . '</li>';
            }
            $ul .= '</ul>';

            $table .= $ul;
        }
    }
    
    /**
     * Build rows for the table
     * @param type $table
     */
    protected function buildRows(&$table, $children = []) {
        
        $rowclass = $class = $rowhtml = null;
        
        if(count($children) === 0){
            $rows = $this->rows;
        }
        else{
            $rows = $children;
        }
        
        $realcount = $tabrowmarker = 0;
        $rowcount = $tabcount = 1;
        $rowhtml = $active = '';
        
        foreach ($rows as $row) {
            
            //create a field-rows holder div
            if($rowcount == 2 && count($children) == 0){
                $rowhtml .= '<div class="field-row-holder">';
                
                //create tab holder
                if(!is_null($this->_rows_per_page)){
                    $rowhtml .= '<div class="tab-content">';
                }
            }
            
            //check rows per page
            if(!is_null($this->_rows_per_page)){            
                
                //reset the rowcount
                if($rowcount == 2){
                    $realcount = 0;
                    $active = ' active';
                }
                
                if($realcount % $this->_rows_per_page == 0 && $rowcount >= 2){
                    
                    $rowhtml .= '<div id="page-'.$tabcount.'" class="tab-pane'.$active.'" role="tabpanel">';
                    $tabcount++;
                    
                    $tabrowmarker = 0;
                }
            }
            
            //check for children and prepend arrow
            if($row->hasChild){                
                $this->_has_children = true;
                $rowhtml .= '<div class="sibling-pointer has-children-'.($this->_show_children_onload == TRUE ? 'on' : 'off').'" data-refers-to="row-'.$rowcount.'">'
                                . ($this->_show_children_onload == TRUE ? '<i class="fa fa-caret-down"></i>' : '<i class="fa fa-caret-right"></i>')
                            . '</div>';
            }
            
            //start row
            $rowhtml .= '<div ';
            
            //start row class
            $rowclass = 'class="row ';
            
            //designate field row
            if($row->isHeader == false && $row->isFooter == false){
                $rowclass .= 'field-row';
            }
            
            $attrs = $row->getAttributes();                
            if(!is_null($attrs) && count($attrs) > 0){
                foreach($attrs as $name => $attr){ 
                    
                    //check for class attibute in row
                    if($name == 'class'){
                        $rowclass .= $attrs['class'];
                    }
                    else{
                        //add attribute
                        $rowhtml .= $name.'="'.$attr.'" ';
                    }
                }
            }
            
            //add row id
            $rowhtml .= 'id="'.(count($children) > 0 ? 'child-' : '').'row-'.$rowcount.'"';
            $row->setId($rowcount);
            
            //add child class
            if(count($children) > 0){
               $rowclass .= '-child'; 
            }
            
            //close class attribute
            $rowclass .= '"';
            
            //close row
            $rowhtml .= $rowclass;
            $rowhtml .= '>';
            
            //check and add shortcut menus
            if($row->hasShortcuts()){
                $shortcutmenu = $row->getShortcuts($this->name, $rowcount);
            }
            else{
                $shortcutmenu = null;
            }
            
            //loop through cells
            $this->buildCells($rowhtml, $row, $shortcutmenu, count($children) > 0 ? TRUE : FALSE);
            
            //check child rows
            if($row->hasChild){
                $table .= $rowhtml;
                    $table .= '<div class="clearfix"></div>'
                            . '<div class="contains-children">';
                    
                    //build child rows
                    $this->buildRows($table, $row->getChildren());
                        
                    $table .= '</div>';
                $table .= '</div>'; 
                
                $rowhtml = null;
                $rowcount++;
                continue;
            }
            
            //close tab div
            if(!is_null($this->_rows_per_page) && $rowcount >= 2){   
                
                if($tabrowmarker == $this->_rows_per_page-1){                    
                    $rowhtml .= '</div>';
                }
            }
            
            //close field-row-holder
            if($rowcount == (count($rows)-1) && count($children) == 0){
                $rowhtml .= '</div>';
                
                //close tab holder
                if(!is_null($this->_rows_per_page)){
                    $rowhtml .= '</div>';
                }
            }
            
            //close row
            $rowhtml .= '</div>'; 
            $table .= $rowhtml;
            
            $rowhtml = $active = null;
            
            $tabrowmarker++;
            $rowcount++;
            $realcount++;
        }
    }
    
    /**
     * Build cells for each row
     * @param type $rowhtml
     * @param type $row
     * @param type $shortcuts
     * @param type $children
     */
    protected function buildCells(&$rowhtml, $row, $shortcuts = null, $children = false){
        
        $cells = $row->getCells();
        $colcount = count($cells);
        $cellhtml = null;
        
        $cellcount = 0;
        foreach($cells as $cell){

            //check if hidden then just add hidden checkbox and skip the rest
            if($cell->isHidden){      
                
                $cellhtml = '<input id="checkbox-'.$row->getId().'" name="rows[]" value="'.$cell->getData().'" type="checkbox" style="display: none;">';
                $rowhtml .= $cellhtml;
                continue;
            }
            
            $cellhtml = '<div ';

            //add column name
            if($cell->hasColumnName()){
                $cellhtml .= 'data-column-name="'.$cell->getColumnName().'" ';
            }
            elseif($cell->isColumn()){
                
                if(array_key_exists('orderBy', $this->tools)){
                    $cellhtml .= 'data-colref="'.strip_tags($cell->getData()).'" data-sort="'.$this->tools['orderBy'][1].'" ';
                }
            }
            
            //check for class in cell
            $cellattrs = $cell->getAttributes();      
            if(!is_null($cellattrs) && count($cellattrs) > 0){

                if(array_key_exists('class', $cellattrs)){
                    $class[] = $cellattrs['class'];
                    unset($cellattrs['class']);
                }

                foreach($cellattrs as $name => $cellattr){ 
                    $cellhtml .= $name.'="'.$cellattr.'" ';
                }
            }

            //analyse grid
            $grid = $cell->getGrid();

            //check header get column grid value
            if($row->isHeader){                    
                if(!is_null($grid) && $children === FALSE){
                    $this->columns['main'][] = join(' ',$grid);
                }
                elseif(!is_null($grid) && $children === TRUE){
                    $this->columns['children'][] = join(' ',$grid);
                }
            }

            //if cell is defined
            $defaultcolsize  = floor(12 / $colcount);
            if(!is_null($grid) && count($grid) > 0){
                $class[] = join(' ', $grid);
            }
            //if cell is not defined but column is
           elseif(array_key_exists($cellcount, $this->columns['main'])
                   || array_key_exists('children', $this->columns) && $row->isHeader === FALSE){

                //add the set column values to the corresponding cell
               if($children === FALSE){
                   
                   if(array_key_exists($cellcount, $this->columns['main']))
                        $class[] = $this->columns['main'][$cellcount];
                   else
                        $class[] = 'col-md-'.$defaultcolsize;
               }
               elseif($children === TRUE){
                   
                   if(array_key_exists('children', $this->columns)){
                       if(array_key_exists($cellcount, $this->columns['children']))
                            $class[] = $this->columns['children'][$cellcount];
                   }
                   else{
                       $class[] = $this->columns['main'][$cellcount];
                   }
                   
               }
           }
           //if cell and column are not defined
           elseif(!array_key_exists($cellcount, $this->columns['main'])
                   || !array_key_exists('children', $this->columns)){  
               
                $class[] = 'col-md-'.$defaultcolsize;
            }

            //add compiled class
            $cellhtml .= 'class="'.trim(join(' ', $class)).'">';
            unset($class);

            //add shortcuts
            if(!is_null($shortcuts) && $cellcount == $shortcuts['column']){
                
                $cellhtml .= '<div class="dropdown" style="width: 100%; text-align:center">';
                
                //add shortcut handle and menu
                $cellhtml .= $shortcuts['handle'];
                $cellhtml .= $shortcuts['menu'];
                
                $cellhtml .= '</div>';
            }
            
            //add cell data
            $cellhtml .= $cell->getData();

            //set column image
            if($cell->isColumn() && $row->isFooter === FALSE){                
                if(array_key_exists('orderBy', $this->tools)){
                    $cellhtml .= '<div class="pull-right">'
                                    . '<img src="'.RELATIVE_APP_HTML_PATH.'/tables/img/sort_both.png">'
                                . '</div>';
                }
            }
            
            //close celll
            $cellhtml .= '</div>';
            $rowhtml .= $cellhtml;

            $cellcount++;
        }
    }
}
