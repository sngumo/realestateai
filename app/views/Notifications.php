<?php
namespace Jenga\App\Views;

use Jenga\App\Core\App;
use Jenga\App\Request\Session;

class Notifications extends Overlays {
    
    public $basecss;
    public $basescript;
    public $attributes = '';
    
    //private $_types = ['tooltip','popover','alert'];
    private $_msglevels = ['notice','error','warning','success','confirm'];
    
    /**
     * Assigns a tooltip to every assigned HTML element
     * 
     * @param type $msg
     * @param type $attributes
     * @return type
     */
    public static function tooltip($msg, $attributes = array()){
        
        //add to tracker
        self::$tracker[] = 'tooltip';
        
        //unset the data-toogle
        if(isset($attributes['data-toggle'])){            
            unset($attributes['data-toggle']);
        }
        
        //process smg and attributes       
        return 'title="'.$msg.'" '.self::processAttributes($attributes).' data-toggle="tooltip" ';
    }
    
    /**
     * Assigns a popover to every assigned HTML element
     * 
     * @param type $msg
     * @param type $attributes
     * @return type
     */
    public static function popover($msg, $attributes = array()){
        
        //add to tracker
        self::$tracker[] = 'popover';
        
        if(isset($attributes['data-toggle'])){
            unset($attributes['data-toggle']);
        }
        
        //process smg and attributes       
        return 'data-content="'.$msg.'" '.self::processAttributes($attributes).' data-toggle="popover" ';
    }
    
    /**
     * Processes any sent attributes from the notifications
     * 
     * @param type $attr
     * @return string
     */
    private static function processAttributes($attr){
        
        foreach($attr as $attrname => $attrvalue){
            $attributes .= $attrname.'="'.$attrvalue.'" ';
        }
        
        return $attributes;
    }
    
    /**
     * Sets message into session for display
     * 
     * @param type $msglevel Message types: Notice, Success, Warning, Error
     * @param type $msg
     * @param type $attributes
     * @return none
     */
    public function setMessage($msglevel, $msg, $attributes = ''){
                
        $level = strtolower($msglevel);
        
        if(in_array($level, $this->_msglevels)){
            
            //clear session flashbag
            Session::getFlush()->clear();
            
            //set message level
            $session_key = 'message_'.$level;            
            Session::flash($session_key, $msg);
            
            //add sticky attribute
            if($attributes == 'sticky'){
                Session::keep($session_key);
                $this->attributes = $attributes;
            }  
        }
    }
    
    /**
     * Configures the display based on the Session data
     * 
     * @return string the sent message
     */
    public function display(){
        
        if(Session::getFlush()->has('message_notice')){

            $message = '<div class="alert alert-info">';
            $strongmsg = 'Info: ';

            $msg = Session::getFlush()->get('message_notice')[0];
            $type = 'info';
        }
        elseif(Session::getFlush()->has('message_success')){

            $message = '<div class="alert alert-success">';
            $strongmsg = 'Success: ';

            $msg = Session::getFlush()->get('message_success')[0];
            $type = 'success';
        }
        elseif(Session::getFlush()->has('message_warning')){

            $message = '<div class="alert alert-warning">';
            $strongmsg = 'Warning: ';

            $msg = Session::getFlush()->get('message_warning')[0];
            $type = 'warning';
        }
        elseif(Session::getFlush()->has('message_error')){

            $message = '<div class="alert alert-error">';
            $strongmsg = 'Error: ';

            $msg = Session::getFlush()->get('message_error')[0];
            $type = 'danger';
        }
        
        //set the notifications
        if(!is_null($strongmsg)){
            
            HTML::script(RELATIVE_VIEWS.'/notifications/notifications.js','file');
            HTML::script('$.bootstrapGrowl("<strong>'.$strongmsg.'</strong>'.$msg.'", {
                            type: "'.$type.'",
                            width: "auto",
                            allow_dismiss: true
                        });');
        }
    }
    
    /**
     * Generetaes a static alert 
     * 
     * @param type $msg
     * @param string $type alert type: info, notice, success, warning, error
     * @param boolean $return Whether the alert sould be echoed or returned
     * @param boolean $noclose Disables the close button
     * @return string
     */
    public static function Alert($msg, $type, $return = FALSE, $noclose = FALSE){
        
        if($type == 'info'){

            $message = '<div class="alert alert-info no-shadow">';
            $strongmsg = 'Info: ';
        }
        elseif($type == 'notice'){

            $message = '<div class="alert alert-info no-shadow">';
            $strongmsg = 'Info: ';
        }
        elseif($type == 'success'){

            $message = '<div class="alert alert-success no-shadow">';
            $strongmsg = 'Success: ';
        }
        elseif($type == 'warning'){

            $message = '<div class="alert alert-warning no-shadow">';
            $strongmsg = 'Warning: ';
        }
        elseif($type == 'error'){

            $message = '<div class="alert alert-danger no-shadow">';
            $strongmsg = 'Error: ';
        }
        
        if($noclose == FALSE){
            
            $message .= '<a href="#" class="close" data-dismiss="alert">&times;</a>';
        }
        
        $message .= '<strong>'.$strongmsg.'</strong>'.$msg
                    . "</div>";
        
        if($return == FALSE){
            
            echo $message;
        }
        else{
            
            return $message;
        }
    }
}