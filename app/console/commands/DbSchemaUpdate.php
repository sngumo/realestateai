<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Helpers\Help;
use Jenga\App\Project\Core\Project;
use Jenga\App\Database\Systems\Pdo\Schema\Processor;
use Jenga\App\Database\Systems\Pdo\Connections\DatabaseConnector;

use ReflectionClass;

/**
 * The DbSchemaUpdate command runs the embedded schema within each element and recreates the tables
 *
 * @author stanley
 * @todo Refine the db:schema:update to track changes to cache more precisely
 */
class DbSchemaUpdate extends Command {
    
    /**
     * @var Processor
     */
    protected $processor;
    
    public $table;
    public $cache_path = DATABASE .DS. 'systems' .DS.'pdo' .DS. 'schema' .DS. 'cache';
    public $annotations;
    public $annotationslist;
    
    protected $io;
    protected $progressbar;
    protected $force = false;

    protected function configure() {
        
        $this->setName('db:schema:update')
                ->setDescription('Runs the embedded schemas within each element and recreates the tables')
                ->addArgument('element_schema', 
                        InputArgument::OPTIONAL, 
                        'Specify the element schema using format <element>/<schema class> ')
                ->addOption('force', null, 
                        InputOption::VALUE_NONE, 
                        "This will disregard the cache and recreate schema. "
                        . "(Note: the previous table data will be destroyed)");
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $this->io = new SymfonyStyle($input, $output);
        $this->io->newLine();
        
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Updating the database schema may interfere with the existing data. Do you want to continue? [ y / n ] ', false);
        
        if (!$helper->ask($input, $output, $question)) {
            return;
        }
        
        //check argument
        $arg = $input->getArguments();
        
        if(!is_null($arg['element_schema'])){
            $elmschema = $input->getArgument('element_schema');
            
            if(strpos($elmschema, '/') === FALSE){
                $elm['element'] = strtolower($elmschema);
                $elm['schema'] = $elmschema.'Schema';
            }
            else{
                $split = explode('/', $elmschema);
                
                $elm['element'] = strtolower($split[0]);
                $elm['schema'] = ucfirst($split[1]);
            }
        }
        else{
            $elm = 'all';
        }
        
        $conn = App::get(DatabaseConnector::class);
        $connect = $conn::connect();
        
        //add annotations list
        $this->annotationslist = require DATABASE .DS. 'systems' .DS. 'pdo' .DS. 'config' .DS. 'annotations.php';
        
        //update the database schema
        $this->io->newLine();
        
        //check force update
        $this->force = $input->getOption('force');
        $this->updateSchema($elm, $connect, $this->io);
    }
    
    /**
     * Updates the database schema
     * @param type $elms
     * @return Jenga\App\Database\Systems\Pdo\Schema\Processor
     */
    public function updateSchema($elms, $connect, $io) {
        
        if($elms == 'all'){
            $elements = Project::elements();
        }
        else {
            $elements[] = Project::elements()[$elms['element']];
        }
        
        foreach ($elements as $element) {
            
            $name = $element['name'];
            $schemas = null;
            
            if($elms == 'all'){
                
                if(array_key_exists('schema', $element))
                    $schemas = array_keys($element['schema']);
            }
            else {
                
                if(array_key_exists('schema', $elms))
                    $schemas[] = $elms['schema'];
            }
            
            //continue if schema is still null
            if(is_null($schemas)){
                continue;
            }
            
            //loop through the schemas
            foreach ($schemas as $schema) {
                
                $schemaclass = 'Jenga\MyProject\\'.ucfirst($name).'\Schema\\'. ucfirst($schema);
                
                $reflect = new ReflectionClass($schemaclass);
                $schemacols = $reflect->getProperties();
                
                //call the schema processor
                $this->processor = App::make(Processor::class,['connection' => $connect, 'schema' => $reflect, 'io' => $io]);
                $this->annotations = $this->processor->parseColumns($schemacols);
                
                //add table
                $this->table = array_keys($this->annotations['table'])[0];
                $this->processor->table($this->table);    
                
                //disable autocommit
                $this->processor->getHandle()->rawQuery("SET autocommit = 0;");
                
                //check cache and run alterations
                $this->processor->start();
                
                if($this->force == FALSE){
                    
                    try{

                        $newtable = $updated = FALSE;
                        $updated = $this->updateTableReferringToCache($this->table, $this->annotations);
                        
                        //start building
                        if($updated === 'NO_CACHE_FOUND'){                   
                            $newtable = $this->buildNewTableFromSchema($name, $schema, $io);
                        }

                        //commit everything
                        $this->processor->commit();
                    }
                    catch(\Exception $ex){

                        $this->io->error($ex->getMessage());
                        $this->io->comment($this->processor->getSQLQuery());

                        $this->processor->cancel(); //rollback all changes
                    }

                    if($updated === FALSE && $newtable === FALSE){

                        $this->io->note("No changes made to ".ucfirst($name).'/'.ucfirst($schema));
                        $this->io->newLine();
                    }
                }
                elseif ($this->force == true) {                    
                    $newtable = $this->buildNewTableFromSchema($name, $schema, $io);
                }
            }
        }
    }
    
    /**
     * Builds new table schema
     * @param type $name
     * @param type $schema
     * @param type $io
     * @return boolean
     */
    public function buildNewTableFromSchema($name, $schema, $io){
        
        $builder = $this->processor->createBuilder($this->annotations);
        $result = $builder->build($this->force);
        
        if(is_null($result->errorInfo()[2])){

            $io->success(ucfirst($name).'/'.ucfirst($schema).' built successfully');
            $this->processor->saveCache(); //cache created table

            return TRUE;
        }
        else{
            
            $io->error('Error building '.ucfirst($name).'/'.ucfirst($schema));
            $io->error($result->errorInfo()[2]);
        }
        
        return FALSE;
    }
    
    /**
     * Updates table referring to saved cache
     * @param type $table
     */
    public function updateTableReferringToCache($table, $annotations){
        
        $cache_dir = File::scandir($this->cache_path);
        $cache_tbl_path = $this->cache_path .DS. strtolower($table) .'.cache';
        
        //check if cache exists
        if(in_array($cache_tbl_path, $cache_dir)){
            
            //get the file pointer
            $pointer = array_search($cache_tbl_path, $cache_dir);
            
            //get the cache file
            $cache_file = File::get($cache_dir[$pointer]);
            $cached_tbl = unserialize($cache_file);
            
            //check for alterations and addition
            $altered = FALSE;
            $diff = $this->diffColumns($annotations['columns'], $cached_tbl['columns']);
            
            if(count($diff) > 0){
                
                //if diff has been found start altering table
                $this->alterTableColumns($diff, $cached_tbl['columns']);
                $altered = TRUE;
            }
            
            //check for removed columns
            if($altered){
                
                //get the cache file
                $cache_file = File::get($cache_dir[$pointer]);
                $cached_tbl = unserialize($cache_file);
            }
            
            $rdiff = $this->diffColumns($annotations['columns'], $cached_tbl['columns'], TRUE);
            
            if(count($rdiff) > 0){
                
                //remove the sent columns
                foreach ($rdiff as $col) {
                    $res = $this->processor->removeColumn($col);
                    
                    if($res === true){
                        
                        $this->io->note($col . ' column dropped');
                        $this->io->success (ucfirst($this->table).' table schema rebuilt successfully');
                    }
                    else{
                        $this->io->error('['.$col . '] column not present in '.ucfirst($this->table).'Schema table');
                    }
                }
                
                $this->processor->saveCache(); //cache modified table
                $altered = TRUE;
            }
            
            //check for altered column attributes
            if($altered){
                
                //get the cache file
                $cache_file = File::get($cache_dir[$pointer]);
                $cached_tbl = unserialize($cache_file);
            }
            
            //filter between the current and cached columns
            $attrdiff = $this->diffColumns($annotations['columns'], $cached_tbl['columns'], FALSE, TRUE);
            
            if(count($attrdiff) > 0){
                
                $status = $this->performActionsFromAttributeDiff($attrdiff);
                
                if($status){
                    $this->processor->saveCache(); //cache modified table
                    $altered = TRUE;
                }
                else{
                    $altered = FALSE;
                }
            }
            
            //if FALSE it indicates no changes to table
            return $altered;
        }
        
        return 'NO_CACHE_FOUND';
    }
    
    /**
     * Modify table according to sent diff
     * @param type $diffs
     */
    public function performActionsFromAttributeDiff($diffs) {
        
        foreach ($diffs as $column => $attrdata){
            
            //get actions
            $actions = $attrdata['actions']; 
            $acon = $this->processor->activeconnection;
            
            if(array_key_exists('foreign', $attrdata['attributes'])){
                
                $ar = array_keys($attrdata['attributes']['foreign']);
                $foreign = end($ar);
                $json = json_encode($attrdata['attributes']['foreign']);

                //create foreign key
                $key = 'fk_'.str_replace($acon['prefix'], '', $this->table).'_'.$foreign;
            }
            
            $errors = [];
            foreach ($actions as $cmd) {
                
                switch ($cmd) {
                    
                    case "add_primary_key":
                        $status = $this->processor->addPrimaryKey($column);
                        
                        if($status){
                            $this->io->success('Primary key '.$column.' in '.$this->table.' added');
                        }
                        else{
                            $errors[] = $status;
                            $errors[] = $this->processor->getSQLQuery();
                            
                            $this->io->error(print_r($errors, TRUE));
                        }
                        break;
                    
                    case "drop_primary_key":
                        $status = $this->processor->deletePrimaryKey($column);
                        
                        if($status){
                            $this->io->success('Primary key '.$column.' in '.$this->table.' dropped');
                        }
                        else{
                            $errors[] = $status;
                            $errors[] = $this->processor->getSQLQuery();
                            
                            $this->io->error(print_r($errors, TRUE));
                        }
                        break;
                    
                    case "modify_column":                       
                        $status = $this->processor->modifyColumn($column, $attrdata['attributes']['var'][0]);
                        
                        if($status){
                            $this->io->success('Column '.$column.' in '.$this->table.' has been modified');
                        }
                        else{
                            $errors[] = $status;
                            $errors[] = $this->processor->getSQLQuery();
                            
                            $this->io->error(print_r($errors, TRUE));
                        }
                        break;
                    
                    case "drop_and_create_foreign_key":
                        
                        //drop the foreign key
                        $drop = $this->processor->dropForeignKey($acon['prefix'].$this->table, $key);
                        
                        //create a new one
                        if($drop){
                            $status = $this->processor->addForeignKey($acon['prefix'].$this->table, $foreign, $json);
                            
                            if($status){
                                $this->io->success('Foreign key '.$key.' in '.$this->table.' has been modified');
                            }
                            else{
                                $errors[] = $status;
                                $errors[] = $this->processor->getSQLQuery();
                                
                                $this->io->error(print_r($errors, TRUE));
                            }
                        }
                        else{
                            $errors[] = $drop;
                            $errors[] = $this->processor->getSQLQuery();
                            
                            $this->io->error($drop);
                        }
                        break;
                    
                    case "drop_foreign_key":
                        
                        //drop the foreign key
                        $status = $this->processor->dropForeignKey($acon['prefix'].$this->table, $key);
                        
                        if($status){
                            $this->io->success('Foreign key '.$key.' in '.$this->table.' has been dropped');
                        }
                        else{
                            $errors[] = $status;
                            $errors[] = $this->processor->getSQLQuery();
                            
                            $this->io->error(print_r($errors, TRUE));
                        }
                        break;
                    
                    case "add_foreign_key":                        
                        $status = $this->processor->addForeignKey($acon['prefix'].$this->table, $foreign, $json);
                        
                        if($status){
                            $this->io->success('Foreign key '.$key.' in '.$this->table.' has been added');
                        }
                        else{
                            $errors[] = $status;
                            $errors[] = $this->processor->getSQLQuery();
                            
                            $this->io->error(print_r($errors, TRUE));
                        }
                        break;
                    
                    case "add_unique":
                        $status = $this->processor->addUniqueConstraint($column);
                        
                        if($status){
                            $this->io->success('Unique column '.$column.' in '.$this->table.' has been added');
                        }
                        else{
                            $errors[] = $status;
                            $errors[] = $this->processor->getSQLQuery();
                            
                            $this->io->error(print_r($errors, TRUE));
                        }
                        break;
                    
                    case "delete_unique":
                        $status = $this->processor->deleteUniqueConstraint($column);
                        
                        if($status){
                            $this->io->success('Unique column '.$column.' in '.$this->table.' has been dropped');
                        }
                        else{
                            $errors[] = $status;
                            $errors[] = $this->processor->getSQLQuery();
                            
                            $this->io->error(print_r($errors, TRUE));
                        }
                        break;
                }
            }
            
            if(count($errors) > 0){
                return FALSE;
            }
            
            return TRUE;
        }
    }
    
    /**
     * Filter between the current and cached columns
     * @param type $current_cols
     * @param type $cached_cols
     * @param type $reverse_arrays //if TRUE columns to be removed are sent
     * @param type $chk_attrs //if TRUE column attributes are checked
     * @return type
     */
    public function diffColumns($current_cols, $cached_cols, $reverse_arrays = false, $chk_attrs = FALSE){
        
        $curcols = array_keys($current_cols);
        $cachcols = array_keys($cached_cols);
        
        if($chk_attrs){            
            return $this->chkAttributes($current_cols, $cached_cols);
        }
        
        //check columns
        if($reverse_arrays === FALSE){
            $diff = array_diff($curcols, $cachcols);
        }
        else{
            $diff = array_diff($cachcols, $curcols);
        }
        
        //check column count
        if(count($curcols) > count($cachcols)){
            $diff['action'] = 'ADD_COLUMN';
        }
        elseif(count($cachcols) > count($curcols) && count(array_intersect($diff, $cachcols)) > 0){
            $diff['action'] = 'REMOVE_COLUMN';
        }
        else{
            $diff = [];
        }
        
        return $diff;
    }
    
    /**
     * Compare each columns attributes
     * @param type $current
     * @param type $cached
     */
    public function chkAttributes($current, $cached){
        
        $colattributes = $this->annotationslist['columns'];
        
        $difflist = [];
        foreach($colattributes as $attr){
            
            //check if attribute exists
            foreach($cached as $column => $cachedcolattrs){
                
                //check if its an attribute modification, addition or removal
                $operation = '';
                if(in_array($attr, array_keys($cachedcolattrs)) 
                        && in_array($attr, array_keys($current[$column]))){
                    
                    //evaluate difference
                    $diff = $this->_evaluateDifference($cachedcolattrs[$attr], $current[$column][$attr]);
                    
                    if($diff)
                        $operation = 'modification';
                    
                }
                elseif(!in_array($attr, array_keys($cachedcolattrs)) 
                        && in_array($attr, array_keys($current[$column]))){
                    $operation = 'addition';
                }
                elseif(in_array($attr, array_keys($cachedcolattrs)) 
                        && !in_array($attr, array_keys($current[$column]))){
                    $operation = 'subtraction';
                }
                else{
                    $operation = 'none';
                }
                
                //process attribute
                switch ($attr) {
                    case 'var':                            
                        $list = $this->compareVars($column, $cachedcolattrs[$attr], $current);

                        if(count($list) > 0){
                            $difflist[$column]['actions'][] = 'modify_column';
                            $difflist[$column]['attributes']['var'] = $list;
                        }                            
                        break;

                    case 'primary':
                        
                        if($operation == 'addition'){
                            $difflist[$column]['actions'][] = 'add_primary_key';
                        }
                        elseif($operation == 'subtraction'){
                            $difflist[$column]['actions'][] = 'drop_primary_key';
                        }
                        break;
                        
                    case 'foreign':
                        
                        if($operation == 'addition'){
                            $difflist[$column]['actions'][] = 'add_foreign_key';
                            $difflist[$column]['attributes']['foreign'] = $current[$column]['foreign'];
                        }
                        elseif($operation == 'subtraction'){
                            $difflist[$column]['actions'][] = 'drop_foreign_key';
                            $difflist[$column]['attributes']['foreign'] = $cached[$column]['foreign'];
                        }
                        elseif($operation == 'modification'){
                            $difflist[$column]['actions'][] = 'drop_and_create_foreign_key';
                            $difflist[$column]['attributes']['foreign'] = $current[$column]['foreign'];
                        }
                        break;
                        
                    case 'unique':
                        
                        if($operation == 'addition'){
                            $difflist[$column]['actions'][] = 'add_unique';
                            $difflist[$column]['attributes']['unique'] = $current[$column]['unique'];
                        }
                        elseif($operation == 'subtraction'){
                            $difflist[$column]['actions'][] = 'drop_unique';
                            $difflist[$column]['attributes']['unique'] = $current[$column]['unique'];
                        }
                        break;
                }
            }
        }
        return $difflist;
    }
    
    private function _evaluateDifference($cached, $current) {
        
        if(is_array($cached) && is_array($current)){
                        
            //check if its associative
            if(Help::isAssoc($cached)){
                
                $cached = json_encode($cached);
                $current = json_encode($current);
                
                if(strcmp($cached, $current) !== 0){
                    return TRUE;
                }
            }
            else{
                
                $diff = array_diff($cached,$current);

                if(count($diff) > 0)
                    return TRUE;
            }
        }
        elseif(gettype($cached) != gettype($current)){
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * Compares the @var attributes in the current and cached columns
     * 
     * @param type $column
     * @param type $cached_attrs
     * @param type $current
     */
    public function compareVars($column, $cached_attrs, $current) {
                            
        $list = [];
        if(array_key_exists('var', $current[$column])){
            
            $current_attrs = $current[$column]['var'];
            
            //if both are arrays perform an array_diff
            if(is_array($current_attrs) && is_array($cached_attrs)){
                
                $diff = array_diff($current_attrs, $cached_attrs);
                
                //if change is present add to diff list
                if(count($diff) > 0){
                    $list[] = $current_attrs;
                }
            }
            elseif(gettype($current_attrs) !== gettype($cached_attrs)){
                $list[] = $current_attrs;
            }            
        }
        else{
            $list[] = $current_attrs;
        }
        
        return $list;
    }
    
    /**
     * Alters columns in table
     * @param type $diff
     */
    public function alterTableColumns($diff, $cache_cols){
        
        $action = null;
        $columns = array_keys($cache_cols);
        
        //check for embedded action
        if(array_key_exists('action', $diff)){
            $action = $diff['action'];
            unset($diff['action']);
        }
        
        foreach($diff as $pos => $col){
            
            //get key pointer
            $key = array_search($col, array_keys($this->annotations['columns']));
            
            //check for @var
            $annotecols = $this->annotations['columns'][$col];
            if(array_key_exists('var',$annotecols)){
                $vars = $annotecols['var'];
                
                //switch between array and string
                switch (gettype($vars)) {

                    case 'string':
                        $vars = [$vars];
                        break;
                }
            }
            
            //if col is present rename or else add it
            if(array_key_exists($key, $columns)){
                
                if(is_null($action)){
                    $result = $this->processor->renameColumn($columns[$key], $col, $vars);
                    
                    if($result)
                        $this->io->note ($col.' column modified');
                }
                elseif($action == 'ADD_COLUMN'){
                    
                    //calculate new position
                    $before = array_keys($cache_cols)[($pos-1 < 0 ? 0 : $pos-1)];
                    $result = $this->processor->addColumn($col, $vars, $before);
                    
                    if($result)
                        $this->io->note ($col.' column added after '.$before);
                }               
                
            }
            else{
                $result = $this->processor->addColumn($col, $vars);
                
                if($result)
                    $this->io->note ($col.' column added');
            }
            
            if($this->processor->hasNoErrors()){
                $this->io->success(ucfirst($this->table).' table schema rebuilt successfully');

                //save cache
                $this->processor->cacheAnnotations($this->table, $this->annotations);
                $this->processor->saveCache();
            }
        }
    }
}
