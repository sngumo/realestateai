<?php
namespace Jenga\App\Helpers;

/**
 * Contains arbitrary functions which may be of use in the system
 */
class Help {
    
    /**
     * Defines encryption key
     */
    public static function defineEncryptKey() {
        define('ENCRYPTION_KEY', 'd0a7e7997b6d5fcd55f4b5c32611b87cd923e88837b63bf2941ef819dc8ca282');
    }
    
    /**
     * Change to the number of characters you want to display
     * @param type $entext
     * @param type $charno
     * @param type $addDots
     * @return string
     */
    public static function shortenTxt($entext, $charno, $addDots = true){
            
        $text = $entext." ";
        $shortText = substr($text,0,$charno);

        if(strlen($entext)>=$charno){
            return $shortText.($addDots ? " ..." : '');
        }
        else{
            return $text;
        }
    }
    
    /**
     * Combines two associative arrays at a designated array key
     * 
     * @param type $input
     * @param type $key
     * @param type $length
     * @param type $replacement
     * 
     * @return type
     */
    public static function array_splice_assoc( &$input ,$key, $length = 0 , $replacement = null){

        $keys = array_keys( $input );
        $offset = array_search( $key, $keys );

        if($replacement){
            $values = array_values($input);
            $extracted_elements = array_combine(array_splice($keys, $offset, $length, array_keys($replacement)),array_splice($values, $offset, $length, array_values($replacement)));
            $input = array_combine($keys, $values);
        } else {
            $extracted_elements = array_slice($input, $offset, $length);
        }

        return $extracted_elements;
    }
    
    /**
     * Rearranges array keys based on old and new index positions given
     * 
     * @param type $array
     * @param type $oldpos
     * @param type $newpos
     */
    public static function array_move_values(&$array, $oldpos, $newpos) {        
        $out = array_splice($array, $oldpos, 1);
        array_splice($array, $newpos, 0, $out);
    }
    
    /**
     * Inserts an array value into a simple array at a specific index position
     * 
     * @param type &$array array to be modified
     * @param type $index position of index
     * @param type $val value to be inserted
     * @return type
     */
    public static function insert_at_index(&$array, $index, $val){
        
       $size = count($array); //because I am going to use this more than one time
       
       if (!is_int($index) || $index < 0 || $index > $size){
           return -1;
       }
       else{
           $temp   = array_slice($array, 0, $index);
           $temp[] = $val;
           $array = array_merge($temp, array_slice($array, $index, $size));
       }
    }
    
    /**
     * Inserts array item into specific position of existing associative array
     * @param type &$array
     * @param type $item
     * @param type $position
     * @return type
     */
    public static function insertIntoAssoc(&$array, $item = [], $position = 0) {
        
        $previous_items = array_slice($array, 0, $position, true);
        $next_items     = array_slice($array, $position, NULL, true);
        
        $array = $previous_items + $item + $next_items;
    }
   
   /**
     * Check if sent variable is a Closure
     * 
     * @param $var
     * @return type
     */
    public static function is_closure($var){        
        return is_object($var) && ($var instanceof \Closure);
    }
    
    /**
     * Checks if a connection to the entered url can be made
     * 
     * @param type $url
     * @return boolean
     */
    public static function is_connected($url){
        
        $connected = @fopen($url, 'r'); 
        //website, port  (try 80 or 443)
        if ($connected){
            $is_conn = true; //action when connected
            fclose($connected);
        }else{
            $is_conn = false; //action in connection failure
        }
        
        return $is_conn;
    }
    
    /**
     * Checks if string is json or not
     * @param type $string
     * @return boolean
     */
    public static function isHtml($string){
         return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }
    
    /**
     * Checks if sent array is associative
     * @param type $arr
     * @return type
     */
    public static function isAssoc($arr){
        
        if (!is_array($arr)) return NULL;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Get text embedded between two custom limiters
     * 
     * @param type $start_limiter
     * @param type $end_limiter
     * @param type $haystack
     * @param type $invert
     * 
     * @return string
     */
    public static function getEmbeddedText($start_limiter,$end_limiter,$haystack,$invert='no'){
        
        $startno = strlen($start_limiter);
        //$endno = strlen($end_limiter);

        $start_pos = strpos($haystack,$start_limiter)+$startno;

        if ($start_pos === FALSE){
            return FALSE;
        }

        $end_pos = strpos($haystack,$end_limiter);

        if ($end_pos === FALSE){
            return FALSE;
        }

        if($invert=='no'){
            return substr($haystack, $start_pos, ($end_pos)-$start_pos);
        }
        else{
            return substr($haystack,($end_pos+1)+$startno);
        }
    }
    
    /**
     * Performs a base 64 encryption
     * @param type $data
     * @return string
     */
    public static function encrypt($data){        
        return base64_encode($data);
    }
    
    /**
     * Decrypts a base 64 encryption
     * @param type $data
     */
    public static function decrypt($data){        
        return base64_decode($data);
    }
    
    public static function url_encrypt($data){        
        return strtr(base64_encode($data), '+/=', '._-');
    }
    
    public static function url_decrypt($data){        
        return base64_decode(strtr($data, '._-', '+/='));
    }
    
    /**
     * Returns human readable 
     * @param type $file
     * @param type $decimals
     * @return type
     */
    public static function humanFileSize($file, $decimals = 2) {
        
        $size_in_bytes = filesize($file);
        
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($size_in_bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $size_in_bytes / pow(1024, $factor)) . @$size[$factor];
    }
    
    /**
     * Returns array copy
     */
    public static function getArrayCopy($input){
        
        $arrayobj = new \ArrayObject($input);
        return $arrayobj->getArrayCopy();
    }
}