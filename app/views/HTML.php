<?php
namespace Jenga\App\Views;

use Jenga\App\Core\App;
use Jenga\App\Request\Url;

class HTML {
    
    public static $notifications;
    public static $tracker = [];
    public static $keywords = ['gridTableTools','gridTableShortcuts','overlay_modal','rowreorder'];

    protected static $head_list = [];
    
    /**
     * Registers resources to be loaded into the head section of the view
     * 
     * @param type $resource
     */
    public static function register($resource){
       
        $resources = [];
        
        if(App::$shell->has('current_page')){
            
            $resources = App::$shell->get('current_page');
            
            //add new entry
            if(is_string($resources)){
                $str = $resources;
                
                $resources = [];
                $resources[] = $str;
            }
            elseif(is_null($resources)){
                $resources = [];
            }
            elseif(is_array($resource)){
                foreach($resource as $asset){
                    $resources[] = $asset;
                }
            }
            
            //add to resources
            if(!in_array($resource, $resources) && !is_array($resource)){
                array_push($resources, $resource);
            }
        }
        else{
            $resources = $resource;
        }       
        
        App::$shell->set('current_page',$resources);
    }

    /**
     * Load the initial starting configurations for the full HTML view
     */
    public static function start(){     
        //insert the notifications class
        self::$notifications = new Notifications();
    }
    
    /**
     * Collects all the scripting components declared in all the 
     * panels and consolidates them in a single section
     */
    public static function end(){
        
        $htmlassets = $resource = $script = $build = null;
        echo '<div class="preload" style="display:none">'
                . self::AddPreloader('center','100','100')
            . '</div>';
        
        //get viewpoint MobileDetect class
        $viewpoint = App::get('viewpoint');
        if(count(self::$tracker)>='1'){
            
            $tooltipset = false;
            
            foreach(self::$tracker as $component){
                
                if($component == 'tooltip'){
                    
                    //detect device
                    if($viewpoint->isMobile() || $viewpoint->isTablet()){                        
                        $options = '{ trigger: "click" }';
                    }
                    
                    //initialize all tooltips
                    if($tooltipset === FALSE){                        
                        $build .= "$('[data-toggle=\"tooltip\"]').tooltip(".$options."); ";
                        $tooltipset = TRUE;
                    }
                    
                    //remove entry to avoid duplicate entries
                    $key = array_search('tooltip', self::$tracker);
                    unset(self::$tracker[$key]);
                }
                elseif($component == 'popover'){
                    
                    //detect device
                    if(!$viewpoint->isMobile() && !$viewpoint->isTablet() ){                        
                        $options = '{ trigger: "hover" }';
                    }
                    
                    //initialize all popovers
                    $build .= "$('[data-toggle=\"popover\"]').popover(".$options."); ";
                    
                    //remove entry to avoid duplicate entries
                    $key = array_search('popover', self::$tracker);
                    unset(self::$tracker[$key]);
                }
            }
        }
        
        //check for dataTable        
        if(App::$shell->has('current_page')){

            if(array_key_exists('body', App::$shell->get('current_page'))){
                
                //check the gridTable
                if(in_array('gridTableTools', App::$shell->get('current_page'))){
                    $resource[] = 'gridTableTools';
                }
                
                $resource[] = App::$shell->get('current_page')['body'];  
            }
            else{
                $resource = App::$shell->get('current_page');
            }
           
            //clear the head resources
            self::clearHeadResources($resource, self::$head_list);
            
            if(!is_null($resource)){
                
                foreach($resource as $htmlresources){        
                    
                    if(is_string($htmlresources) && !in_array($htmlresources, self::$keywords)){                        
                        $htmlassets .= html_entity_decode($htmlresources);
                    }
                    elseif(is_array($htmlresources)){
                        foreach($htmlresources as $htmlresource){
                            $htmlassets .= html_entity_decode($htmlresource);
                        }
                    }
                }
            }
            
            if(is_string($resource)){

                $str = $resource;

                $resource = [];
                $resource[] = $str;
            }
            elseif(is_null($resource)){
                $resource = [];
            }
        }
        
        if(!is_null($resource)){
            
            //destroy the modal content on close
            $script .= "$('body').on('hidden.bs.modal', '.modal', function () {
                            $(this).find('.modal-body').html('');
                        });";
            
            $script .= '//this is to prevent caching of remote bootstrap modals
                    $("body").on("click","a[data-toggle=modal]",function(ev){

                        ev.preventDefault();
                        var target = $(this).attr("href");
                        var modalname = $(this).attr("data-target");

                        if(target != "#"){ 
                        
                            $(modalname + " .modal-content").html(\''.self::AddPreloader('center','100','100').'\');
                            $.ajax({url: target, cache: false})
                                .done(function(html){
                                    $(modalname + " .modal-content").html(html);
                                });
                        }
                    });';

            //add the datatables resources
            if(in_array('gridTableTools', $resource)){

                //datatables scripts and css
                echo '<script src="'. RELATIVE_APP_PATH .'/html/tables/scripts/jng.gridTableTools.js"></script>';
            }

            //add the datatables shortcut menu
            if(in_array('gridTableShortcuts', $resource)){
                echo '<script src="'. RELATIVE_APP_PATH .'/html/tables/scripts/jq.gridTableShortcuts.js"></script>';
            }

            //add the datatables reorder
            if(in_array('rowreorder', $resource)){
                echo '<script src="'. RELATIVE_APP_PATH .'/html/facade/DataTables/plugins/rowReordering/jquery.dataTables.rowReordering.js"></script>';
            }
        }
        
        if($htmlassets != ''){
            echo $htmlassets;
        }
        
        if($script != '' || $build != ''){
            
            self::script('$(function () {'
                            . $script
                            . $build
                        .'})');
        }
    }
    
    /**
     * Clears the duplicates in the footer
     * @param type $footer
     * @param type $header
     */
    protected static function clearHeadResources(&$footer_resources, $header) {
        
        //loop through footer resources
        foreach($footer_resources as $key => &$resources){
            
            if(in_array($resources, $header)){
                unset($footer_resources[$key]);
            }
        }
    }

    /**
     * Loads the referenced CSS file
     * 
     * @param type $csspath
     * @param type $removetemplatepath
     * @param type $inline_css
     */
    public static function css($csspath, $removetemplatepath = FALSE, $inline_css = FALSE){
        
        if($removetemplatepath == FALSE){            
            $tmp_path = TEMPLATE_URL;
        }
        
        if($inline_css == false){
            echo '<link href="'.$tmp_path.$csspath.'" rel="stylesheet" type="text/css" />';
        }
        else{
            
            $filepath = str_replace('/',DS, ABSOLUTE_PROJECT_PATH .DS. 'templates' .DS. $csspath);
            $filecontents = file_get_contents($filepath);
            
            echo '<style>'.$filecontents.'</style>';
        }
    }
    
    /**
     * Wraps the jquery script or file
     * 
     * @param type $jscript
     * @param type $type
     * @param type $return
     * 
     * @return string
     */
    public static function script($jscript, $type = 'script', $return = FALSE){
        
        if($type == '' || $type == 'script'){            
            $script = '<script type="text/javascript">'.$jscript.'</script>';
        }
        elseif($type == 'file'){            
            $script = '<script src="'.$jscript.'"></script>';
        }
        
        if($return == FALSE){ echo $script; }
        else{ return $script; }
    }
    
    /**
     * Function synonym for self::script() within the templates
     * 
     * @param type $script_path
     * @param type $return
     */
    public static function js($script_path, $return = FALSE) {
        self::script(TEMPLATE_URL.$script_path, 'file', $return);
    }


    /**
     * Load the HTML head section
     */
    public static function head($use_native_componenets = TRUE, $js_engine = 'jQuery'){
        
        //start the notifications
        self::start();
        
        if($use_native_componenets){
            
            switch($js_engine){                
                case "jQuery":
                    self::jQueryInit();
                    break;
            }
        }
        
        //load the registered resources
        if(App::$shell->has('current_page')){
            
            $resources = App::$shell->get('current_page');
            if(is_string($resources)){
                $str = $resources;
                
                $resources = [];
                $resources[] = $str;
            }
            
            if(!is_null($resources)){
               
                foreach($resources as $key => $resource){
                    if($key !== 'body' && !in_array($resource, self::$keywords)){
                        
                        echo html_entity_decode($resource);
                    
                        //remove entry to avoid duplicate entries
                        self::$head_list[] = $resource;
                    }
                }
            }
            
            App::bind('current_page',$resources);
        }     
    }
    
    public static function notifications(){        
        self::$notifications->display();
    }
    
    /**
     * Loads the jQuery main page file and the respective detection files
     * @param type $return
     * @return type
     */
    public static function jQueryInit($return = false){ 
        
        $jq = '<script src="'.RELATIVE_VIEWS .'/js/detect.js"></script>'
                . '<script src="'.RELATIVE_VIEWS .'/js/fastclick.js"></script>'
                . '<script src="'.RELATIVE_VIEWS .'/js/jquery.page.js"></script>';
        
        $jq .= self::script('$(function(){
                        jng = new JengaPage({
                            base: "'.RELATIVE_ROOT.'",
                            viewpath: "'.RELATIVE_VIEWS.'",
                            current: "'.Url::current().'"
                        });
                    });','script', TRUE);
        
        if($return == FALSE){
            echo $jq;
        }
        
        return $jq;
    }
    
    /**
     * returns the default preloader image and HTML
     * 
     * @param type $width
     * @param type $height
     * @return type
     */
    public static function AddPreloader($align='center',$width = null, $height = null, $refreshmsg = TRUE, $loadertxt = null ){
        
        $loader = '<div class="showpreload" style="text-align:'.$align.';opacity:0.9;">'
                    . '<img src="'.RELATIVE_APP_PATH .'/views/loading/fups-loader.gif"'
                    . (!is_null($width) ? 'width="'.$width.'" ' : '') 
                    . (!is_null($width) ? 'height="'.$height.'" ' : '') 
                    .' />';
        
        if($refreshmsg == TRUE){
            $loader .= '<p style="font-size: small; color: grey">';
            
            if(!is_null($loadertxt)){
                $loader .= $loadertxt;
            }
            
            $loader .= '</p>';
        }
        
        $loader .= '</div>';
        
        return $loader;
    }
    
    public static function shortenUrls($data) {            
        $data = preg_replace_callback('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', array(get_class(self), '_fetchTinyUrl'), $data);
        return $data;
    }

    public static function fetchTinyUrl($url) { 
        
        $ch = curl_init(); 
        
        $timeout = 5; 
        curl_setopt($ch,CURLOPT_URL,'http://tinyurl.com/api-create.php?url='.$url[0]); 
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1); 
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout); 
        $data = curl_exec($ch); 
        curl_close($ch); 
            
        return '<a href="'.$data.'" target = "_blank" >'.$data.'</a>'; 
    }
    
    /**
     * Searches the sent haystack and returns the first found text inside the 
     * specified tags.
     * 
     * If recursive is set, it will return an array containing all the results
     * from searching the whole haystack
     * 
     * @param type $start_tag
     * @param type $end_tag
     * @param type $haystack
     * @param type $recursive
     * @return mixed
     */
    public static function findInTags($start_tag,$end_tag,$haystack,$recursive = FALSE){
        
        $startno = strlen($start_tag);
        $start_pos = strpos($haystack,$start_tag)+$startno;

        if ($start_pos === FALSE){
            return FALSE;
        }

        $end_pos = strpos($haystack,$end_tag);

        if ($end_pos === FALSE){
            return FALSE;
        }

        if($recursive === FALSE){            
            return substr($haystack, $start_pos, ($end_pos-$start_pos));
        }
        else{
            
            $value[] = substr($haystack, $start_pos, ($end_pos-$start_pos));
            $strcount = substr_count($haystack, $start_tag);
            
            for($r=1; $r<=$strcount; $r++){

                //create new haystack
                $haystack = substr($haystack, ($end_pos+1));
                $strcount = substr_count($haystack, $start_tag);
                
                if($strcount >= '1'){

                    $value = array_merge($value, self::findInTags($start_tag, $end_tag, $haystack, TRUE));
                }
            }
            
            return $value;
        }
    }
    
    /**
     * Processes any sent attributes from the HTML
     * 
     * @param type $attr
     * @return string
     */
    private static function _parseAttributes($attr){
        
        foreach($attr as $attrname => $attrvalue){
            
            $attributes .= $attrname.'="'.$attrvalue.'" ';
        }
        
        return $attributes;
    }
    
    /**
     * Inserts the Bootstrap heading
     * 
     * @param type $content
     */
    public static function heading($type, $content, $attr = array()){
        
        if(count($attr) >= 1){
            
            $attrs = self::_parseAttributes($attr);
        }
        
        $heading = '<div class="panel-heading">';        
        $heading .= '<'.$type.' '.$attrs.'>'.$content.'</'.$type.'>';        
        $heading .= '</div>';
        
        return $heading;
    }
    
    /**
     * Prints current page
     */
    public static function printPage(){
        
        self::script('window.onload = function () {
                window.print();
            }');
    }
    
    /**
     * Returns simple table based on single ORM object properties
     * 
     * @param string $type either bootstrap or raw
     * @param object $contents_object
     * @param array $table_attrs Any attributes to be added to the table
     * 
     * @return html Fully rendered table
     */
    public static function simpleTable($type, $contents_object, $table_attrs = array()){
        
        if($type == 'raw'){
            
            if(count($table_attrs) >= 1){
            
                $attrs = self::_parseAttributes($table_attrs);
            }
        }
        elseif($type == 'bootstrap'){
            
            if(isset($table_attrs['class'])){
                
                $attrs = 'class="table '.$table_attrs['class'].'"';
                unset($table_attrs['class']);
            }
            
            if(count($table_attrs) >= 1){
            
                $attrs .= self::_parseAttributes($table_attrs);
            }
        }
        
        $table = '<table '.$attrs.'>';
        $table .= '<tbody>';
        
        if(!is_null($contents_object)){
            
            $properties = get_object_vars($contents_object);

            foreach($properties as $property => $value){

                if(is_null($value)){

                    $value = 'Not Specified';
                }

                $table .= '<tr>'
                        . '<td><strong>'.str_replace('_',' ',ucwords($property)).'</strong></td>'
                        . '<td>'.$value.'</td>'
                        . '</tr>';

            }

            $table .= '</tbody>'
                    . '</table>';
        }
        else{            
            $table = Notifications::Alert('No data provided', 'info', TRUE, TRUE);
        }
        
        return $table;
    }
}