<?php
namespace Jenga\App\Core;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;
use League\Flysystem\FilesystemException;

/**
 * Class to handle some file management functions
 */
class File{
    
    
    public static $filesystem;
    
    /**
     * Load the required disk
     */
    public static function mount(){
        
        $adapter = new Local(ABSOLUTE_PATH, LOCK_EX, Local::DISALLOW_LINKS, [
            'file' => [
                'public' => 0777,
                'private' => 0777,
            ],
            'dir' => [
                'public' => 0755,
                'private' => 0700,
            ]
        ]);
        
        //bind filesystem to service container
        $file = new Filesystem($adapter);
        App::bind('Disk', $file);
    }
    
    /**
     * Removes the root path from the filename
     */
    public static function cleanPath(&$filename){
        //strip root path
        if(strpos($filename, ABSOLUTE_PATH) !== FALSE){
            $filename = str_replace(ABSOLUTE_PATH, '', $filename);
        }
    }
    
    /**
     * Checks if the sent file exists
     * 
     * @param type $filename
     * @return type
     */
    public static function exists($filename){     
        
        static::cleanPath($filename);   
        return App::get('Disk')->has($filename);
    }

    /**
     * Gets the requested file
     * 
     * @param type $filename
     * @return type
     */
    public static function get($filename){  
        
        static::cleanPath($filename);   
        return App::get('Disk')->read($filename);
    } 

    /**
     * Puts the sent data into the assigned file
     * 
     * @param type $filename
     * @param type $data
     * @param type $recursive if true it will create the necessary folders before the file
     * @param type $mode
     * @param type $put_attributes
     * @return type
     */
    public static function put($filename, $data = ''){     
        
        static::cleanPath($filename);   
        
        try{
        
            $put = App::get('Disk')->put($filename, $data);
            return $put;
        }
         catch (FilesystemException $exception){
             return $exception;
         }
    }
    
    public static function write($filename, $data = ''){     
        
        static::cleanPath($filename);   
        
        try{
        
            $put = App::get('Disk')->write($filename, $data);
            return $put;
        }
         catch (FilesystemException $exception){
             return $exception;
         }
    }

    /**
     * Deletes a file
     * 
     * @param type $filename
     * @return type
     */
    public static function delete($filename) {
        
        try{
            
            static::cleanPath($filename);   
            $del = App::get('Disk')->delete($filename);
            
            return $del;
        }
         catch (FilesystemException $exception){
             return $exception;
         }
    }

    /**
     * Creates a folder
     * @param type $foldername
     * @param type $recursive
     * @return type
     */
    public static function createFolder($foldername, $recursive = false, $mode = 0777){     
        
        static::cleanPath($foldername);   
        return App::get('Disk')->createDir($foldername);
    }

    /**
     * Deletes a folder
     * 
     * @param type $dirname
     * @param type $forcerecursively This will force a recursive deletion
     */
    public static function deleteFolder($dirname, $forcerecursively = FALSE) {

        static::cleanPath($dirname);
        return App::get('Disk')->deleteDir($dirname);
    }

    /**
     * Recursively scans all files and folders in sent directory
     * @param type $dir
     * @return type
     */
    public static function scandir($dir){

        $listDir = [];

        if(is_dir($dir)){

            foreach(scandir($dir) as $sub) {

                if ($sub != "." && $sub != ".." && $sub != "Thumb.db") {

                    if(is_file($dir.DS.$sub)) {
                        $listDir[] = $dir.DS.$sub;
                    }
                    elseif(is_dir($dir.DS.$sub)){
                        $listDir[$sub] = self::scandir($dir.DS.$sub);
                    }
                }
            }   
        }
        
        return $listDir;  
    }

    /**
     * Scans the directory using the Flysystem plugin
     * @param type $dir
     * @return type
     */
    public static function scanDirectory($dir){
        
        static::cleanPath($dir);
        return App::get('Disk')->listContents($dir);
    }
    
    /**
     * Returns the local disk instance of Flysystem
     * @returns League\Flysystem\Filesystem
     */
    public static function getDisk(){
        return App::get('Disk');
    }

    /**
     * Recursive version of glob
     * @return array containing all pattern-matched files.
     * @param string $sDir      Directory to start with.
     * @param string $sPattern  Pattern to glob for.
     * @param int $nFlags      Flags sent to glob.
     */
    function globr($sDir, $sPattern, $nFlags = NULL)
    {
            $sDir = escapeshellcmd($sDir);
            // Get the list of all matching files currently in the
            // directory.
            $aFiles = glob("$sDir/$sPattern", $nFlags);
            // Then get a list of all directories in this directory, and
            // run ourselves on the resulting array.  This is the
            // recursion step, which will not execute if there are no
            // directories.
            foreach (@glob("$sDir/*", GLOB_ONLYDIR) as $sSubDir)
            {
                    $aSubFiles = $this->globr($sSubDir, $sPattern, $nFlags);
                    $aFiles = array_merge($aFiles, $aSubFiles);
            }
            // The array we return contains the files we found, and the
            // files all of our children found.
            return $aFiles;
    }//end function

    /**
     * Method to get the parent directory
     * 
     * @param void
     * @return the full path to the parent dir
     */
    function parentDir()
    {
            $parentDir = join(array_slice(split( "/" ,dirname($_SERVER['PHP_SELF'])),0,-1),"/").'/';
            return $parentDir;
    }

    /**
     * Method to change the mode of a file or directory
     * @param mixed $file
     * @param int $octal
     * @example $this->changeMode('/var/www/html/test.php',0777);
     * @return true on success
     */
    function changeMode($file,$octal)
    {
            if(chmod($file,$octal))
                return true;
            else
                return FALSE;
    }

    /**
     * Method to perform a Recursive chmod
     * @param mixed $path
     * @param int $filemode
     * @return bool TRUE on success
     */
    function chmod_R($path, $filemode){
        
        if (!is_dir($path))
        return chmod($path, $filemode);

        $dh = opendir($path);
        while ($file = readdir($dh))
        {
                if($file != '.' && $file != '..')
                {
                        $fullpath = $path.'/'.$file;
                        if(!is_dir($fullpath))
                        {
                                if (!chmod($fullpath, $filemode))
                                return FALSE;
                        }
                        else
                        {
                                if (!$this->chmod_R($fullpath, $filemode))
                                return FALSE;
                        }
                }
        }

        closedir($dh);

        if(chmod($path, $filemode))
        return TRUE;
        else
        return FALSE;
    }

    /**
     * Methiod to convert UNIX style permissions (--rxwrxw) to an octal
     * @param mixed $mode
     * @return int $newmode
     */
    function chmodnum($mode){

        $mode = str_pad($mode,9,'-');
        $trans = array('-'=>'0','r'=>'4','w'=>'2','x'=>'1');
        $mode = strtr($mode,$trans);
        $newmode = '';

        $newmode .= $mode[0]+$mode[1]+$mode[2];
        $newmode .= $mode[3]+$mode[4]+$mode[5];
        $newmode .= $mode[6]+$mode[7]+$mode[8];

        return $newmode;
    }

    /**
     * Method to recursively chown files
     * @param mixed $mypath
     * @param int $uid
     * @param int $gid
     * @return void
     */
    function recurse_chown_chgrp($mypath, $uid, $gid)
    {
            $d = opendir ($mypath) ;
            while(($file = readdir($d)) !== false)
            {
                    if ($file != "." && $file != "..")
                    {
                            $typepath = $mypath . "/" . $file ;
                            if (filetype ($typepath) == 'dir')
                            {
                                    $this->recurse_chown_chgrp ($typepath, $uid, $gid);
                            }

                            chown($typepath, $uid);
                            chgrp($typepath, $gid);
                    }
            }

    }

    /**
     * Copy a file, or recursively copy a folder and its contents
     * @author      Aidan Lister <aidan@php.net>
     * @author      Paul Scott
     * @version     1.0.1
     * @param       string   $source    Source path
     * @param       string   $dest      Destination path
     * @return      bool     Returns TRUE on success, FALSE on failure
     */
    function copyr($source, $dest)
    {
            // Simple copy for a file
            if (is_file($source))
            {
                    return copy($source, $dest);
            }
            // Make destination directory
            if (!is_dir($dest))
            {
                    mkdir($dest);
            }

            // Loop through the folder
            $dir = dir($source);
            while (false !== $entry = $dir->read())
            {
                    // Skip pointers
                    if ($entry == '.' || $entry == '..')
                    {
                            continue;
                    }
                    // Deep copy directories
                    if ($dest !== "$source/$entry")
                    {
                            $this->copyr("$source/$entry", "$dest/$entry");
                    }
            }
            // Clean up
            $dir->close();
            return true;
    }

    /**
     * Method to determine disk free space
     * NOTE: On UNIX like filesystems make sure that the param is given
     * @param string $drive
     * @return int $df
     */
    function df($drive = "C:")
    {
            if(PHP_OS=='WINNT' || PHP_OS=='WIN32')
            {
                    $df = disk_free_space($drive);
            }
            else
            {
                    $df = disk_free_space("/");
            }
            return $df;
    }

    /*
    //gets the Framework extension of the file
    //FINISH THIS
    public static function getFrameworkExt($file)
    {
        $sys = new App();
    }
     * 
     */

    //gets the Root extension of the file
    public static function getRootExt($file)
    {
            $dot = strrpos($file, '.') + 1;

            return substr($file, $dot);
    }

    // Remove any trailing dots, as those aren't ever valid file names.
    public static function makeSafe($file)
    {
            $file = rtrim($file, '.');

            $regex = array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#');

            return preg_replace($regex, '', $file);
    }

}//end class

/*USAGE
 *  <?php
//include the class src
include('file.class.php');

//instantiate the class
$files = new file;

//do a recursive file search for *.php
$phpfiles = $files->search('/var/www/html/phpclasses/','*.php',NULL);
print_r($phpfiles);

//find the parent directory
$parent = $files->parentDir();
echo $parent;

//change a files permissions...
$files->changeMode('/var/www/html/test.php',0777);

//Method to perform a Recursive chmod
$files->chmod_R('/var/www/',0777);

//get the octal for a given permission
$octal = $files->chmodnum('--rxwrxw');
echo $octal;

//Recursively chown files to a group
$files->recurse_chown_chgrp('/var/www/', $uid, $gid);

//recursively copy a directory and contents to dest
$files->copyr('/var/www/phpclasses', '/var/www/php_backups');

//Determine how much free drive space is available
$files->df('/');

//or for Windows
$files->df("C:");
?> 
 */

