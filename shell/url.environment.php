<?php
//set the environment for Jenga
$base_dir  = ROOT; 
$splitbase = explode(DS,$base_dir);

$base_folder = end($splitbase);
$protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';

$port      = $_SERVER['SERVER_PORT'];
$disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
$domain    = $_SERVER['SERVER_NAME'];

if(substr_count($_SERVER['REQUEST_URI'], $base_folder) >= 1){
    
    $uri = trim($_SERVER['REQUEST_URI'], '/');
    $stringpos = strpos($uri, $base_folder);
    
    if($stringpos >= 1){
        
        $uri = '/'.str_split($uri, $stringpos)[0];
        $uri = rtrim($uri, '/');
    }
    else{
        
        $uri = '';
    }
    
    $relative_url  = $protocol."://".$domain . $disp_port . $uri .'/'. $base_folder; 
}
else{
    
    $relative_url  = $protocol."://".$domain . $disp_port; 
}
