<?php
namespace Jenga\App\Html\Forms\Parsley;

use Jenga\App\Helpers\Help;

/**
 * Processes the Parsley validation mechanism
 * @author stanley
 */
class Validator {
    
    /**
     * The specific form element
     * @var type 
     */
    protected $element;
    
    /**
     * The validation rules
     * @var type 
     */
    protected $rules;
    
    /**
     * Any inline triggers to trigger validation
     * @var type 
     */
    protected $trigger;
    
    /**
     * The validation data types
     * @var type 
     */
    private $_data_types = ['email','url','urlstrict','number','digits','alphanum','dateIso'];
    
    /**
     * The validation data constraints
     * @var type 
     */
    private $_data_constraints = ['required','notblank','minlength','min','maxlength','max',
                    'rangelength','range','regexp','equalto','mincheck'];

    public function __construct($element, $rules, $trigger = ''){
        
        $this->element = $element;
        $this->rules = $rules;
        $this->trigger = $trigger;
    }
    
    /**
     * Merges rules with element
     */
    public function merge(){
        
        //if validation has custom error message
        if(Help::isAssoc($this->rules)){
            
            foreach($this->rules as $rule => $message){
                
                //insert validation
                if(in_array($rule, $this->_data_constraints)){
                    
                    switch ($rule) {
                        case 'equalto':
                            $this->element->set_attributes([
                                'data-parsley-'.$rule => '#'.$message
                            ]);
                            break;
                        
                        case 'minlength': case 'maxlength':
                        case 'min': case 'mincheck': case 'max':
                        case 'range': case 'rangelength':
                            $this->element->set_attributes([
                                'data-parsley-'.$rule => $message
                            ]);
                            break;

                        default:
                            $this->element->set_attributes([
                                'data-parsley-'.$rule => "true",
                                'data-parsley-'.$rule.'-message' => $message
                            ]);
                            break;
                    }
                }
                elseif(in_array($rule, $this->_data_types)){

                    $this->element->set_attributes([
                        'data-parsley-type' => $rule,
                        'data-parsley-type-'.$rule.'-message' => $message
                    ]);
                }
            }
        }
        //if no error message is provided
        else{
            
            foreach($this->rules as $rule){
                
                //insert validation
                if(in_array($rule, $this->_data_constraints)){                    
                    $this->element->set_attributes(['data-parsley-'.$rule => true]);
                }
                elseif(in_array($rule, $this->_data_types)){
                    $this->element->set_attributes(['data-parsley-type' => $rule]);
                }
            }
        }
            
        //insert trigger
        if($this->trigger != ''){
            $this->element->set_attribute(['data-parsley-trigger' => $this->trigger]);
        }
    }
}
