<?php
namespace Jenga\App\Build;

/**
 * Sets up the initial core files for a Jenga project
 *
 * @author Stanley Ngumo
 */

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Project\Security\Gateway;

use Jenga\App\Project\Core\Project;

class Foundation {
    
    public $io;
    public $file;
    public $mouldpath;
    public $progress;
    public $name;
    
    public function __construct(File $file) {     
        
        //assign the IO handler
        $this->io = App::get('io');
        
        //assign file handler
        $this->file = $file;
        
        $this->mouldpath = ABSOLUTE_PATH .DS. 'app' .DS. 'build' .DS. 'moulds';
    }
    
    /**
     * Checks for the core project files
     */
    public function check(){
        
        $configpath = ABSOLUTE_PROJECT_PATH;
        
        if(file_exists($configpath .DS. 'config.php') && file_exists($configpath .DS. 'map.xml')){
            return TRUE;
        }
        else{
            return FALSE;
        }
    }
    
    /**
     * Loads the initialfiles for the project
     */
    public function loadProjectInitializationFiles($projectname) {
        
        $this->name = $projectname;
        
        //create the project folder
        $this->file->createFolder(ABSOLUTE_PROJECT_PATH, TRUE);
        
        //insert init files
        $initfiles = $this->file->scandir($this->mouldpath .DS. 'init');
        
        foreach($initfiles as $file){
            
            $splitfile = explode(DS, $file);
            $filename = end($splitfile);
            
            switch ($filename) {
                
                case 'config.mld':
                    $this->_createConfig($this->file->get($file));
                    
                    $this->io->write(' [..] Configurations file created');
                    $this->io->newLine();
                    break;
                
                case 'events.mld':
                    $this->_createEvents($this->file->get($file));
                    
                    $this->io->write(' [..] Events file created');
                    $this->io->newLine();
                    break;
                
                case 'htaccess.mld':                    
                    $this->_createHtAccess($this->file->get($file));
                    
                    $this->io->newLine();
                    $this->io->write(' [..] Htaccess file rewritten');
                    $this->io->newLine();
                    break;
                
                case 'map.mld':
                    $this->_createMaps($this->file->get($file));
                    
                    $this->io->newLine();
                    $this->io->write(' [..] Maps file created');
                    $this->io->newLine();
                    break;
                
                case 'routes.mld':
                    $this->_createRoutes($this->file->get($file));
                    
                    $this->io->newLine();
                    $this->io->write(' [..] Routes file created');
                    $this->io->newLine();
                    break;
            }
            
            $this->io->newLine();
        }
    }
    
    /**
     * Writes the set database connection settings
     * @param array $db
     */
    public function writeDbSettings($db) {
        
        $config = $this->file->get($this->mouldpath .DS. 'init' .DS. 'config.mld');
        
        if($this->file->exists(ABSOLUTE_PROJECT_PATH .DS. 'config.php')){
            $config = $this->file->get(ABSOLUTE_PROJECT_PATH .DS. 'config.php');
        }
        
        //replace db
        $cfgdata1 = str_replace('{{{db}}}', $db['name'], $config);
        
        //replace host
        $cfgdata2 = str_replace('{{{host}}}', $db['host'], $cfgdata1);
        
        //replace username
        $cfgdata3 = str_replace('{{{username}}}', $db['username'], $cfgdata2);
        
        //replace password
        $cfgdata4 = str_replace('{{{password}}}', $db['password'], $cfgdata3);
        
        //replace prefix
        $cfgdata5 = str_replace('{{{dbprefix}}}', $db['prefix'], $cfgdata4);
        
        //replace port
        $cfgdata = str_replace('{{{port}}}', $db['port'], $cfgdata5);
        
        return $this->file->put(ABSOLUTE_PROJECT_PATH .DS. 'config.php', $cfgdata);
    }
    
    /**
     * Creates the htaccess file
     * @param type $htaccess
     */
    private function _createHtAccess($htaccess) {
        
        $projectfolder = $this->_getRootFolder();        
        $htaccessdata = str_replace('{{{projectfolder}}}', $projectfolder, $htaccess);
        
        if($this->file->exists(ROOT .DS. '.htaccess'))
            $this->file->delete (ROOT .DS. '.htaccess');
                
        return $this->file->put(ROOT .DS. '.htaccess', $htaccessdata);
    }
    
    /**
     * Creates the events.php file
     * @param type $events
     */
    private function _createEvents($events) {
        
        if($this->file->exists(ABSOLUTE_PROJECT_PATH .DS. 'events.php'))
            $this->file->delete (ABSOLUTE_PROJECT_PATH .DS. 'events.php');
        
        return $this->file->put(ABSOLUTE_PROJECT_PATH .DS. 'events.php', $events);
    }
    
    /**
     * Creates the routes.php file
     * 
     * @param type $routes
     */
    private function _createRoutes($routes) {
        
        if($this->file->exists(ABSOLUTE_PROJECT_PATH .DS. 'routes.php'))
            $this->file->delete (ABSOLUTE_PROJECT_PATH .DS. 'routes.php');
        
        return $this->file->put(ABSOLUTE_PROJECT_PATH .DS. 'routes.php', $routes);
    }
    
    /**
     * Creates the project configurations file
     * @param type $cfg
     * @return type
     */
    private function _createConfig($cfg){
        
        $cfgdata = str_replace('{{{project_name}}}', $this->name, $cfg);
        
        if($this->file->exists(ABSOLUTE_PROJECT_PATH .DS. 'config.php'))
            $this->file->delete (ABSOLUTE_PROJECT_PATH .DS. 'config.php');
                
        return $this->file->put(ABSOLUTE_PROJECT_PATH .DS. 'config.php', $cfgdata);
    }
    
    /**
     * Creates the maps.xml file
     * 
     * @param type $maps
     * @return type
     */
    private function _createMaps($maps){
        
        $mapdata = str_replace('{{{projectname}}}', $this->name, $maps);
        
        if($this->file->exists(ABSOLUTE_PROJECT_PATH .DS. 'map.xml'))
            $this->file->delete (ABSOLUTE_PROJECT_PATH .DS. 'map.xml');
                
        return $this->file->put(ABSOLUTE_PROJECT_PATH .DS. 'map.xml', $mapdata);
    }
    
    /**
     * Gets the local root folder
     * @return string the local root folder
     */
    private function _getRootFolder(){
        
        $root = explode(DS, ROOT);
        return end($root);
    }
}
