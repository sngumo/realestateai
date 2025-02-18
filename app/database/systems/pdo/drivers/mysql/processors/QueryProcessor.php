<?php
namespace Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Processors;

use Jenga\App\Database\Build\Query;

/**
 * This takes the query object and return a full PDO query with the rendered statements and arguments
 *
 * @author stanley
 */
class QueryProcessor {
    
    public $query = null;
    public $mysql_query = null;
    
    protected $stmt = null;
    protected $args = null;
    
    private $_cmd = null;
    
    /**
     * Add FROM clause
     * @var type 
     */
    private $_add_from = false;
    
    /**
     * Arguments reference list
     * @var type 
     */
    private $_args_ref = [];
    
    /**
     * The standard mysql query commands
     * @var type 
     */
    private $_commands = ['SELECT','SELECTRAW','INSERT','UPDATE','DELETE','CALL','REPLACE','DO'];
    
    /**
     * The keywords for grouping 
     * @var type 
     */
    private $_union_keywords = ['GROUP BY','HAVING','ORDER BY'];
    
    /**
     * This array holds the various operators used in Where mysql statements
     * 
     * @var array
     */
    private $_operators = array('BETWEEN','NOT BETWEEN','LIKE','NOT LIKE','IN','NOT IN','IS NOT','IS NOT NULL','IS NULL',
                            '<','<=','=','!=',':=','^','|','<=>','->','>=','>');
    
    /**
     * @param Query $query the query object
     */
    public function __construct(Query $query) {        
        $this->query = $query;
    }
    
    /**
     * Generates full MySql query from query object
     * @return type
     */
    public function translate(){   
        
        //get prepared statement
        $statement = $this->_generateMySqlStatement($this->query->stmt);
        
        //full mysql_query
        if(!empty($statement)){
            
            $mysql_query['stmt'] = $statement;
            $mysql_query['args'] = $this->_getArgs();

            return $mysql_query;
        }
        
        return NULL;
    }
    
    /**
     * Generates MySql prepared statement
     * @param type $stmt
     */
    private function _generateMySqlStatement($stmt) {
        
        $keywords = array_keys($stmt);
        
        //start on commmand
        $statement = '';
        $found = FALSE;
        
        //search through commands
        if(in_array(strtoupper($keywords[0]), $this->_commands)){
            
            $found = true;
            $cmd = $keywords[0];
        }
        else{
            
            //reverse array
            $revkeys = array_reverse($keywords);
            
            if(in_array(strtoupper($revkeys[0]), $this->_commands)){
                
                $found = true;
                $cmd = $revkeys[0];
            }
        }
        
        if($found){
            
            //switch commands
            switch ($cmd) {
                
                case 'select':
                    //add flags
                    $this->_cmd = 'select';
                    $this->_add_from = true;
                    
                    $statement  .= $this->_buildSelect($stmt['select']).' ';
                    break;
                
                case 'selectRaw':
                    //add flags
                    $this->_cmd = 'select';
                    $this->_add_from = true;
                    
                    $statement  .= $this->_buildSelect($stmt['selectRaw']).' ';
                    break;
                
                case 'insert':
                    
                    //add flags
                    $this->_cmd = 'insert';
                    
                    $statement .= $this->_buildInsert($stmt['insert']);
                    break;
                
                case 'update':
                    //add flags
                    $this->_cmd = 'update';
                    
                    $statement .= $this->_buildUpdate($stmt['update'], $stmt);
                    $keywords = array_keys($stmt);                    
                    break;
                
                case 'delete':
                    //add flags
                    $this->_add_from = true;
                    $this->_cmd = 'delete';
                    
                    $statement .= $this->_buildDelete();
                    break;
            }
        }
        else {
            
            //if no command is set assume its a select all
            $statement .= 'SELECT * ';
            
            //add flags
            $this->_cmd = 'select';
            $this->_add_from = true;
        }
        
        //add FROM clause
        if($this->_add_from){
            
            if(in_array('from', $keywords))
                $statement .= 'FROM '.$stmt['from'][0].' ';
            else
                $statement .= 'FROM '.$this->query->table.' ';
        }
        
        //add join
        if(in_array('join', $keywords)){
            $statement .= $this->_buildJoin($stmt['join']);
        }
        
        //add where condition
        if(in_array('where', $keywords)){
            $statement .= $this->_buildWhere($stmt['where']);
        }
        
        //add groupby clause
        if(in_array('groupBy', $keywords)){
            $statement .= $this->_buildGroupBy($stmt['groupBy']);
        }
        
        //add orderby clause
        if(in_array('orderBy', $keywords)){
            
            @list($ordercol, $direction) = $stmt['orderBy'][0];
            $statement .= $this->_buildOrderBy($ordercol, $direction);
        }
        
        //add having clause
        if(in_array('having', $keywords)){
            
            @list($condition) = $stmt['having'][0];
            $statement .= $this->_buildHaving($condition);
        }
        
        //add limit clause
        if(in_array('limit', $keywords)){
            $statement .= $this->_buildLimit($stmt['limit']);
        }
        
        return $statement;
    }
    
    /**
     * Build SELECT query section
     * @param type $selects
     */
    private function _buildSelect($selects){
        
        $select_expr = 'SELECT ';        
        foreach ($selects as $select_values) {
            
            //iterate through all the select values
            foreach($select_values as $value){
                
                $this->_parseSelectValues($value);
                $select_expr .= $value.', ';
            }
        }
        
        return rtrim($select_expr,', ');
    }
    
    /**
     * Countercheck the entered select values
     * @param type $value
     */
    private function _parseSelectValues(&$value){
        
        //check for commas
        $cols = '';
        if(strstr($value, ',') !== FALSE){            
            $vals = explode(',', $value);
            
            foreach($vals as $val){
                $cols .= $this->_filterValues($val).', ';
            }
            
            $cols = rtrim($cols, ', ');
        }
        else{
            $cols = $this->_filterValues($value);
        }
        
        $value = $cols;
    }
    
    private function _filterValues($vals){
        
        //case sensitive comparison
        if(strstr($vals, ' as ') !== FALSE)
            $split = explode(' as ', $vals);
        else
            $split = explode(' AS ', $vals);
        
        //check for dot in the first section
        if(strstr($split[0], '.') !== FALSE){
            $cols = explode('.', $split[0]);            
            $col = $this->_verifyTablePrefix(trim($cols[0]));
            
            $split[0] = $col.'.'.$cols[1];
        }
        
        return join(' as ', $split);
    }
    
    /**
     * Build the WHERE condition
     * @param type $wheres
     */
    private function _buildWhere($wheres) {
        
        $where_str = $where_string = '';
        
        $count = 0;
        foreach($wheres as $condition){
            
            //map variables
            $where_str = '';
            @list($prop, $operator, $value, $concat) = $condition;
                     
            //reverse operators and value
            if(is_null($value)){                
                $value = $operator;
                $operator = '=';
            }
            
            //switch between operators
            if($count > 0 && $count <= (count($wheres)-1)){
                $where_str .= ' '.$concat. ' ';
            }    
                
            //confirm operator and continue if operator is present
            if(!in_array(strtoupper($operator), $this->_operators)){
                continue;
            }
            
            //assign the where condition
            $where_string .= $this->_generateWhereString($where_str,$prop, $operator, $value);   
            $count++;
        }
        
        $where_condition = ' WHERE '.$where_string.' ';
        return $where_condition;
    }
    
    /**
     * Generates the WHERE condition
     * 
     * @param type $where_str
     * @param type $prop
     * @param type $operator
     * @param type $value
     * 
     * @return string
     */
    private function _generateWhereString($where_str,$prop, $operator, $value){
        
        //check if table is defined in prop
        if(strstr($prop, '.') !== FALSE){
            
            $propsplit = explode('.', $prop);
            
            $prop = $this->_verifyTablePrefix($propsplit[0]).'.'.$propsplit[1];
            $prop2 = $propsplit[1];
        }
        else {
            $prop2 = $prop;
        }
        
        //switch between operators
        switch (strtoupper($operator)) {
                
            case '=':        
                $where_str .= $prop.' = ?';
                $this->_addArgReferenceList(':'.$prop2, $value);
                break;

            case 'IS NULL':
                $where_str .= $prop.' IS NULL';
                break;

            case 'IS NOT NULL':
                $where_str .= $prop.' IS NOT NULL';
                break;

            case 'BETWEEN':
                $where_str .= $prop.' BETWEEN ? AND ?';
                $this->_addArgReferenceList(':'.$prop2.'_start', $value[0]);
                $this->_addArgReferenceList(':'.$prop2.'_end', $value[1]);
                break;

            case 'NOT BETWEEN':
                $where_str .= $prop.' NOT BETWEEN ? AND ?';
                $this->_addArgReferenceList(':'.$prop2.'_start', $value[0]);
                $this->_addArgReferenceList(':'.$prop2.'_end', $value[1]);
                break;
            
            case 'IN':
                //loop through values
                $count = 0;
                foreach ($value as $item) {
                    
                    $this->_addArgReferenceList($count, $item);
                    $count++;
                }
                
                $valstr = str_repeat('?,', count($value));
                $where_str .= $prop.' IN ('. rtrim($valstr,',').')';
                break;

            default:                                        
                $where_str .= $prop.' '.$operator.' ?';
                $this->_addArgReferenceList(':'.$prop2, $value);
                break;
        }
        
        return $where_str;
    }
    
    /**
     * Build the query limits
     * @param type $limits
     */
    private function _buildLimit($limits) {
        
        //assign vars
        if(count($limits[0]) > 1){
            @list($offset, $limit) = $limits[0];
        }
        else{
            @list($limit) = $limits[0];
            $offset = null;
        }
        
        //query limit string
        $limit_str = ' LIMIT ? '.(!is_null($offset) ? 'OFFSET ?' : '');
        
        //pdo bind arguments
        $this->_addArgReferenceList(':limit', (int) $limit);
        
        if(!is_null($offset) ){
            $this->_addArgReferenceList(':offset', (int) $offset);
        }
        
        return $limit_str;
    }
    
    /**
     * Adds the group by column
     * @param type $groupcol
     * @return string
     */
    private function _buildGroupBy($groupcol){      
        
        @list($col) = $groupcol[0];
        $group_str = ' GROUP BY '.$col;
        
        return $group_str;
    }
    
    /**
     * Adds order by 
     * @param type $ordercol
     * @param type $direction
     * @return string
     */
    private function _buildOrderBy($ordercol, $direction){  
        
        //check for the dot notation
        if(strpos($ordercol, '.') !== FALSE){
            
            //check for prefix
            if(strpos($ordercol, $this->query->prefix) !== 0){
                $ordercol = $this->query->prefix.$ordercol;
            }
        }
        
        $order_str = ' ORDER BY '.$ordercol.' '.$direction;
        return $order_str;
    }
    
    /**
     * Add Having condition
     * @param type $condition
     */
    private function _buildHaving($condition) {
        $having_str = ' HAVING '.$condition;
        return $having_str;
    }
    
    /**
     * Add a join
     * @param type $joins
     */
    private function _buildJoin($joins) {
        
        $join_str = '';
        foreach ($joins as $join) {
            
            @list($table, $condition, $type) = $join;
            $join_str .= ' '.$type.' JOIN '.$table.' ON '. $condition;
        }
        
        return $join_str;
    }
    
    /**
     * Builds insert statement
     * @param type $inserts
     */
    private function _buildInsert($inserts){
        
        foreach($inserts as $insert){
            
            //add insert table
            $insertstr = 'INSERT INTO '.$this->query->table;  
            
            $timestamp = $insert[1];
            $data = $insert[0][$timestamp];
            
            $cols = array_keys($data);
            
            //check for primary key
            if(in_array($this->query->primarykey, $cols)){
                
                $key = array_search($this->query->primarykey, $cols);
                unset($cols[$key], $data[$this->query->primarykey]);
            }
            
            //start string         
            $colstr = str_repeat('?,', count($cols));
            $insertstr .= ' ('.join(', ', $cols).') VALUES ('. rtrim($colstr, ',').')';
            
            //bind values
            foreach($data as $param => $value){
                $this->_addArgReferenceList(':'.$param, $value);
            }
        }
        
        return $insertstr;
    }
    
    /**
     * Builds update statement
     * @param type $updates
     */
    private function _buildUpdate($updates, &$stmt) {
        
        $updatestr = 'UPDATE '.$this->query->table.' ';
        
        foreach ($updates as $update) {
            
            @list($data, $createkey, $updatekey) = $update;
            $updata = $data[$updatekey];
            
            $updatestr .= 'SET ';
            
            //if updata is empty
            if(count($updata) === 0) return NULL;
            
            foreach(array_keys($updata) as $key){
                $updatestr .= $key.'= ?, ';
                $this->_addArgReferenceList(':'.$key);
            }
            
            $updatestr = rtrim($updatestr, ', ');
            
            //where clause
            if(!array_key_exists('where', $stmt)){
                
                $id = $data[$createkey][$this->query->primarykey];
                $stmt['where'][] = [$this->query->primarykey,'=',$id,'AND'];
            }
            
            //bind values
            foreach($data[$updatekey] as $param => $value){
                $this->_addArgReferenceList(':'.$param, $value);
            }
        }
        
        return $updatestr;
    }
    
    /**
     * Build the delete statement
     * @param type $deletes
     */
    private function _buildDelete() {
        
        $deletestr = 'DELETE ';
        return $deletestr;
    }
    
    /**
     * Checks if the table name has the prefix
     * @param type $tablename
     * @return boolean
     */
    private function _verifyTablePrefix($tablename){
        
        $prefix = $this->query->prefix;
        
        if(strpos($tablename, $prefix) === 0){
            return $tablename;
        }
        
        return $prefix.$tablename;
    }
    
    /**
     * Populates the arguments reference list
     * @param type $argname
     */
    private function _addArgReferenceList($argname, $argval = null){
        
        //check for command
        $cmd = $this->_cmd;
        
        if(array_key_exists($cmd, $this->_args_ref)){
            
            $cmdlist = $this->_args_ref[$cmd];
            
            //check for param
            if(array_key_exists($argname, $cmdlist)){
                
                if(is_null($this->_args_ref[$cmd][$argname])){
                    
                    //add new argument name with randon number
                    return $this->_args_ref[$cmd][$argname] = $argval;
                }
                
                //add new argument name with randon number
                return $this->_args_ref[$cmd][$argname.'_'.rand()] = $argval;
            } 
        }
        
        return $this->_args_ref[$cmd][$argname] = $argval;
    }
    
    /**
     * Return bound arguments in query
     */
    private function _getArgs() {
        
        if(count($this->_args_ref) >= 1){
            
            $args = array_values($this->_args_ref[$this->_cmd]);
            return $args;
        }
    }
}
