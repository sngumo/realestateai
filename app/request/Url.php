<?php
namespace Jenga\App\Request;

use Jenga\App\Core\App;
use Jenga\App\Views\HTML;
use Jenga\App\Project\Routing\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Url{
    
    /**
     * Wraps the sent link into the proper URL notation
     * @param type $url
     * @return type
     */
    public static function link($url){     
        
        if(strpos($url, 'http') === 0){
            return $url;
        }
        else{
            return SITE_PATH.$url;
        }
    }
    
    /**
     * Returns the current system URL
     */
    public static function current($include_native_query = FALSE){
        
        $request = App::get('_request');
        
        if($include_native_query == false){            
            $uri = explode('?', $request->getUri());
            $url = $uri[0];
        }
        else{
            $url = $request->getUri();
        }
        
        //replace http prefix
        if($url == 'http://:/'){
            $url = self::base ();
        }
        elseif(strpos($url, 'http://:/') == 0){
            $url = str_replace('http://:/', '', $url);
        }
        
        return str_replace(SITE_PATH, '', $url);
    }
    
    /**
     * Helper function which returns the main site URL
     * @return string
     */
    public static function base(){   
        return SITE_PATH;
    }
    
    /**
     * Generates full URL path based on route alias
     * @param string $uri ruteote uri or alias
     * @param type $params
     * @param type $urltype Options are ABSOLUTE_PATH, ABSOLUTE_URL, NETWORK_PATH, RELATIVE_PATH 
     */
    public static function route($uri, array $params = null, $urltype = 'ABSOLUTE_PATH'){
        
        $routekeys = App::get('_route_keys');   
        $routeuris = App::get('_route_uris');
        
        if(substr($uri, -1) == '/'){
            $uri = rtrim($uri, '/');
        }
        
        if(is_string($uri)){
            
            $alias = Route::generateAlias($uri); 
            $key = self::_returnMostLikelyRouteKey($alias, $routekeys, $routeuris);
        }
        elseif(is_array($uri)){
            
            if(array_key_exists('alias', $uri))
                $key = $uri['alias'];
            elseif(array_key_exists('name', $uri))
                $key = $uri['name'];
        }
        
        if($key != FALSE){
            
            $type = self::returnUrlGenTypes($urltype);    
            
            if($type === 1)
                $url = self::_simplegenerate($uri, $params, $type);
            else
                $url = self::_generate($key, $params, $type);
            
            //add site root to url
            if($urltype == 'ABSOLUTE_PATH'){
                
                if(strpos(self::base(), $url)===FALSE){
                    $url = self::base().$url;
                }
            }
            
            return $url;
        }
        else{            
            throw App::exception('The sent URI - '.$uri.' are not compatible with any routes');
        }
    }
    
    /**
     * Generates url from route alias
     * 
     * @param type $alias
     * @param array $params
     * @param type $urltype
     * @return type
     */
    public static function alias($alias, array $params = null, $urltype = 'ABSOLUTE_URL'){
        return self::_generate($alias, $params, $urltype);
    }
    
    /**
     * Generates the actual URL from Symfony
     * 
     * @param type $key
     * @param type $params
     * @param type $type
     * @return type
     */
    private static function _generate($key, $params, $type){        
        return App::get('_urlgenerator')->generate($key, (is_null($params) ? [] : $params), $type);
    }
    
    /**
     * Bypasses the Symfony URL generation for a simpler format
     * 
     * @param type $key
     * @param type $params
     * @param type $type
     */
    private static function _simplegenerate($uri, $params, $type){
        
        if($type == 1){
            
            $uris = explode('/', $uri);    
            
            foreach ($uris as $uri) {
                
                if(strpos($uri, '{') === 0){
                    $key = HTML::findInTags('{', '}', $uri);
                    $uri = $params[$key];
                }
                
                $blocks[] = $uri;
            }
            return join('/',$blocks);
        }
    }
    
    /**
     * Processes the partial alias and return the most likely route key
     * 
     * @param type $alias
     * @param type $routekeys
     * @param type $routeuris
     */
    private static function _returnMostLikelyRouteKey($alias, $routekeys, $routeuris){
        
        //check route keys
        foreach($routekeys as $rkey){
            if(strpos($rkey, $alias)){
                return $rkey;
            }
        }
        
        //check roite uris
        foreach($routeuris as $ralias => $uri){
            
            $alias = Route::generateAlias($uri); 
            
            if(!empty($alias)){
                if(strpos($uri, $alias)){
                    return $ralias;
                }
            }
        }
        
        return FALSE;
    }
    
    public static function returnUrlGenTypes($urltype){
        
        switch ($urltype) {
            case 'ABSOLUTE_PATH':
                $type = UrlGeneratorInterface::ABSOLUTE_PATH;
                break;

            case 'ABSOLUTE_URL':
                $type = UrlGeneratorInterface::ABSOLUTE_URL;
                break;

            case 'NETWORK_PATH':
                $type = UrlGeneratorInterface::NETWORK_PATH;
                break;

            case 'RELATIVE_PATH':
                $type = UrlGeneratorInterface::RELATIVE_PATH;
                break;

            default:
                $type = UrlGeneratorInterface::ABSOLUTE_URL;
                break;
        }
        
        return $type;
    }
}
