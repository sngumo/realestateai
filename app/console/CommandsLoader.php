<?php
namespace Jenga\App\Console;

use Symfony\Component\Console\Application;

/**
 * This class loads the console commands for use inside the framework or project
 *
 * @author Stanley Ngumo
 */
class CommandsLoader {
    
    public $app;
    
    public function __construct(){
        
        $commands = $this->loadCommands();
        $this->load($commands);
        
        return $this;
    }
    
    /**
     * Scans the selected folder and 
     * 
     * @param type $dir
     * @return type
     */
    public function scandirs($dir){
            
        $listDir = array();
        if($handler = @opendir($dir)) {
            while (($sub = readdir($handler)) !== FALSE) {
                if ($sub != "." && $sub != ".." && $sub != "Thumb.db") {
                    if(is_file($dir.DS.$sub)) {
                        $listDir[] = $dir.DS.$sub;
                    }
                    elseif(is_dir($dir.DS.$sub)){
                        $listDir[$sub] = $this->scandirs($dir.DS.$sub);
                    }
                }
            }   
            closedir($handler);
        }
        return $listDir;  
    }
    
    public function loadCommands() {
        
        $dirs = $this->scandirs(ROOT.DS.'app'.DS.'console'.DS.'commands');
        
        foreach($dirs as $name => $folder){
            if(is_array($folder)){
                foreach($folder as $file){                    
                    $cmd = basename($file,'.php');
                }
                $commands[] = $cmd;
            }
            else{
                $commands[] = basename($folder,".php");
            }
        }
        
        return $commands;
    }
    
    /**
     * Executes the commands loaded into the framework
     * 
     * @param type $commands
     * @param type $execute
     * @return Application
     */
    public function load($commands){
        
        $this->app = new Application();
        
        foreach($commands as $command){
            $namespaced_command = 'Jenga\App\Console\Commands\\'.$command;
            $this->app->add(new $namespaced_command ());
        }
        
        return $this->app;
    }
}
