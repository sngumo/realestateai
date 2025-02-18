<?php
namespace Jenga\App\Database\Systems\Pdo\Schema;

use Jenga\App\Core\File;
use Jenga\App\Helpers\Help;
use Jenga\App\Project\Core\Project;

/**
 * This class writes the schema class for the respective elements
 * @author stanley
 */
class AnnotationsWriter {
    
    /**
     * The table schematic
     * @var type 
     */
    protected static $schematic;
    
    /**
     * Flag for deleting schema file if it exists
     * @var type 
     */
    private static $_dropIfExists = false;
    
    /**
     * Flag for simple rebuild of existing schema file
     * @var type 
     */
    private static $_rebuildFromPrevious = false;
    
    /**
     * Path to the mould files
     * @var type 
     */
    private static $_mouldpath = ABSOLUTE_PATH .DS. 'app' .DS. 'build' .DS. 'moulds' .DS. 'element';
    
    /**
     * The schema class placeholder
     * @var type 
     */
    private static $_placeholder = "//{{{_______________WRITE_TABLE_COLUMNS_HERE_USING_ANNOTATOR________________}}}";
    
    /**
     * Start of the auto generated columns section
     * @var type 
     */
    private static $_autogen_start = "/****** AUTO-GENERATED COLUMNS ******/";
    
    /**
     * End of the auto generated columns section
     * @var type 
     */
    private static $_autogen_end = "/****** END OF AUTO-GENERATED COLUMNS ******/";
    
    /**
     * Deletes the existing schema table and creates a new schema class
     * @param type $class
     * @return new static
     */
    public static function dropAndCreate(){        
        static::$_dropIfExists = true;        
        return new static;
    }
    
    /**
     * Performs simple rebuild of existing schema file
     * @return \static
     */
    public static function rebuildFromPreviousSchema(){
        static::$_rebuildFromPrevious = true;
        return new static;
    }
    
    /**
     * The schema class to create
     * @param type $class The class name ONLY without any namespace
     * @return new static
     */
    public static function in($class){
        static::$schematic['schema'] = $class;
        return new static;
    }
    
    /**
     * The table column to be created, attributes will be inserted into the @var annotation as an JSON array
     * @param type $name
     * @param type $attr
     * @return new static
     */
    public static function column($name, $attributes = []){        
        static::$schematic['columns'][$name]['attributes'] = $attributes;        
        return new static;
    }
    
    /**
     * Adds the @primary annotation
     * @param type $column
     * @return new static
     */
    public static function primary($column = null){
        
        if(!is_null($column)){
            
            if(array_key_exists($column, static::$schematic['columns']))
                static::$schematic['columns'][$column]['primary'] = TRUE;
            else
                return 'COLUMN_NOT_FOUND';
        }
        else{
            $keys = array_keys(static::$schematic['columns']);
            $name = end($keys);
            static::$schematic['columns'][$name]['primary'] = true;
        }
        
        return new static;
    }
    
    /**
     * Adds the @unique annotation
     * @param type $column
     * @return new static
     */
    public static function unique($column = null){
        
        if(!is_null($column)){
            if(array_key_exists($column, static::$schematic['columns']))
                static::$schematic['columns'][$column]['unique'] = TRUE;
            else
                return 'COLUMN_NOT_FOUND';
        }
        else{
            $ar = array_keys(static::$schematic['columns']);
            $name = end($ar);
            static::$schematic['columns'][$name]['unique'] = true;
        }
        
        return new static;
    }
    
    /**
     * Adds the @comment annotation
     * @param type $statement
     * @return new static
     */
    public static function comment($statement, $column = null){
        
        if(!is_null($column)){
            if(array_key_exists($column, static::$schematic['columns']))
                static::$schematic['columns'][$column]['comment'] = $statement;
            else
                return 'COLUMN_NOT_FOUND';
        }
        else{
            $ar = array_keys(static::$schematic['columns']);
            $name = end($ar);
            static::$schematic['columns'][$name]['comment'] = $statement;
        }
        
        return new static;
    }
    
    /**
     * Adds the @foreign annotation
     * @param type $table
     * @param type $foreignkey
     * @param type $ondelete Options are RESTRICT | CASCADE | SET NULL | NO ACTION | SET DEFAULT
     * @param type $onupdate Options are RESTRICT | CASCADE | SET NULL | NO ACTION | SET DEFAULT
     * @return new static
     */
    public static function foreign($table, $foreignkey, $ondelete = null, $onupdate = null){
        
        //fk attributes
        $attrs = [
            'table' => $table,
            'column' => $foreignkey
        ];
        
        //add cascading actions
        //ondelete
        if(!is_null($ondelete)){
            $attrs['ondelete'] = $ondelete;
        }
        
        //onupdate
        if(!is_null($onupdate)){
            $attrs['onupdate'] = $onupdate;
        }
        
        $arcols = array_keys(static::$schematic['columns']);
        $name = end($arcols);
        static::$schematic['columns'][$name]['foreign'] = $attrs;
        
        return new static;
    }
    
    /**
     * Specify the attributes of the columns as form input
     * @param array $attributes - Will be organised in the order below by position as indicated below
     *              @group text $label
     *              @group text $type Options are hidden, textfield, textarea, select, password, country, date
     *              @group text $default
     *              @group array $options or validation - ONLY if input type is select - should be array
     *              @group array $validation
     * 
     */
    public static function input($attributes){
        
        //get the column name
        $ar = array_keys(static::$schematic['columns']);
        $name = end($ar);
        
        //analyse arguments
        $args = func_get_args();
        
        if(func_num_args() < 1){
            App::warning('Please specify more input attributes');
            exit;
        }
        
        //hiddem field
        if(func_num_args() == 2){
            
            @list($label, $type) = $args;
            static::$schematic['columns'][$name]['input'] = [
                'label' => $label,
                'type' => $type
            ];
        }
        //almost everything else
        elseif(array_key_exists(1,$args) && strtolower($args[1]) != 'select'){
            
            @list($label, $type, $default, $validate, $attributes) = $args;
            static::$schematic['columns'][$name]['input'] = [
                'label' => $label,
                'type' => $type,
                'default' => $default,
                'validate' => $validate,
                'attributes' => $attributes
            ];
        }
        //select input
        elseif(array_key_exists(1,$args) && strtolower($args[1]) == 'select'){
            
            @list($label, $type, $default, $options, $validate, $attributes) = $args;
            static::$schematic['columns'][$name]['input'] = [
                'label' => $label,
                'type' => $type,
                'default' => $default,
                'options' => $options,
                'validate' => $validate,
                'attributes' => $attributes
            ];
        }
        
        return new static;
    }
    
    /**
     * Builds the schema class
     * @param type $update
     */
    public static function write($update){
        
        if($update){
            return static::writeAndUpdateSchema();
        }
        else{
            return static::writeColumns();
        }
    }
    
    /**
     * Builds the schema class columns
     */
    protected static function writeColumns(){
        
        $schema = static::$schematic['schema'];
        $columns = static::$schematic['columns'];
        
        //get class file
        $split = explode('\\',str_replace('Jenga\MyProject\\','',$schema));
        $element = $split[0];
        $schemaclass = end($split);
        
        //rebuild project
        Project::build();
        
        $fullelm = Project::elements()[strtolower($element)];
        if(array_key_exists($schemaclass, $fullelm['schema'])){
            
            $path = ABSOLUTE_PROJECT_PATH .DS. str_replace('/', DS, $fullelm['schema'][$schemaclass]['path']);
            $annotations = static::createAnnotations($columns);
            
            //delete schema if present
            if(static::$_dropIfExists){
                static::forceRebuildSchemaFile($path);
            }
            
            //get schema file
            if(is_writeable($path)){
                
                //get file contents
                $file = File::get($path);
                
                if(static::$_rebuildFromPrevious === FALSE){
                    
                    //replace placeholders
                    $data = str_replace(static::$_placeholder, static::$_autogen_start."\n".$annotations."\n\t".static::$_autogen_end, $file);
                }
                elseif(static::$_rebuildFromPrevious){
                    
                    //get previous columns
                    $columns = Help::getEmbeddedText(static::$_autogen_start, static::$_autogen_end, $file);
                    $annotations = "\n".$annotations."\t";
                    
                    $data = str_replace($columns, $annotations, $file);
                }
                
                //save file
                $response = File::put($path, $data);
                return $response;
            }
            else{
                return 'FILE_NOT_WRITABLE';
            }
        }
        else{
            return 'SCHEMA_NOT_FOUND_IN_'.$element;
        }
    }
    
    /**
     * This function will create the columns within the schema and run the Update command to 
     * create/update the respective table
     * @return string a UPDATE_SCHEMA flag
     */
    public static function writeAndUpdateSchema(){
        
        //create the schema columns
        $status = static::writeColumns();
        
        //return update schema flag
        if($status === TRUE){
            return 'UPDATE_SCHEMA';
        }
        else{
            return $status;
        }
    }
    
    /**
     * Creates the respective annotations for the columns
     * @param type $columns
     */
    protected static function createAnnotations($columns){
        
        $annotate_keywords = require DATABASE .DS. 'systems' .DS. 'pdo' .DS. 'config' .DS. 'annotations.php';
        
        //add @input annotaions separately from db annotations
        array_push($annotate_keywords['columns'], 'input');
        
        $annotationstr = '';
        foreach ($columns as $name => $column) {
            
            $annotationstr .= "\t/**\n";
            foreach($column as $attribute => $value){
                
                switch ($attribute) {
                    case 'attributes':
                        $annotationstr .= "\t* @var ".json_encode($value)."\n";
                        break;
                }
                
                //loop through the rest
                if(in_array($attribute, $annotate_keywords['columns'])){
                    
                    switch ($attribute){
                        
                        //@primary
                        case "primary":
                            $annotationstr .= "\t* @primary true \n";
                            break;
                        
                        //@foreign
                        case "foreign":
                            
                            //add fk column
                            $fk = [];
                            $fk[$value['table']]['column'] = $value['column'];
                            
                            //ondelete
                            if(array_key_exists('ondelete', $value)){
                                $fk[$value['table']]['ondelete'] = $value['ondelete'];
                            }
                            
                            //onupdate
                            if(array_key_exists('onupdate', $value)){
                                $fk[$value['table']]['onupdate'] = $value['onupdate'];
                            }
                            
                            $annotationstr .= "\t* @foreign ".json_encode($fk)." \n";
                            unset($fk); //clear to prevent combining of many fk columns
                            break;
                            
                        //@unique
                        case "unique":
                            $annotationstr .= "\t* @unique true \n";
                            break;
                        
                        //@comment
                        case "comment":
                            $annotationstr .= "\t* @comment \"".$value."\" \n";
                            break;
                        
                        //@input
                        case "input":
                            $annotationstr .= "\t* @input ". json_encode($value)."\n";
                            break;
                    }
                }
            }
            
            $annotationstr .= "\t**/\n";
            $annotationstr .= "\tpublic $".$name.";\n\n";
        }
        
        return $annotationstr;
    }
    
    /**
     * Destroys and rebuilds the previous schema file
     * @param type $filename
     */
    protected static function forceRebuildSchemaFile($filename){
        
        //get the schema variables
        $schema_vars = get_class_vars(static::$schematic['schema']);
        
        //delete the previous schema file
        File::delete($filename);
        
        $schema = File::get(static::$_mouldpath .DS. 'schema.mld');        
        $schema_bits = explode('\\',static::$schematic['schema']);
        
        //get the schema class and namespace
        $class = array_pop($schema_bits);
        $namespace = join('\\',$schema_bits);
        
        //replace the schema namespace, class and table
        $schemastr = str_replace('{{{schm_namespace}}}', $namespace, $schema);
        $schemadata = str_replace('{{{schm_classname}}}', $class, $schemastr);        
        $schematext = str_replace ('{{{schm_table}}}', $schema_vars['table'], $schemadata);
        
        //save the new schema file
        return File::put($filename, $schematext);
    }
    
    protected function rebuildSchemaFile($filename) {
        
    }
}
