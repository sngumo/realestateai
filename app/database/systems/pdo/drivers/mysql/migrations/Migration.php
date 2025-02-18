<?php
namespace Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Migrations;

use Jenga\App\Core\App;
use Jenga\App\Database\Systems\Pdo\Drivers\Mysql\Migrations\DatabaseDumper;

class Migration extends DatabaseDumper{
    
    /**
     * @var string
     */
    protected $db_host = 'localhost';
    /**
     * @var string
     */
    protected $db_name = '';
    /**
     * @var string
     */
    protected $db_user = 'root';
    /**
     * @var string
     */
    protected $db_pass = '';
    /**
     * @var string
     */
    protected $db_port = '3306';
    
    protected $io;

    public function __construct(){
        
        $this->loadConfigurations();
        $this->setMySqli();
    }
    
    /**
     * Load database configurations
     */
    protected function loadConfigurations(){
        
        $configs = App::get('_config');
        
        $this->db_host = $configs->host;
        $this->db_name = $configs->db;
        $this->db_user = $configs->username;
        $this->db_pass = $configs->password;
        $this->db_port = $configs->port;
        $this->prefix = $configs->dbprefix;
        
        //load the io if present
        if(App::has('migrator_io')){
            $this->io = App::get('migrator_io');
        }
    }
    
    /**
     * Compress the exported file into a zip file
     * @param type $bool
     */
    public function compress($bool = TRUE){
        $this->compress = $bool;
    }
    
    /**
     * Asserts the output to be base 16
     * @param type $hex
     */
    public function hexValue($hex = TRUE) {
        $this->hexValue = $hex;
    }

    /**
     * @return $this
     */
    protected function setMySqli(){
        
        $this->mysqli = new \mysqli($this->db_host, $this->db_user, $this->db_pass);
        
        if($this->mysqli->connect_errno) {
            $this->io->error("Failed to connect to MySQL: " . mysqli_connect_error());
            exit;
        }
        elseif(!$this->mysqli->select_db($this->db_name)){
            
            //create the database
            $this->createDatabase();
        }
        
        $this->mysqli->select_db($this->db_name);
        
        //Set encoding
        $this->mysqli->query("SET CHARSET utf8");
        $this->mysqli->query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");
        
        return $this;
    }
    
    /**
     * Creates a database 
     * 
     * @return boolean
     */
    protected function createDatabase() {
        
        $sql = 'CREATE DATABASE '.$this->db_name;
        
        if($this->mysqli->query($sql) === TRUE){
            $this->io->success('Database: '.$this->db_name.' created');
            return TRUE;
        }
        else{
            $this->io->error('Database of '.$this->db_name.' has failed');
            exit;
        }
    }
    
    /**
     * Writes to file the selected database dump
     * 
     * @param array $tables
     * @param null $file_name
     * @param string $export_type
     * 
     * @return bool
     */
    public function export($tables = [], $file_name = null, $export_type = 'both'){
        
        switch ($export_type) {
            case 'structure':
                $this->dump_data = false;
                break;
            case 'data':
                $this->dump_structure = false;
                break;
        }
        
        $this->table_filter = $tables;
        
        $this->setFilePath($file_name);
        $this->doTheDatabaseDump();
        $this->closeFile($this->file);
        
        return $this->file_name;
    }

    /**
     * Import to the database
     * @param $script
     * @param bool $drop Drop existing tables
     * @return string
     */
    public function import($script, $drop = false){
        
        $SQL_CONTENT = (strlen($script) > 300 ? $script : file_get_contents($script));
        $allLines = explode("\n", $SQL_CONTENT);
        
        $this->mysqli->query('SET foreign_key_checks = 0'); //some keys might be pain full to work with
        preg_match_all("/\nCREATE TABLE(.*?)\`(.*?)\`/si", "\n" . $SQL_CONTENT, $target_tables);
        
        if ($drop) {
            foreach ($target_tables[2] as $table) {
                $this->mysqli->query('DROP TABLE IF EXISTS ' . $table);
            }
        }

        $this->mysqli->query('SET foreign_key_checks = 1');
        $this->mysqli->query("SET NAMES 'utf8'");
        
        $templine = '';    // Temporary variable, used to store current query

        foreach ($allLines as $line) {                                            // Loop through each line
            if (substr($line, 0, 2) != '--' && $line != '') {
                $templine .= $line;    // (if it is not a comment..) Add this line to the current segment
                if (substr(trim($line), -1, 1) == ';') {        // If it has a semicolon at the end, it's the end of the query
                    if (!$this->mysqli->query($templine)) {
                        $this->io->error('Error performing query \'<strong>' . $templine . '\': ' . $this->mysqli->error . '<br /><br />');
                    }
                    $templine = ''; // set variable to empty, to start picking up the lines after ";"
                }
            }
        }

        return true;
    }

}