<?php
namespace Jenga\App\Views;

use Jenga\App\Core\App;

class Overlays extends HTML {
    
    public static $title_addons = null;
    
    /**
     * Creates the wrapping to convert an element to be a Bootstrap Modal
     * 
     * @param type $settings array [id, role, title, buttons = ['name' => 'settings']]
     * @param type $content The content to be inserted
     * 
     * @return type Complete Modal
     */
    public static function Modal(array $settings, $content = ''){
        
        //register overlay caching mechanism into HTML processing queue
        HTML::register('overlay_modal');
        
        if(isset($settings['buttons'])){            
            $buttons = self::_processButtons($settings['buttons']);
        }
        
        if($content == ''){            
            $content = '<div class="showpreload" style="width:100%;text-align:center;opacity:0.5;">'
                            . '<img src="'.RELATIVE_APP_PATH .'/views/loading/logo.gif" />'
                        . '</div>';
        }
        
        if(array_key_exists('size', $settings)){            
            if($settings['size'] == 'large'){
                $size = 'modal-lg';
            }
            elseif($settings['size'] == 'small'){
                $size = 'modal-sm';
            }
        }
        
        $modal = '<div class="modal fade " id="'.$settings['id'].'" role="'.$settings['role'].'" aria-labelledby="'.$settings['id'].'Label" aria-hidden="true">
                  <div class="modal-dialog '.$size.'">
                    <div class="modal-content">
                      <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title" id="'.$settings['id'].'Label">'.$settings['title'].'</h4>
                      </div>
                      <div class="modal-body">
                        '.$content.'
                      </div>
                      <div class="modal-footer">
                        '.$buttons.'
                      </div>
                    </div>
                  </div>
                </div>';
        
        return $modal;
    }
    
    /**
     * Creates the wrapping to convert an element to be a Bootstrap Modal 
     * BUT without the customary headers and footers attached
     * 
     * @param type $settings array [id, role, title, buttons = ['name' => 'settings']]
     * @param type $content The content to be inserted
     * 
     * @return type Complete Modal
     */
    public static function StrippedModal(array $settings, $content = ''){
        
        //register overlay caching mechanism into HTML processing queue
        HTML::register('overlay_modal');
        $viewpoint = App::get('viewpoint');
        
        if($content == ''){            
            $content = '<div class="showpreload" style="width:100%;text-align:center;opacity:0.5;">'
                            . '<img src="'.RELATIVE_APP_PATH .'/views/loading/logo.gif" />'
                        . '</div>';
        }
        
        if(array_key_exists('size', $settings)){
            if($settings['size'] == 'large'){
                $size = 'modal-lg';
            }
            elseif($settings['size'] == 'small'){
                $size = 'modal-sm';
            }
        }
        else{
            $size = '';
        }
        
        //check viewpoint
        if($viewpoint->isMobile()){
            $env = 'env-mobile';
        }
        else{
            $env = 'env-desktop';
        }
                
        $modal = '<div class="modal fade " id="'.$settings['id'].'" role="'.(array_key_exists('role', $settings) ? $settings['role'] : '').'" aria-labelledby="'.$settings['id'].'Label" aria-hidden="true">
                  <div class="modal-dialog '.$size.' '.$env.'">
                    <div class="modal-content">
                      <div class="modal-body">
                        '.$content.'
                      </div>
                    </div>
                  </div>
                </div>';
        
        return $modal;
    }
    
    /**
     * Inserts generated content outer settings into a Bootstrap Modal form.
     * 
     * NOTE: The form should be loaded using the boostrap ajax feature
     * 
     * @param array $settings
     * @param type $form
     * @param type $content
     * 
     * @return type
     */
    public static function ModalDialog(array $settings, $content = '', $disable_submit = false){
        
        $build = $buttons = null;
        
        //register overlay caching mechanism into HTML processing queue
        HTML::register('overlay_modal');
        
        //generate the submit button javascript
        if(array_key_exists('buttons', $settings)){
            foreach($settings['buttons'] as $button){

                //check if its a submit button
                if(array_key_exists('type', $button)){

                    if($button['type'] == strtolower('submit')){

                        if(isset($button['id'])){
                            $submit = $button['id'];
                        }

                        if($disable_submit == false){

                            if(array_key_exists('message', $settings)){
                                $message = $settings['message'];
                            }
                            else{
                                $message = 'Operation in Progress ...';
                            }

                            if(array_key_exists('submitmode', $settings)){

                                $build = HTML::script('$( document ).ready(function() {
                                    $("button#'.$submit.'").bind(\'click\',function() { 
                                       $("#'.$settings['formid'].'").submit();
                                   });
                                });','script',TRUE);
                            }
                            else{
                                $build = HTML::script('$( document ).ready(function() {
                                    $("button#'.$submit.'").bind(\'click\',function() { 
                                       jng.saveFromOverlay("#'.$settings['formid'].'", "'.$message.'");
                                   });
                                });','script',TRUE);
                            }
                        }
                    }
                }
            }

            $buttons = self::_processButtons($settings['buttons']);
        }
        
        if($content == ''){            
            $content = '<div class="showpreload" style="width:100%;text-align:center;opacity:0.5;">'
                            . '<img src="'.RELATIVE_APP_PATH .'/views/loading/logo.gif" />'
                        . '</div>';
        }
        
        $viewpoint = App::get('viewpoint');
        
        $build .= '<div class="modal-header dialog">';
        
        if($viewpoint->isMobile()){
            $build .= '<a class="close close-modal-dialog" data-dismiss="modal" aria-label="Close">'
                        . '<i class="fa fa-arrow-left"></i>'
                    . '</a>'
                    . '<h4 class="modal-title" id="'.$settings['id'].'Label">'.$settings['title'].'</h4>';
        }
        else{
            if(!is_null(self::$title_addons)){
                $build .= '<div class="row">
                            <div class="col-md-6">
                                <h4 class="modal-title" id="'.$settings['id'].'Label">'.$settings['title'].'</h4>
                            </div>
                            <div class="col-md-5">
                                '.self::$title_addons.'
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                        </div>';
            }
            else{
                $build .= '<div class="row">
                            <div class="col-md-11">
                                <h4 class="modal-title" id="'.$settings['id'].'Label">'.$settings['title'].'</h4>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                        </div>';
            }
        }
        
        $build .= '</div>
                    <div class="modal-body">
                    '.$content.'
                  </div>
                  <div class="modal-footer">
                    '.$buttons.'
                  </div>';
        
        return $build;
    }
    
    /**
     * Inserts a confirm dialog based on data-confirm attrbute.
     * 
     * @param none No parameters are needed, 
     *            just add the data-confirm attribute to the related tag with the question to be asked
     */
    public static function confirm(){
        
        $build = HTML::script("$(document).ready(function() {
	$('a[data-confirm]').click(function(ev) {
		var href = $(this).attr('href');
		if (!$('#dataConfirmModal').length) {
			$('body').append('<div id=\"dataConfirmModal\" class=\"modal fade\" role=\"dialog\" aria-labelledby=\"dataConfirmLabel\" aria-hidden=\"true\"> \
                                            <div class=\"modal-dialog\"> \
                                                <div class=\"modal-content\"> \
                                                    <div class=\"modal-header\"> \
                                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-hidden=\"true\">×</button> \
                                                        <h3 id=\"dataConfirmLabel\" class=\"modal-title\">Please Confirm</h3> \
                                                    </div> \
                                                    <div class=\"modal-body\"></div><div class=\"modal-footer\"><button class=\"btn\" data-dismiss=\"modal\" aria-hidden=\"true\">Cancel</button><a class=\"btn btn-primary\" id=\"dataConfirmOK\">OK</a></div></div></div></div>');
		} 
		$('#dataConfirmModal').find('.modal-body').text($(this).attr('data-confirm'));
		$('#dataConfirmOK').attr('href', href);
		$('#dataConfirmModal').modal({show:true});
		return false;
            });
        });",'script',TRUE);
        
        return $build;
    }

        /**
     * Processes the modal buttons
     * 
     * @param array $buttons
     * @return string
     */
    private static function _processButtons(array $buttons){
        
        $build = '';
        $names = array_keys($buttons);
        
        $count = 0;
        foreach($buttons as $button){
            
            //check if the type has been set
            if(isset($button['type'])){                
                $type = 'type="'.$button['type'].'"';
                unset($button['type']);
            }
            else{                
                $type = 'type="button"';
            }
            
            //skip cancel button on mibile
            if(App::get('viewpoint')->isMobile() && $names[$count] == 'Cancel'){
            
                $count++;
                continue;
            }
            else{
                $attr = self::_processAttributes($button);
                $build .= '<button '.$type.' '.$attr.'>'.$names[$count].'</button>';
            
            $count++;
            }
        }
        
        return $build;
    }
    
    /**
     * Processes the attributes and returns a string 
     * of the properties to be inserted
     * 
     * @param type $properties
     * @return string $properties_string
     */
    private static function _processAttributes($properties){
        
        $properties_string = '';
        foreach($properties as $attr => $value){
            $properties_string .= $attr.' = "'.$value.'" ';
        }
        
        return $properties_string;
    }
}