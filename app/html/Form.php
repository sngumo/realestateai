<?php
namespace Jenga\App\Html;

require APP_HTML .DS. 'forms' .DS. 'Zebra_Form-master' .DS.'Zebra_Form.php';

use Zebra_Form;
use Jenga\App\Views\HTML;
use Jenga\App\Html\Forms\Parsley\Validator;

/**
 * This is the Jenga default form generator class
 * @author stanley
 */
class Form {
    
    /**
     * Holds the form name
     * @var type 
     */
    protected $name;
    
    /**
     * Holds all the elements within the form
     * @var type 
     */
    protected $elements = [];
    
    /**
     * Instance of Zebra_Form
     * @var Zebra_Form
     */
    protected $zebra;
    
    /**
     * The framework to be used to frame the form elements
     * @var type 
     */
    protected $formcast;
    
    /**
     * If labels and elements are horizontal or vertical
     * @var type 
     */
    protected $orientation;
    
    /**
     * Holds the form js resources
     * @var type 
     */
    protected $resources = [];
    
    /**
     * Map results boolean flag
     * @var type 
     */
    private $_map_elements = false;
    
    /**
     * Holds the elements map array
     * @var type 
     */
    private $_elements_map = null;
    
    /**
     * Use the date dropper plug-in
     * @var type 
     */
    private $_use_date_dropper = false;
    
    /**
     * The DOM for each HTML element
     * @var type 
     */
    protected $dom;
    
    /**
     * Map the form controls into tabs
     */
    protected $mapIntoTabs = false;
    
    /**
     * Holds the tab names
     * @var type 
     */
    protected $tabs = [];
    
    /**
     * Validate flag
     * @var type 
     */
    public $validate = false;

    public function __construct($name, $action = '', $method = 'POST', $attributes = ['data-parsley-validate' =>  '']){
        
        $this->name = $name;
        $this->formcast = 'bootstrap';
        
        //add validate class
        $this->zebra = new Zebra_Form($this->name, $method, $action, $attributes);
    }
    
    /**
     * This will serve as the tabs titles
     */
    public function addSeparator($label = '', $name = ''){
        
        $this->mapIntoTabs = true;
        $this->tabs[$name] = $label;
        
        //add label
        $this->addLabel($label, 'label_separator_'.$name, $name);
        return $this;
    }
    
    /**
     * Adds an <button> element to the form
     * @param type $caption
     * @param type $name
     * @param type $label
     * @param type $attributes
     * @return this 
     */
    public function addButton($caption, $name, $label = '', $attributes = '', $type = 'submit'){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add button
        $this->elements[$name] = $this->zebra->add('button', $name, $caption, $type, $attributes);
        return $this;
    }
    
    /**
     * Adds a CAPTCHA image to the form.
     * @param type $label
     * @param type $name
     * @param type $question
     * @return this 
     */
    public function addCaptcha($label, $name, $question){
        
        //add the captcha element
        $this->zebra->add('captcha',$name);
        
        //add the captcha label
        $this->addLabel($question, 'label_'.$name, $name);
        $text = $this->zebra->add('text', $name);
        
        //add captcha rule
        $text->set_rule(['captcha' => ['error', 'Characters not entered correctly!']]);
        $this->elements[$name] = $text;
        return $this;        
    }
    
    /**
     * Use the date-dropper plugin
     */
    public function useDateDropperPlugin(){
        $this->_use_date_dropper = true;
        return $this;
    }
    
    /**
     * Adds a date element to the form.
     * @param type $label
     * @param type $name
     * @param type $default
     * @param type $attributes
     * @param type $format
     * @return $this
     */
    public function addDate($label = '', $name, $default = '', $attributes = [], $addrange = false){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //remove the date attribute to avoid errors in zebra form
        if(array_key_exists('date', $attributes) || array_key_exists('daterange', $attributes) ){
            
            if(array_key_exists('date', $attributes)){
                
                $attrs = $attributes;
                unset($attrs['date']);
            }
            elseif(array_key_exists('daterange', $attributes)){
                
                $attrs = $attributes;
                unset($attrs['daterange']);
            }
        }
        else{
            $attrs = $attributes;
        }
        
        //add date element
        //$attributes['data-prefix'] = '<span class="add-on input-group-addon"><i class="fa fa-calendar"></i></span>';
        $date = $this->zebra->add('text', $name, $default, $attrs);
        
        //add date rule
        $date->set_rule(['date' => ['error', 'Invalid date specified!']]);
        
        //add to elements 
        $this->elements[$name] = $date;
        
        //add resources for datepicker
        $attributes['id'][] = $name;
        
        if($addrange === FALSE){
            
            if(array_key_exists('singledatepicker', $this->resources)){
                $resources = $this->resources['singledatepicker'];
                array_push($resources,$attributes);
                
                $this->resources['singledatepicker'] = $resources;
            }
            else{
                $this->addResources('singledatepicker', [$attributes]);
            }
        }
        else{
            
            if(array_key_exists('daterangepicker', $this->resources)){
                
                if(array_key_exists('id', $this->resources['daterangepicker'])){
                    
                    $id = $this->resources['daterangepicker']['id'];
                    array_push($id, $name);
                    
                    $this->resources['daterangepicker']['id'] = $id;
                }
            }
            else{
                $this->addResources ('daterangepicker', $attributes);
            }
        }
        
        return $this;
    }
    
    /**
     * Add a Date Range picker to the form
     * @param type $label
     * @param type $name
     * @param type $default
     * @param type $attributes
     */
    public function addDateRange($label = '', $name, $default = '', $attributes = []){
        return $this->addDate($label, $name, $default, $attributes, TRUE);
    }
    
    /**
     * Adds an <input type="file"> element to the form.
     * @param type $label
     * @param type $name
     * @param type $upload_path
     * @param type $file_name
     * @param type $error_block
     * @param type $error_message
     * @return $this
     */
    public function addFile($label = '', $name, $upload_path = 'tmp', $file_name = true, $error_block = 'error', $error_message = 'File could not be uploaded!'){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add file upload
        $this->elements[$name] = $this->zebra->add('file', $name);
        $this->elements[$name]->set_rule([
            'upload' => [
                $upload_path, 
                $file_name, 
                $error_block, 
                $error_message
            ]]);
        
        return $this;
    }
    
    /**
     * Adds an <input type="image"> element to the form.
     * @param type $label
     * @param type $name
     * @param type $path
     * @param type $attributes
     */
    public function addImage($label = '', $name, $path){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add image element
        $this->elements[$name] = $this->zebra->add('image', $name, $path);
        return $this;
    }
    
    /**
     * Adds an <label> element to the form
     * @param type $caption
     * @param type $name
     * @param type $element
     * @param type $attributes
     * @return $this
     */
    public function addLabel($caption, $name, $element, $attributes = ''){   
        $this->zebra->add('label', $name, $element, $caption, $attributes);
        return $this;
    }
    
    /**
     * Adds a "note" to the form, attached to a element.
     * @param type $name
     * @param type $attachto Must be an existing element to attach the note to
     * @param type $attributes
     * @return $this
     */
    public function addNote($name, $attachto, $caption, $attributes = ''){
        $this->elements[$name] = $this->zebra->add('note','note_'.$name, $attachto, $caption);
        return $this;
    }
    
    /**
     * Adds an <input type="password"> element to the form.
     * @param type $label
     * @param type $name
     * @param type $default
     * @param type $attributes
     */
    public function addPassword($label = '', $name, $default = '', $attributes = ''){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add password
        $this->elements[$name] = $this->zebra->add('password', $name, $default, $attributes);
        return $this;
    }
    
    /**
     * Adds an <input type="checkbox"> control to the form.
     * @param type $label
     * @param type $name
     * @param type $value if string one checkbox is added if array multiple checkboxes
     * @param type $attributes
     */
    public function addCheckBox($label = '', $name, $value = '', $attributes = null){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add checkbox
        if(is_string($value)){
            $this->elements[$name] = $this->zebra->add('checkbox', $name, $value, $attributes);
        }
        elseif(is_array($value)){
            $this->elements[$name] = $this->zebra->add('checkboxes', $name, $value, $attributes);
        }
        
        return $this;
    }
    
    /**
     * Adds an <input type="radio"> element to the form.
     * @param type $label
     * @param type $name
     * @param type $value if string one radio is added if array multiple
     * @param type $default
     * @param type $attributes
     */
    public function addRadios($label = '', $name='', $value = '', $default = '', $attributes = ''){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add radios
        if(is_string($value)){
            $this->elements[$name] = $this->zebra->add('radio', $name, $value, $default, $attributes);
        }
        elseif(is_array($value)){
            $this->elements[$name] = $this->zebra->add('radios', $name, $value, $default, $attributes);
        }
        
        return $this;
    }
    
    /**
     * Adds an <input type="reset"> element to the form.
     * @param type $label
     * @param type $name
     * @param type $caption
     * @param type $attributes
     */
    public function addResetButton($label = '', $name, $caption = '', $attributes = ''){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add reset button
        $this->elements[$name] = $this->zebra->add('reset', $name, $caption);
        return $this;
    }
    
    /**
     * Adds an <select> element to the form.
     * @param type $label
     * @param type $name
     * @param type $default
     * @param type $options
     * @param type $attributes
     */
    public function addSelect($label = '', $name, $default = '', $options = [] ,$attributes = ''){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add select
        $select = $this->zebra->add('select', $name, $default, $attributes);
        $select->add_options($options);
        
        //add to element list
        $this->elements[$name] = $select;
        
        return $this;
    }
    
    /**
     * Adds a countries listing
     * @param type $label
     * @param type $name
     * @param type $default
     */
    public function addCountry($label = '', $name, $default = '', $attributes = '') {
        
        $jsonCountries = null;
        
        include(APP_HTML .DS. 'forms' .DS. "includes" .DS. "countries.json.php");
        $jsonObj = json_decode($jsonCountries);

        $countries = array_combine($jsonObj->keys, $jsonObj->values);
        
        //create the select input
        return $this->addSelect($label, $name, $default, $countries, $attributes);
    }
    
    /**
     * Returns full country name from shortcode
     */
    public static function resolveCountry($shortcode){
        
        $jsonCountries = null;
        include(APP_HTML .DS. 'forms' .DS. "includes" .DS. "countries.json.php");
        $jsonObj = json_decode($jsonCountries);

        $countries = array_combine($jsonObj->keys, $jsonObj->values);
        
        if(array_key_exists($shortcode, $countries)){
            return $countries[$shortcode];
        }
        else{
            return NULL;
        }
    }
    
    /**
     * Adds an <input type="submit"> element to the form.
     * @param type $label
     * @param type $name
     * @param type $caption
     * @param type $attributes
     * @return type
     */
    public function addSubmit($label = '', $name, $caption = '', $attributes = ''){
        return $this->addButton($label, $name, $caption, $attributes);
    }
    
    /**
     * Adds an <input type="text"> element to the form.
     * @param type $label
     * @param type $name
     * @param type $default
     * @param type $attributes
     */
    public function addTextField($label = '', $name, $default = '', $attributes = ''){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add text field
        $this->elements[$name] = $this->zebra->add('text', $name, $default, $attributes);
        return $this;
    }
    
    /**
     * Adds an <input type="number"> element to the form.
     * @param type $label
     * @param type $name
     * @param type $default
     * @param type $attributes
     */
    public function addNumber($label = '', $name, $default = '', $attributes = ''){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add text field
        $this->elements[$name] = $this->zebra->add('number', $name, $default, $attributes);
        return $this;
    }
    
    /**
     * Adds an <textarea> element to the form.
     * @param type $label
     * @param type $name
     * @param type $default
     * @param type $attributes
     */
    public function addTextArea($label = '', $name, $default = '', $attributes = ''){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add textarea
        $this->elements[$name] = $this->zebra->add('textarea', $name, $default, $attributes);
        return $this;
    }
    
    /**
     * Adds an <hidden> element to the form.
     * @param type $name
     * @param type $value
     * @return $this
     */
    public function addHidden($name, $value = ''){
        
        //add hidden
        $this->elements[$name] = $this->zebra->add('hidden', $name, $value);
        return $this;
    }
    
    /**
     * Adds a time picker element to the form.
     * @param type $label
     * @param type $name
     * @param type $default
     * @param type $attributes
     */
    public function addTime($label = '', $name, $default = '', $attributes = ''){
        
        //add label
        if($label != ''){
            $this->addLabel($label, 'label_'.$name, $name);
        }
        
        //add time
        $this->elements[$name] = $this->zebra->add('time', $name, $default, $attributes);
        return $this;
    }
    
    /**
     * Organizes the form elements according to set elements map with each number representing a new row
     * @example [2,2,2] means 2 elements in each row
     */
    public function map(array $elements_map){        
        $this->_map_elements = TRUE;
        $this->_elements_map = $elements_map;
        
        return $this;
    }
    
    /**
     *  Renders the form.
     *
     *  @param  string  $template       The output of the form can be generated automatically, can be given from a template
     *                                  file or can be generated programmatically by a callback function.
     *
     *                                  For the automatically generated template there are two options:
     *
     *                                  -   when <i>$template</i> is an empty string or is "<i>*vertical</i>", the script
     *                                      will automatically generate an output where the labels are above the controls
     *                                      and controls come one under another (vertical view)
     *
     *                                  -   when <i>$template</i> is "<i>*horizontal</i>", the script will automatically
     *                                      generate an output where the labels are positioned to the left of the controls
     *                                      while the controls come one under another (horizontal view)
     *
     *                                  When templates are user-defined, <i>$template</i> needs to be a string representing
     *                                  the <i>path/to/the/template.php</i>.
     *
     *                                  The template file itself must be a plain PHP file where all the controls
     *                                  added to the form (except for the hidden controls, which are handled automatically)
     *                                  will be available as variables with the names as described in the documentation
     *                                  for each of the controls. Also, error messages will be available as described at
     *                                  {@link Zebra_Form_Control::set_rule() set_rule()}.
     *
     *                                  A special variable will also be available in the template file - a variable with
     *                                  the name of the form and being an associative array containing all the controls
     *                                  added to the form, as objects.
     *
     *                                  <i>The template file must not contain the <form> and </form> tags, nor any of the
     *                                  <hidden> controls added to the form as these are generated automatically!</i>
     *
     *                                  There is a third method of generating the output and that is programmatically,
     *                                  through a callback function. In this case <i>$template</i> needs to be the name
     *                                  of an existing function.
     *
     *                                  The function will be called with two arguments:
     *
     *                                  -   an associative array with the form's controls' ids and their respective
     *                                      generated HTML, ready for echo-ing (except for the hidden controls which will
     *                                      still be handled automatically);
     *
     *                                      <i>note that this array will also contain variables assigned through the
     *                                      {@link assign()} method as well as any server-side error messages, as you
     *                                      would in a custom template (see {@link Zebra_Form_Control::set_rule() set_rule()}
     *                                      method and read until the second highlighted box, inclusive)</i>
     *
     *                                  -   an associative array with all the controls added to the form, as objects
     *
     *                                  THE USER FUNCTION MUST RETURN THE GENERATED OUTPUT!
     *
     *  @param  boolean $return         (Optional) If set to TRUE, the output will be returned instead of being printed
     *                                  to the screen.
     *
     *                                  Default is FALSE.
     *
     *  @param  array   $variables      (Optional) An associative array in the form of "variable_name" => "value"
     *                                  representing variable names and their associated values, to be made available
     *                                  in custom template files.
     *
     *                                  This represents a quicker alternative for assigning many variables at once
     *                                  instead of calling the {@link assign()} method for each variable.
     *
     *  @return mixed                   Returns or displays the rendered form.
     */
    public function render($template = '', $return = false, $variables = ''){
        
        //check label oreintation
        if($template === 'horizontal' || $template === 'vertical'){
            $this->orientation = $template;
            $template = '*'.$template;
        }
        
        //map form elements
        if($this->_map_elements && $this->mapIntoTabs === FALSE){
            $zebraform = $this->zebra->render([$this, 'mapElements'], $return, $variables);
        }
        elseif($this->mapIntoTabs === TRUE){
            $zebraform = $this->zebra->render([$this, 'mapToTabs'], $return, $variables);
        }
        else{
            $zebraform = $this->zebra->render($template, $return, $variables);
        }
        
        //set validator
        if($this->validate){
            $zebraform .= '<link href="'.RELATIVE_APP_HTML_PATH.'/forms/scripts/parsley-js/parsley.css" rel="stylesheet">';
            $zebraform .= '<script type="text/javascript" src="'.RELATIVE_APP_HTML_PATH.'/forms/scripts/parsley-js/parsley.min.js"></script>';
        }
        
        //add built resources
        $resources = $this->buildFormResources();   
        
        if(!is_null($resources))
            $zebraform .= $resources;
        
        if($return == false)
            echo $zebraform;
        else
            return $zebraform;
    }
    
    /**
     * Maps the form elements into tabs
     * @param type $arg1
     * @param type $arg2
     */
    public function mapToTabs($arg1, $arg2){
        
        $tabs = array_keys($this->tabs);
        
        //make tab holder
        $html = '<div class="tab-content">';
        
        //start tabs loop
        $tabcount = 0;
        foreach($tabs as $tab){
            
            //create tab
            $html .= '<div id="'.$tab.'" role="tabpanel" class="tab-pane '.($tabcount == 0 ? 'active' : '').'">';
            
            //start controls loop
            foreach($arg1 as $label => $control){
                    
                //set the label and control
                if(strpos($label, 'separator_') == FALSE){
                    
                    switch (get_class($arg2[$label])) {
                        
                        case 'Zebra_Form_Radio':
                            
                            $html .= '<div class="row">'
                                        . '<div class="col-md-12">'
                                            . '<table class="checkbox">'
                                                . '<tr>'       
                                                    . '<td>'
                                                        . '<div class="radio">'
                                                            . $arg1[$label]
                                                            . $arg1['label_'.$label]
                                                        . '</div>'
                                                    . '</td>'
                                                . '</tr>'
                                            .'</table>'
                                        . '</div>'
                                    . '</div>';
                            break;
                        
                        case 'Zebra_Form_Checkbox':
                            
                            $html .= '<div class="row">'
                                        . '<div class="col-md-12">'
                                            . '<table class="checkbox">'
                                                . '<tr>'       
                                                    . '<td>'
                                                        . '<div class="checkbox checkbox-primary">'
                                                            . $arg1[$label]
                                                            . $arg1['label_'.$label]
                                                        . '</div>'
                                                    . '</td>'
                                                . '</tr>'
                                            .'</table>'
                                        . '</div>'
                                    . '</div>';
                            break;

                        default:
                            $html .= '<div class="row">'
                                        . '<div class="col-md-12">'
                                            . $arg1[$label]
                                        . '</div>'
                                    . '</div>';
                            break;
                    }
                    
                }
                
                //clear from arg1
                unset($arg1['label_'.$label], $arg1[$label]);
                
                //check separator
                if(strpos($label, 'separator_') !== FALSE && $tabcount > 0){
                    break;
                }
                
                $tabcount++;
            }
            
            //close tab
            $html .= '</div>';
        }
        
        //end tab holder
        $html .= '</div>';
        
        //compile full tabs
        $tabshtml = '<ul class="nav nav-tabs m-b-10" id="myTab" role="tablist">';
        
        $linkcount = 0;
        foreach($this->tabs as $heading => $title){
            $tabshtml .= '<li class="nav-item '.($linkcount == 0 ? 'active' : '').'">
                            <a class="nav-link" id="home-tab" data-toggle="tab" href="#'.$heading.'" role="tab" aria-controls="home" aria-expanded="false">
                                '.$title.'
                            </a>
                        </li>';
            
            $linkcount++;
        }
        $tabshtml .= '</ul>';
        
        //attach the tabs
        $tabshtml .= $html;
        
        return $tabshtml;
    }
    
    /**
     * Processes the schematic map section and rearranges the form elements accordingly
     * 
     * @param type $arg1
     * @param type $arg2
     * @return string
     */
    public function mapElements($arg1, $arg2) {
        
        $elementcount = $rowcount = 0;
        $elementids = array_keys($this->elements);
        $html = '';
        
        foreach($this->_elements_map as $row){
            
            if($this->formcast == 'bootstrap'){
                $rowclass = 'form-group';
                $cellclass = 'col-md-'.round((12 / $row),0,PHP_ROUND_HALF_DOWN);
            }
            else{
                $rowclass = 'rows '.($rowcount % 2 == 0 ? 'even' : '').' column-'.$row;
                $cellclass = 'cell';
            }
            
            $html .= '<div class="'.$rowclass.'">';

            if($this->formcast == 'bootstrap'){
                $html .= '<div class="row">';
            }
            
            $this->buildFormRow($html, $elementcount, $row, [$cellclass, $elementids, $arg1, $arg2]);
            
             if($this->formcast == 'bootstrap'){
                $html .= '</div>';
             }
             
            $html .= '<div class="clear"></div>';
            $html .= '</div>';
            
            $rowcount++;
        }
        
        return $html;
    }
    
    /**
     * Builds the form row
     * @param type $html
     * @param type $row
     * @param type $attrs
     * @todo Fix bug regarding the mapping of radio and checkboxes
     */
    protected function buildFormRow(&$html, &$elementcount, $row, $attrs) {
        
        list($cellclass, $elementids, $arg1, $arg2) = $attrs;
        
        $label = ''; 
        
        for($r=0; $r<=($row-1); $r++){

            //get element id
            $id = $elementids[$elementcount]; 

            //get type
            if(array_key_exists($id, $arg2)){                    
                $type = $arg2[$id]->get_attributes(['type'])['type'];
            }
            else{   
                
                $r--;
                $elementcount ++;
                continue;
            }
            
            if($row > 1){
                $html .= '<div class="'.$cellclass.'">';    
            }
            elseif($row == 1 && $this->formcast == 'bootstrap'){
                $html .= '<div class="col-md-12">';
            }

            //save element label separately
            if(array_key_exists('label_'.$id, $arg1)){
                $label = $arg1['label_'.$id];
            }
            else{
                $label = '';
            }

            //attach label and element
            if($type !== ('label' || 'checkbox' || 'radio')){   

                $html .= $label;
                $html .= ($this->orientation == 'vertical') ? '<div class="clearfix"></div>' : '';
                $prefix = $this->addPrefixIfPresent($arg1[$id]);

                if($prefix != ''){

                    $html .= '<div class="input-prepend input-group">';
                    $html .= $prefix;
                    $html .= $arg1[$id];
                    $html .= '</div>';
                }
                else{
                    $html .= $arg1[$id];
                }

                unset($label);
            }
            elseif($type === ('checkbox' || 'radio')){

                $html .= '<table class="checkbox">'
                        . '<tr>';                    
                foreach($radiochk as $key){                        
                    $html .= '<td>'.$arg1[$key].'</td>';
                    $html .= '<td>'.$arg1['label_'.$key]. '</td>';
                }                    
                $html .= '</tr>'
                        .'</table>';
            }

            $html .= ($row > 1 || ($row == 1 && $this->formcast == 'bootstrap') ? '</div>' : '' );

            if(!is_null($arg2[$id])){                    
                $arg2[$id]->get_attributes(['type'])['type'];
            }

            $elementcount ++;
        }
    }
    
    /**
     * Sets validation rule to the last created element
     * @param type $rules
     * @param type $trigger
     * @return $this
     */
    public function validate($rules, $trigger = ''){
        
        //set validate
        $this->validate = true;
        
        //build validator
        $element = end($this->elements);
        
        //get element name
        $name = $element->attributes['name'];
        
        //get the 
        if(array_key_exists('label_'.$name, $this->zebra->controls)){
            $label = $this->zebra->controls['label_'.$name]->attributes['label'];
            $this->zebra->controls['label_'.$name]->attributes['label'] = $label .' <span style="color:red">*</span>';
        }
        
        $this->buildValidator($element, $rules, $trigger);
        
        return $this;
    }
    
    /**
     * Incorporates the full Parsley validator
     * @param type $element
     */
    protected function buildValidator($element, $rules, $trigger = '') {
        
        $validator = new Validator($element, $rules, $trigger);
        $validator->merge();
    }
    
    /**
     * Adds the HTML string to the DOM
     * @param type $html
     */
    protected function createDOM($html) {        
        $this->dom = new \DOMDocument();
        $this->dom->loadHTML($html);
    }
    
    /**
     * Look for the data-prefix attribute and adds it to the form element
     * @param type $html
     * @return string
     */
    protected function addPrefixIfPresent($html){
        
        $this->createDOM($html);
        $inputs = $this->dom->getElementsByTagName('input');
        
        foreach($inputs as $input){
            $prefix = $input->getAttribute('data-prefix');
            
            if($prefix != '')
                return urldecode ($prefix);
        }
        
        return '';
    }
    
    /**
     * Adds to form resources
     * @param type $name
     * @param type $resource
     */
    protected function addResources($name, $resource){
        $this->resources[$name] = $resource;
    }
    
    /**
     * Build a coherent Javascript resources object for the form
     */
    protected function buildFormResources(){
        
        if(count($this->resources) > 0){
            
            $script = '';
            $resourcelist = [];
            foreach($this->resources as $name => $attrs){
                switch ($name) {
                    
                    //date picker for addDate() element
                    case 'singledatepicker':
                        if($this->_use_date_dropper === FALSE){
                            
                            $asset = '<script type="text/javascript" src="'.RELATIVE_APP_HTML_PATH.'/forms/scripts/moment/moment.min.js"></script>';
                            if(!in_array($asset, $resourcelist)){
                                $resourcelist[] = $asset;
                                unset($asset);
                            }

                            $asset = '<script src="'.TEMPLATE_URL.'/backend/plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>'
                                    . '<link href="'.TEMPLATE_URL.'/backend/plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">';

                            if(!in_array($asset, $resourcelist)){
                                $resourcelist[] = $asset;
                                unset($asset);
                            }

                            foreach($attrs as $attr){

                                //bypass the data attribute
                                if(array_key_exists('data-jng-map', $attr)){
                                    unset($attr['data-jng-map']);
                                }

                                $id = $attr['id'][0];
                                $script .= "$('#".$id."').datepicker({
                                                ".$this->compileAttrs($attr)."
                                            });";
                            }
                        }
                        else{
                            
                            //add the date dropper plugin
                            $asset = '<script src="'. TEMPLATE_URL .'/backend/plugins/datedropper/datedropper.pro.min.js"></script>';
                            
                            //add asset to resource list
                            if(!in_array($asset, $resourcelist)){
                                $resourcelist[] = $asset;
                                unset($asset);
                            }
                            
                            foreach($attrs as $attr){
                                
                                $id = $attr['id'][0];
                                $script .= "var ".$id."_picker = $('#".$id."').dateDropper({
                                                ".$this->compileAttrs($attr['date'])."
                                            });";
                            }
                        }
                        break;
                    
                    //datepicker for addDateRange() element
                    case 'daterangepicker':
                        $asset= '<script type="text/javascript" src="'.RELATIVE_APP_HTML_PATH.'/forms/scripts/moment/moment.min.js"></script>';
                        if(!in_array($asset, $resourcelist)){
                            $resourcelist[] = $asset;
                            unset($asset);
                        }
                        
                        $asset = '<script type="text/javascript" src="'.RELATIVE_APP_HTML_PATH.'/forms/scripts/date-range-picker/daterangepicker.js"></script>';
                        if(!in_array($asset, $resourcelist)){
                            $resourcelist[] = $asset;
                            unset($asset);
                        }
                        
                        //unset id and data-prefix
                        $ids = $attrs['id'];
                        foreach($ids as $id){
                            $script .= "$('#".$id."').daterangepicker({"
                                        . "showDropdowns: true,"
                                        . (array_key_exists('daterange', $attrs) ? $this->compileAttrs($attrs['daterange']) : "")
                                    . "});";
                        }
                        
                        unset($attrs['id'], $attrs['data-prefix']);
                        break;
                }
            }
            
            //add to script
            $resources = join('', $resourcelist)
                        . HTML::script('$(function () {'.$script.'});', 'script', TRUE);
                              
            
            return $resources;
        }
        
        return NULL;
    }
    
    /**
     * Compile attributes into attribute string
     * @param type $attributes
     */
    protected function compileAttrs($attributes) {
        
        $attr_str = '';
        foreach ($attributes as $name => $attr){
            
            if(!is_array($attr) && !is_bool($attr)){
                $attr_str .= $name.': \''.addslashes($attr).'\',';
            }
            elseif(is_bool($attr)){
                
                if($attr === true){
                    $attr_str .= $name.': true,';
                }
                else{
                    $attr_str .= $name.': false,';
                }
            }
            else{
                $attr_str .= $name.': {'. rtrim($this->compileAttrs($attr), ',') .'},';
            }
        }
        return $attr_str;
    }
    
    /**
     * Returns the raw ZebraForm elements
     * @param type $name
     * @return type
     */
    public function getElements($name){
        
        if(array_key_exists($name, $this->elements))
            return $this->elements[$name];
        
        return $this->elements;
    }
    
    /**
     * Return form name
     * @return type
     */
    public function getFormName(){
        return $this->name;
    }
        
    /**
     * Strips the validation values in sent form data
     * @param type $formdata
     */
    public static function garbageCollect(& $formdata) {
        
        if(is_array($formdata)){
            $keys = array_keys($formdata);

            $list = [];
            foreach($keys as $key){            
                if(strpos($key, 'zebra_') !== FALSE || strpos($key, 'name_') !== FALSE){
                    $list[] = $key;
                }
            }

            //unset keys in list        
            foreach($list as $name){
                unset($formdata[$name]);
            }
        }
    }
}
