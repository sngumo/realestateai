<?php
namespace Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Migrations;

class DatabaseDumper{
    
    /**
     * @var string
     */
    protected $db_host;
    /**
     * @var string
     */
    protected $db_name;
    /**
     * @var string
     */
    protected $db_user;
    /**
     * @var string
     */
    protected $db_pass;
    /**
     * @var string
     */
    protected $db_port;

    /**
     * Compress the file
     * @var bool
     */
    protected $compress = false;

    /**
     * If need to hex value mapping
     * @var bool
     */
    protected $hexValue = false;
    /**
     * @var array
     */
    protected $table_filter = [];
    /**
     * @var bool
     */
    protected $dump_structure = true;
    /**
     * @var bool
     */
    protected $dump_data = true;
    /**
     * The file name
     * @var null|string
     */
    protected $file_name = null;

    /**
     * @var array
     */
    protected $_fk_names = [];
    /**
     * @var string
     */
    private $file_ext = '.sql';
    /**
     * @var resource|null
     */
    protected $file = null;
    /**
     * @var bool
     */
    protected $isWritten = false;
    /**
     * @var bool
     */

    protected $drop_existing = true;
    /**
     * @var string|null
     */
    protected $prefix = null;
    /**
     * @var mysqli
     */
    protected $mysqli;

    /**
     * @param null $file_path
     * @return bool
     */
    protected function setFilePath($file_path = null)
    {
        if ($this->compress) {
            $this->file_ext = '.sql.gz';
        }
        $this->file_name = ($file_path ? $file_path : $this->db_name) . $this->file_ext;
        if ($this->isWritten)
            return false;
        $this->file = $this->openFile($this->file_name);
    }


    /**
     * Writes to file the $table's structure
     * @param string $table The table name
     * @return bool
     */
    private function getTableStructure($table)
    {
        // Structure Header
        $structure = "-- \n";
        $structure .= "-- Table structure for table `{$table}` \n";
        $structure .= "-- \n\n";
        
        // Dump Structure
        if ($this->drop_existing)
            $structure .= 'DROP TABLE IF EXISTS `' . $table . '`;' . "\n";
        
        $structure .= "CREATE TABLE `" . $table . "` (\n";
        $records = $this->mysqli->query('SHOW FIELDS FROM `' . $table . '`');
        if ($records->num_rows == 0)
            return false;
        while ($record = $records->fetch_assoc()) {

            $structure .= '`' . $record['Field'] . '` ' . $record['Type'];
            if (strcmp($record['Null'], 'YES') != 0)
                $structure .= ' NOT NULL';
            if (!empty($record['Default']) || strcmp($record['Null'], 'YES') == 0) {
                if ($record['Default'] == 'CURRENT_TIMESTAMP')
                    $structure .= ' DEFAULT ' . (is_null($record['Default']) ? 'NULL' : "{$record['Default']}");
                else
                    $structure .= ' DEFAULT ' . (is_null($record['Default']) ? 'NULL' : "'{$record['Default']}'");
            }
            if (!empty($record['Extra']))
                $structure .= ' ' . $record['Extra'];
            $structure .= ",\n";
        }

        $structure = preg_replace("/,\n$/", null, $structure);

        // Save all Column Indexes
        $structure .= $this->getSqlKeysTable($table);
        $structure .= "\n)";

        //Save table engine
        $records = $this->mysqli->query("SHOW TABLE STATUS LIKE '" . $table . "'");

        // echo $query; - ???
        if ($record = $records->fetch_assoc()) {
            if (!empty($record['Engine']))
                $structure .= ' ENGINE=' . $record['Engine'];
            if (!empty($record['Auto_increment'])) {
                // $structure .= ' AUTO_INCREMENT=' . $record['Auto_increment'];
            }
        }
        $structure .= ";\n\n-- --------------------------------------------------------\n\n";
        return $structure;
        // $this->saveToFile($this->file, $structure);
    }

    /**
     * Writes to file the $table's data
     * @param string $table The table name
     * @param boolean $hexValue It defines if the output is base 16 or not
     * @return bool
     */
    private function getTableData($table, $hexValue = true)
    {
        // Header
        $data = "-- \n";
        $data .= "-- Dumping data for table `$table` \n";
        $data .= "-- \n\n";

        $records = $this->mysqli->query('SHOW FIELDS FROM `' . $table . '`');
        $num_fields = $records->num_rows;
        if ($num_fields == 0)
            return false;
        // Field names
        $selectStatement = "SELECT ";
        $insertStatement = "INSERT INTO `$table` (";
        $hexField = [];
        for ($x = 0; $x < $num_fields; $x++) {
            $record = $records->fetch_assoc();
            if (($hexValue) && ($this->isTextValue($record['Type']))) {
                $selectStatement .= 'HEX(`' . $record['Field'] . '`)';
                $hexField [$x] = true;
            } else
                $selectStatement .= '`' . $record['Field'] . '`';
            $insertStatement .= '`' . $record['Field'] . '`';
            $insertStatement .= ", ";
            $selectStatement .= ", ";
        }
        $insertStatement = substr($insertStatement, 0, -2) . ') VALUES';
        $selectStatement = substr($selectStatement, 0, -2) . ' FROM `' . $table . '`';

        $records = $this->mysqli->query($selectStatement);
        $num_rows = $records->num_rows;
        $num_fields = $records->field_count;
        // Dump data
        if ($num_rows > 0) {
            $data .= $insertStatement;
            for ($i = 0; $i < $num_rows; $i++) {
                $record = $records->fetch_assoc();
                $data .= ' (';
                for ($j = 0; $j < $num_fields; $j++) {
                    $field_name = $this->getFieldName($records, $j);
                    if (isset($hexField[$j]) && $hexField[$j] && (@strlen($record[$field_name]) > 0))
                        $data .= "0x" . $record[$field_name];
                    else if (is_null($record[$field_name]))
                        $data .= "NULL";
                    else
                        $data .= "'" . str_replace('\"', '"', $this->mysqli->escape_string($record[$field_name])) . "'";
                    $data .= ',';
                }
                $data = substr($data, 0, -1) . ")";
                $data .= ($i < ($num_rows - 1)) ? ',' : ';';
                $data .= "\n";
                //if data in greater than 1MB save
                if (strlen($data) > 1048576) {
                    $this->saveToFile($this->file, $data);
                    $data = '';
                }
            }
            $data .= "\n-- --------------------------------------------------------\n\n";
            return $data;
        }
    }

    /**
     * Get the field name
     * @param $records
     * @param $j
     * @return null
     */
    private function getFieldName($records, $j)
    {
        $properties = mysqli_fetch_field_direct($records, $j);
        return is_object($properties) ? $properties->name : null;
    }

    /**
     * Writes to file all the selected database tables structure
     * @return boolean
     */
    private function getDatabaseStructure()
    {
        $records = $this->mysqli->query('SHOW TABLES');
        if ($records->num_rows == 0)
            return;
        $structure = '';
        while ($record = $records->fetch_row()) {
            if ($this->needTable($record[0])) {
                $structure .= $this->getTableStructure($record[0]);
            }
        }
        return $structure;
    }

    /**
     * If this table should be dumped
     * @param $table
     * @return bool
     */
    private function needTable($table)
    {
        if (empty($this->table_filter)) {
            return true;
        }
        if (!empty($this->prefix)) {
            $table = preg_replace('/^' . $this->prefix . '/', '', $table);
        }
        return in_array($table, $this->table_filter);
    }

    /**
     * Writes to file all the selected database tables data
     * @param boolean $hexValue It defines if the output is base-16 or not
     * @return bool
     */
    private function getDatabaseData($hexValue = true)
    {
        $data = null;
        $records = $this->mysqli->query('SHOW TABLES');
        if ($records->num_rows == 0)
            return false;
        while ($record = $records->fetch_row()) {
            if ($this->needTable($record[0])) {
                $data .= $this->getTableData($record[0], $hexValue);
            }
        }
        return $data;
    }

    /**
     * Writes to file the selected database dump
     * @return bool
     */
    public function doTheDatabaseDump(){
        
        //$this->setMySqli();
        
        $this->saveToFile($this->file, "SET FOREIGN_KEY_CHECKS = 0;\n\n");
        
        $script = null;
        if ($this->dump_structure)
            $script .= $this->getDatabaseStructure();
        if ($this->dump_data)
            $script .= $this->getDatabaseData($this->hexValue);
        
        $script .= $this->indexRules();
        
        $this->saveToFile($this->file, $script);
        $this->saveToFile($this->file, "SET FOREIGN_KEY_CHECKS = 1;\n\n");
        
        return true;
    }

    private function indexRules(){
        
        $this->getForeignKeys();
        
        if (empty($this->_fk_names)) {
            return null;
        }
        
        $sql_file = "-- ------------\n";
        $sql_file .= "-- FOREIGN KEYS\n";
        $sql_file .= "-- ------------\n";
        $sql_file .= $this->getForeignKeysRules();
        
        return $sql_file;
    }

    /**
     * Return SQL command with foreign keys as string
     *
     * Function select some columns from Information Schema and write informations about foreign keys to string.
     *
     * @return string
     */
    public function getForeignKeysRules(){
        
        $FK_to_sql_file = "";
        foreach ($this->_fk_names as $fk_name) {

            $sql = "select KEY_COLUMN_USAGE.TABLE_NAME, KEY_COLUMN_USAGE.CONSTRAINT_NAME, COLUMN_NAME,
                    REFERENCED_COLUMN_NAME, KEY_COLUMN_USAGE.REFERENCED_TABLE_NAME, UPDATE_RULE, DELETE_RULE
                    from information_schema.KEY_COLUMN_USAGE, information_schema.REFERENTIAL_CONSTRAINTS
                    where KEY_COLUMN_USAGE.CONSTRAINT_SCHEMA = '{$this->db_name}'
                    and KEY_COLUMN_USAGE.CONSTRAINT_NAME = '{$fk_name}'
                    and KEY_COLUMN_USAGE.CONSTRAINT_NAME = REFERENTIAL_CONSTRAINTS.CONSTRAINT_NAME
                    and KEY_COLUMN_USAGE.CONSTRAINT_SCHEMA = REFERENTIAL_CONSTRAINTS.CONSTRAINT_SCHEMA";

            $result = $this->mysqli->query($sql);

            while ($row = $result->fetch_assoc()) {
                if ($this->needTable($row['TABLE_NAME'])) {
                    $FK_to_sql_file .= "ALTER TABLE `" . $row['TABLE_NAME'] . "` ADD CONSTRAINT `" . $row['CONSTRAINT_NAME'] . "` FOREIGN KEY (`" . $row['COLUMN_NAME'] . "`) REFERENCES `" . $row['REFERENCED_TABLE_NAME'] . "` (`" . $row['REFERENCED_COLUMN_NAME'] . "`) ON DELETE {$row['DELETE_RULE']} ON UPDATE {$row['UPDATE_RULE']};";
                    $FK_to_sql_file .= "\r\n\r\n";
                }
            }
        }
        return $FK_to_sql_file;
    }

    /**
     * Gets Foreign Keys names to array
     *
     * Select CONSTRAINT_NAME from Information Schema
     *
     * @return void
     */
    public function getForeignKeys(){
        
        $sql = "select * from information_schema.TABLE_CONSTRAINTS
            where  CONSTRAINT_TYPE = 'foreign key' 
            and CONSTRAINT_SCHEMA ='{$this->db_name}'";
            
        $result = $this->mysqli->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            if ($this->needTable($row['TABLE_NAME'])) {
                array_push($this->_fk_names, $row['CONSTRAINT_NAME']);
            }
        }
    }

    /**
     * Get the sql keys table
     * @param $table
     * @return bool|string
     */
    private function getSqlKeysTable($table)
    {
        $primary = "";
        $unique = null;
        $index = null;
        $fulltext = null;
        $results = $this->mysqli->query("SHOW KEYS FROM `{$table}`");
        if ($results->num_rows == 0)
            return false;
        while ($row = $results->fetch_object()) {
            if (($row->Key_name == 'PRIMARY') AND ($row->Index_type == 'BTREE')) {
                if ($primary == "")
                    $primary = "  PRIMARY KEY  (`{$row->Column_name}`";
                else
                    $primary .= ", `{$row->Column_name}`";
            }
            if (($row->Key_name != 'PRIMARY') AND ($row->Non_unique == '0') AND ($row->Index_type == 'BTREE')) {
                if ((!is_array($unique)) OR ($unique[$row->Key_name] == ""))
                    $unique[$row->Key_name] = "  UNIQUE KEY `{$row->Key_name}` (`{$row->Column_name}`";
                else
                    $unique[$row->Key_name] .= ", `{$row->Column_name}`";
            }
            if (($row->Key_name != 'PRIMARY') AND ($row->Non_unique == '1') AND ($row->Index_type == 'BTREE')) {
                if ((!is_array($index)) OR ($index[$row->Key_name] == ""))
                    $index[$row->Key_name] = "  KEY `{$row->Key_name}` (`{$row->Column_name}`";
                else
                    $index[$row->Key_name] .= ", `{$row->Column_name}`";
            }
            if (($row->Key_name != 'PRIMARY') AND ($row->Non_unique == '1') AND ($row->Index_type == 'FULLTEXT')) {
                if ((!is_array($fulltext)) OR ($fulltext[$row->Key_name] == ""))
                    $fulltext[$row->Key_name] = "  FULLTEXT `{$row->Key_name}` (`{$row->Column_name}`";
                else
                    $fulltext[$row->Key_name] .= ", `{$row->Column_name}`";
            }
        }
        $sqlKeyStatement = '';
        // generate primary, unique, key and fulltext
        if ($primary != "") {
            $sqlKeyStatement .= ",\n";
            $primary .= ")";
            $sqlKeyStatement .= $primary;
        }
        if (isset($unique) && is_array($unique)) {
            foreach ($unique as $keyName => $keyDef) {
                $sqlKeyStatement .= ",\n";
                $keyDef .= ")";
                $sqlKeyStatement .= $keyDef;

            }
        }
        if (isset($index) && is_array($index)) {
            foreach ($index as $keyName => $keyDef) {
                $sqlKeyStatement .= ",\n";
                $keyDef .= ")";
                $sqlKeyStatement .= $keyDef;
            }
        }
        if (isset($fulltext) && is_array($fulltext)) {
            foreach ($fulltext as $keyName => $keyDef) {
                $sqlKeyStatement .= ",\n";
                $keyDef .= ")";
                $sqlKeyStatement .= $keyDef;
            }
        }
        return $sqlKeyStatement;
    }

    /**
     * If this is a text value
     * @param $field_type
     * @return bool
     */
    private function isTextValue($field_type)
    {
        switch ($field_type) {
            case "tinytext":
            case "text":
            case "mediumtext":
            case "longtext":
            case "binary":
            case "varbinary":
            case "tinyblob":
            case "blob":
            case "mediumblob":
            case "longblob":
                $type = true;
                break;
            default:
                $type = false;
        }
        return $type;
    }

    /**
     * Open a file
     * @param $filename
     * @return resource
     */
    private function openFile($filename)
    {
        return $this->compress ? gzopen($filename, "w9") : fopen($filename, "w");
    }

    /**
     * Save to file
     * @param $file
     * @param $data
     */
    protected function saveToFile($file, $data)
    {
        if ($this->compress)
            gzwrite($file, $data);
        else
            fwrite($file, $data);
        $this->isWritten = true;
    }

    /**
     * @param $file
     */
    protected function closeFile($file)
    {
        if ($this->compress)
            gzclose($file);
        else
            fclose($file);
    }
}