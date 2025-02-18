<?php
/**
 * This class processes the Project.map.xml file and assigns the various project elements and their various attributes
 * 
 * @author Stanley Ngumo <sngumo@gmail.com>
 * 
 */
namespace Jenga\App\Project\Elements;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Request\Facade\Sanitize;
use League\Flysystem\FileExistsException;

class XmlElements{
    
    public $elements = [];
    public $rootPath;
    public $absolutepath;
    public $fileManager;
    
    private $xmlelement;
    private $xmlselect;
    private $xmlElementAttributes;
    private $xmlFolderAttributes;
    private $xmlFileAttributes;
    private $xmlChildNodes;
    private $sanitize;
    private $xmlfile;
    private $_templatekey;
    
    public function __construct( Sanitize $sanitize ){
        
        $this->sanitize = $sanitize;
        
        $this->xmlElementAttributes = array('name','function','path','scope','attachto','default');
        $this->xmlFolderAttributes = array('function','autoload','primary','attachto');
        $this->xmlFileAttributes = array('function','autoload');
        $this->xmlChildNodes = array('folder','file');
    }

    private function _initDocument($filename, $formatting = false){
        
        $xmlDoc = new \DOMDocument($filename);
        
        if($formatting){
            
            $xmlDoc->preserveWhiteSpace = false;
            $xmlDoc->formatOutput = true;
        }
        
        try{
            $xmlDoc->load($filename);
        } 
        catch (\Exception $ex) {
            App::critical_error('<strong>'.$ex->getMessage().'</strong><br>');
        }
        
        return $xmlDoc;
    }
    
    private function _preparePath($path) {
        
        $path = ($path === null ? "" : trim($path));
        $length = strlen($path);
        
        if($length > 0) {
            
            $lastChar = substr($path, $length-1, 1);
            $path = ($lastChar == '/' || $lastChar == '\\') ? $path : $path;
                
        } else {            
            $path = "/";
        }
        
        return $path;
    }
    
    /**
     * Cleans the elements based on filter rules
     * 
     * @param type $xmlparam
     * @return type
     */
    private function _clean(&$xmlparam) {
        
        /**
            //add the filter rule 
            Sanitize::add_filter('xmltolower', function($value, $params = NULL){
                return strtolower($value);
            });
         * 
         */
        
        $xml = array('vals' => $xmlparam);        
        $filter_rules = array('vals' => 'trim|sanitize_string|noise_words'); 
        
        $clean = $this->sanitize->filter($xml, $filter_rules);
        $xmlparam = $clean['vals'];
    }
    
    /**
     * Loads the xml-defined elements and assets into the framework
     * 
     * @param string $filename
     * @param string $map_path
     * 
     */
    public function loadXml($filename,$map_path){
        
        $this->rootPath = $this->_preparePath($map_path);
        
        if(!File::exists( $this->rootPath .DS. $filename ))
            App::critical_error ('XML document not found: '.$this->rootPath .DS. $filename);
        
        $xmlDoc = $this->_initDocument($this->rootPath .DS. $filename);
        
        //get the project elements
        $elements = $xmlDoc->getElementsByTagName('element');
            
        foreach($elements as $element){
        
            if(count($this->elements) == 0 ){
                $this->elements = $this->_loadElement($element);
            }
            else{
                $this->elements = array_merge($this->elements,$this->_loadElement($element));
            }
        }
        
        //isolate the template key
        if(!is_null($this->_templatekey) && !is_null($this->elements[$this->_templatekey]))
            $this->elements['templates'] = $this->elements[$this->_templatekey];
        
        //clean the element values
        //array_walk_recursive($this->element, array($this, '_clean'));
        
        $elmpath = APP_PROJECT .DS. 'elements.php';
        
        $file = App::get(File::class);
        
       $filesave =$file->put($elmpath, serialize($this->elements)); 
       
       if($filesave === false){
           
            //change the file permissions
            chmod($elmpath, 0777);
           
           //delete the file
           $file->delete($elmpath);
           
           //update then
           $file->cleanPath($elmpath);
           $file->getDisk()->write($elmpath, serialize($this->elements));
       }
        
       return $filesave;
    }
    
    /**
     * Load and returns a XML file
     * 
     * @param type $filename
     * @param type $map_path
     * @return boolean
     */
    public function loadXMLFile($filename,$map_path, $formatting = FALSE){
        
        $path = $this->_preparePath($map_path);
        $this->xmlfile = $this->_initDocument($path .DS. $filename, $formatting);
        
        $this->absolutepath = rtrim($path,'/') .DS. $filename;
        
        return $this->xmlfile;
    }
    
    /**
     * Creates and inserts the new attributes into the selected element
     * 
     * @param type $attribute
     * @param type $value
     * @return boolean
     */
    public function createAttribute($attribute, $value){
        
        if(isset($this->xmlselect)){
            
            foreach ($this->xmlselect as $node) {              
                $node->setAttribute($attribute, $value);
            }
            
            return TRUE;
        }
        else{
            return false;
        }
    }
    
    /**
     * Deletes the selected XML element
     * 
     * @return boolean
     */
    public function deleteXMLElement(){
        
        if(isset($this->xmlselect)){
            
            foreach ($this->xmlselect as $node) {    
                $node->parentNode->removeChild($node);
            }
            
            $this->save();
        }
        else{
            return false;
        }
    }
    
    /**
     * Add the first element in the maps.xml
     * 
     * @param type $name
     * @param array $folders
     * @param array $attributes
     */
    public function addElement($name, array $folders, array $attributes){
        
        $elements = $this->xmlfile->getElementsByTagName('elements')->item(0);
        
        //if there are no other elements, the first one is set as a default
        if(!$elements->hasChildNodes()){
            $attributes['default'] = 'true';
        }
        
        $element = $this->xmlfile->createElement('element');   
        
        //attributes
        if(count($attributes) > 0 ){
            
            foreach ($attributes as $key => $attr) {
                $element->setAttribute($key, $attr);
            }
        }
        
        $elements->appendChild($element);
        
        //create folders
        if(count($folders) > 0){
            
            foreach($folders as $function => $name){
                
                $folder = $this->xmlfile->createElement('folder',$name);
                $folder->setAttribute('function', $function);
                
                $element->appendChild($folder);
            }
        }
        
        $this->save();
    }
    
    /**
     * Creates the main templates element
     * @param type $attrs
     */
    public function createTemplateElement($attrs = null){
        
        $elements = $this->xmlfile->getElementsByTagName('elements')->item(0);        
        $element = $this->xmlfile->createElement('element');   
        
        //attributes
        if(count($attrs) > 0 ){
            
            foreach ($attrs as $key => $attr) {
                $element->setAttribute($key, $attr);
            }
        }
        
        //add element to overall elements
        $elements->appendChild($element);
        
        //save changes
        $this->save();
    }
    
    /**
     * Saves the XML file edits
     * 
     * @return type
     */
    public function save(){        
        return $this->xmlfile->save($this->absolutepath);
    }
    
    /**
     * Removes the sent attribute
     * @param type $attribute
     * @return boolean
     */
    public function removeAttribute($attribute){
        
        if(isset($this->xmlselect)){            
            foreach ($this->xmlselect as $node) {              
                $node->removeAttribute($attribute);
            }
        }
        else{
            return false;
        }
    }
    
    /**
     * Picks the designated xml element to be modified
     * 
     * @param type $elementname
     */
    public function selectXMLElement($elementname){
        
        if(isset($this->xmlfile)){
            
            $xpath = new \DOMXPath($this->xmlfile);
            $xquery = "//elements/element[@name='".$elementname."']";
            
            //
            $results = $xpath->query($xquery);
            
            if($results->length >= 1){                
                $this->xmlselect = $xpath->query($xquery);
                return true;
            }        
        }
        
        return false;
    }
    
    /**
     * Processes the sent XML elements by the various attributes and combines it into the array
     * 
     * @param string $element
     */
    private function _loadElement($element){
        
        //assign the element name 
        $e_name = strtolower( $element->getAttribute('name') );
        $element_array[ $e_name ] = array();
        
        if($element->hasAttributes()){
            
            foreach($element->attributes as $attribute){
                
                $name = $attribute->nodeName;
                $value = $attribute->nodeValue;
                
                $element_array[ $e_name ][$name] = $value;
                
                //isolate templates for special attention
                if($name == 'function' && $value == 'templates'){
                    $this->_templatekey = $e_name;
                }
                else{
                    $this->_templatekey = NULL;
                }
            }
        }
        
        //check for the default attribute
        if($element->hasAttribute('default')){            
            $element_array[ $e_name ]['default'] = $element->getAttribute('default');
        }
        
        //check for function attribute - this is for the main template section
        if($element->hasAttribute('function')){       
            $element_array[ $e_name ]['function'] = $element->getAttribute('function');
        }
        
        if($element->hasChildNodes()){
            
            //check for folders and process
            $element_folders = $element->getElementsByTagName('folder');
            
            if($element_folders->length != 0){

                //process the folders within the element
                foreach($element_folders as $folder){  
                    $element_array = $this->_registerNodes($e_name,$element_array, $folder, 'folder');                  
                }
            }
            
            //check for files and process
            $element_files = $element->getElementsByTagName('file');
            
            if($element_files->length!=0 ){
                
                foreach($element_files as $file){                    
                    $element_array = $this->_registerNodes($e_name,$element_array, $file, 'file');                    
                }
            }
            
            return $element_array;
        }        
    }
    
    /**
     * It registers the child nodes for each element
     * 
     * @param type $e_name Element Name
     * @param type $element_array
     * @param type $domobject
     * @param type $nodetype
     * @return array
     */
    private function _registerNodes($e_name,$element_array, $domobject, $nodetype){
        
        if($nodetype == 'folder'){
            
            //push folder name into element folders array 
            if(array_key_exists('folders', $element_array[$e_name]) && !is_array($element_array[$e_name]['folders']))
                $element_array[$e_name]['folders'] = array($domobject->nodeValue);
            else
                $element_array[$e_name]['folders'] = [$domobject->nodeValue];
            
            //check against the registered attributes
            $attributes = $this->xmlFolderAttributes;
            
            //look for the files inside the specified element folder
            $rpath = str_replace('/',DS,$element_array[ $e_name ]['path']);
            $directories = File::scandir(PROJECT_PATH .DS. $rpath .DS. $domobject->nodeValue);
            
            //removes all internal folders in each element
            $files = array_intersect_key($directories, array_flip(array_filter(array_keys($directories), 'is_numeric')));
        }
        elseif($nodetype == 'file'){ 
            
            //push file name into element file array
            if(!is_array($element_array[$e_name]['files'])) 
                $element_array[$e_name]['files'] = array($domobject->nodeValue);
            else
                array_push ($element_array[$e_name]['files'], $domobject->nodeValue);

            //check against the registered attributes
            $attributes = $this->xmlFileAttributes;            
        }        
        
        foreach($attributes as $attr){
            
            if($domobject->hasAttribute($attr)){   
                
                if($attr == 'function'){
                    
                    switch ($domobject->getAttribute($attr)){ 

                        case 'model':
                            
                            if($nodetype == 'folder'){
                                
                                foreach($files as $file){
                                    
                                    if(is_array($file)){
                                        $splitfile = explode( DS,$file[0] );
                                    }
                                    else{
                                        $splitfile = explode( DS,$file );
                                    }
                                    
                                    $mdlfile = end($splitfile);
                                    $mdlfilename = explode('.', $mdlfile);

                                    $mdl = $mdlfilename[0];

                                    //check if model exists
                                    if(isset($element_array[$e_name][ 'models' ][ $mdl ][ 'folder' ])){
                                        App::critical_error('You cannot have two models with the same name in one element');
                                    }

                                    $element_array[$e_name][ 'models' ][ $mdl ][ 'folder' ] = $domobject->nodeValue;

                                    //remove the initial ../project/ sections
                                    $key = (int) array_search('project',$splitfile);
                                    $key = $key+1;
                                    $array_short = array_slice($splitfile,$key);

                                    $fullpath = join(DS, $array_short);                                
                                    $element_array[$e_name][ 'models' ][ $mdl ][ 'path' ] = $fullpath;
                                }
                            }
                            elseif($nodetype == 'file'){
                                
                                $mdlfile = $domobject->nodeValue;                                
                                $fullpath = $element_array[ $e_name ]['path'] .'/'. $mdlfile;
                                
                                $mdl = explode('.',$mdlfile);
                                $element_array[$e_name][ 'models' ][ $mdl[0] ][ 'path' ] = $fullpath;
                            }                            
                        break;

                        case 'controller':                                    
                            
                            if($nodetype == 'folder'){
                                
                                foreach($files as $file){

                                    if(is_array($file)){
                                        $splitfile = explode( DS,$file[0] );
                                    }
                                    else{
                                        $splitfile = explode( DS,$file );
                                    }
                                    $ctrfile = end($splitfile);
                                    
                                    $ctrfilename = explode('.', $ctrfile);

                                    $ctr = $ctrfilename[0];

                                    //check if controller exists
                                    if(isset($element_array[$e_name][ 'controllers' ][ $ctr ][ 'folder' ]))
                                        App::critical_error('You cannot have two controllers with the same name in one element');

                                    $element_array[$e_name][ 'controllers' ][ $ctr ][ 'folder' ] = $domobject->nodeValue;

                                    //remove the initial ../project/ sections
                                    $key = (int) array_search('project',$splitfile);
                                    $key = $key+1;
                                    $array_short = array_slice($splitfile,$key);

                                    $fullpath = join(DS, $array_short);
                                    
                                    $element_array[$e_name][ 'controllers' ][ $ctr ][ 'path' ] = $fullpath;
                                }
                            }
                            elseif($nodetype == 'file'){
                                
                                $ctrfile = $domobject->nodeValue;                                
                                $fullpath = $element_array[ $e_name ]['path'] .'/'. $ctrfile;
                                
                                $ctr = explode('.',$ctrfile);
                                $element_array[$e_name][ 'controllers' ][ $ctr[0] ][ 'path' ] = $fullpath;
                            }
                        break;

                        case 'view':                                    
                            if($nodetype == 'folder'){
                                
                                foreach($files as $file){ 
                                    
                                    //jump the panels folder within the view folder
                                    if(is_array($file)){                                        
                                        continue;
                                    }
                                    
                                    $splitfile = explode( DS,$file );
                                    $tplfile = end($splitfile);

                                    $tplfilename = explode('.', $tplfile);

                                    $tpl = $tplfilename[0];

                                    //check if view exists
                                    if(isset($element_array[$e_name][ 'views' ][ $tpl ][ 'folder' ]))
                                        App::critical_error('You cannot have two views with the same name in one element');

                                    $element_array[$e_name][ 'views' ][ $tpl ][ 'folder' ] = $domobject->nodeValue;

                                    //remove the initial ../project/ sections
                                    $key = (int) array_search('project',$splitfile);
                                    $key = $key+1;
                                    $array_short = array_slice($splitfile,$key);

                                    $fullpath = join(DS, $array_short);                                
                                    $element_array[$e_name][ 'views' ][ $tpl ][ 'path' ] = $fullpath;
                                }
                            }
                            elseif($nodetype == 'file'){
                                
                                //jump the panels folder within the view folder
                                if(is_array($file)){

                                    continue;
                                }
                                
                                $tplfile = $domobject->nodeValue;                                
                                $fullpath = $element_array[ $e_name ]['path'] .'/'. $tplfile;
                                
                                $tpl = explode('.',$tplfile);
                                $element_array[$e_name][ 'views' ][ $tpl[0] ][ 'path' ] = $fullpath;
                            }                          
                        break;
                        
                        case 'schema':
                            
                            if($nodetype == 'folder'){
                                
                                foreach($files as $file){
                                    
                                    $splitfile = explode( DS,$file );
                                    $tplfile = end($splitfile);

                                    $tplfilename = explode('.', $tplfile);

                                    $tpl = $tplfilename[0];
                                    
                                    //check if schema exists
                                    if(isset($element_array[$e_name][ 'views' ][ $tpl ][ 'folder' ]))
                                        App::critical_error('You cannot have two views with the same name in one element');

                                    $element_array[$e_name][ 'schema' ][ $tpl ][ 'folder' ] = $domobject->nodeValue;

                                    //remove the initial ../project/ sections
                                    $key = (int) array_search('project',$splitfile);
                                    $key = $key+1;
                                    $array_short = array_slice($splitfile,$key);

                                    $fullpath = join(DS, $array_short);                                
                                    $element_array[$e_name][ 'schema' ][ $tpl ][ 'path' ] = $fullpath;
                                }
                            }
                            elseif($nodetype == 'file'){
                                
                                $tplfile = $domobject->nodeValue;                                
                                $fullpath = $element_array[ $e_name ]['path'] .'/'. $tplfile;
                                
                                $tpl = explode('.',$tplfile);
                                $element_array[$e_name][ 'schema' ][ $tpl[0] ][ 'path' ] = $fullpath;
                            }
                            break;
                    }
                }
                elseif($attr == 'autoload'){
                    
                    switch ($domobject->getAttribute($attr)){

                        case 'true':                                    
                            if(!is_array($element_array[$e_name][ 'autoload' ]))
                                $element_array[$e_name][ 'autoload' ] = array($domobject->nodeValue); 
                            else
                                array_push($element_array[$e_name][ 'autoload' ], $domobject->nodeValue); 
                        break;

                    }
                }
                elseif($attr == 'primary'){
                    
                    switch ($domobject->getAttribute($attr)){
                        
                        case 'true':
                                $element_array[$e_name][ 'primary' ] = [$domobject->nodeValue]; 
                            break;
                        
                    }
                }
                elseif($attr == 'attachto'){
                    
                    if(!is_array($element_array[$e_name][ 'attachto' ]))
                        $element_array[$e_name][ 'attachto' ] = array($domobject->getAttribute('attachto')); 
                    else
                        array_push($element_array[$e_name][ 'attachto' ], $domobject->getAttribute('attachto')); 
                }
            }
        }
        
        return $element_array;        
    }
}

