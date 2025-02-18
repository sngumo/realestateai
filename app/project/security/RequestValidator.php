<?php
namespace Jenga\App\Project\Security;

use Carbon\Carbon;

use Jenga\App\Core\App;
use Jenga\App\Request\Session;
use Jenga\App\Models\Utilities\DB;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Security\User;

use Jenga\MyProject\Users\Acl\Gateway;
use Jenga\MyProject\Users\Schema\RequestsSchema;

use Symfony\Component\HttpFoundation\Request;

/**
 * Validates the security features of a request
 */
class RequestValidator {
    
    public $user;
    public $request;
    public $gateway;
    
    public function __construct(Request $request) {
        
        $this->request = $request;
        $this->gateway = unserialize(Session::get('gateway'));
    }
    
    /**
     * Run the validator
     */
    public function createOrRefreshRequestEntry($force_refresh = false){
        
        //get ip from request
        $request = Request::createFromGlobals();
        $ipAddress = $request->getClientIp();
        $configs = Project::getConfigs();
        
        //check the user alias
        if(Project::user()->role->alias == 'guest'){
            
            $user_type = 'guest';
            
            //overwrite local ip
            if($ipAddress == '::1'){
                $ipAddress = '127.0.0.1';
            }
        }
        else{
            $user_type = Project::user()->role->alias;
        }
        
        //start db operations
        $schema = RequestsSchema::class;
        $user_request = DB::schema($schema)
                            ->where('token', Project::user()->token)
                            ->first();

        //check for previous entry
        if(is_null($user_request)){

            $new_user_request = DB::schema($schema);
            $request_url = $request->getRequestUri();

            //set the fields
            $new_user_request->user_id_ip = $ipAddress;
            $new_user_request->user_type = $user_type;
            $new_user_request->request_agent = $request->headers->get('User-Agent');
            $new_user_request->request_url = $request_url;
            $new_user_request->token = Project::user()->token;
            $new_user_request->fetch_interval = $configs->fetch_interval;
            $new_user_request->created_at = time();

            //bypass any random visits to the homepage
            if($request_url !== '/' && $request_url !== '/favicon.ico'){
                
                //create the user
                $savenew = $new_user_request->save();

                if($savenew){
                    return TRUE;
                }
                else{
                    // dump($new_user_request->getLastError(), $new_user_request->getLastQuery());
                    return FALSE;
                }
            }
            else{
                return TRUE;
            }

        }
        else{

            //check fetch interval and refresh if need 
            $timestamp = $request->server->get('REQUEST_TIME');
            $created_at = $user_request->created_at;

            //get thetime difference
            $requesttime = Carbon::createFromTimestamp($timestamp);
            $usertime = Carbon::createFromTimestamp($created_at);

            $diff = $requesttime->diffInMinutes($usertime);

            //if diff is larger than fetch interval  ie the request has expired
            if((int) $diff > (int) $user_request->fetch_interval){

                //check the force refresh flag
                if($force_refresh === FALSE){
                    
                    //return session expired array
                    return [
                        'status' => 2,
                        'message' => 'Session expired'
                    ];
                }
            }
            
            //update the request
            $user_request->user_type = Project::user()->role->alias;
            $user_request->request_agent = $request->headers->get('User-Agent');
            $user_request->request_url = $request->getRequestUri();
            
            //replace created at timestamp
            if($force_refresh){
                $user_request->created_at = time();
            }
            
             //save the user request
            $save = $user_request->save();            
            if($save === FALSE){

                //die if new token is not created
                die(json_encode([
                    'status' => 0,
                    'message' => $user_request->getLastError()
                ]));
            }
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    /**
     * Get the security token in the request
     */
    public function getRequestToken(Request $request = null){

        if(!is_null($request)){
            $this->request = $request;
        }
        
        // Access the request header via the PHP getallheaders()
        $allheaders = getallheaders();
        
        //add to request 
        if(array_key_exists('Authorization', $allheaders)){
            
            //add token to request for future use
            $this->request->headers->add([
                "Authorization" => $allheaders['Authorization']
            ]);

            //now get the bearer token from the request
            $authorizationHeader = $this->request->headers->get('Authorization');

            // Extract the token from the header value
            if ($authorizationHeader !== null) {

                $token = trim(str_replace('Bearer', '', $authorizationHeader));
                return $token;
            } 
        }
        
        return false;
    }
    
    /**
     * 
     * @param type $request
     * @return Carbon/Carbon
     */
    public function getCreatedAt($request) {
        
        $timestamp = $request->headers->get('Token-Created-At');       

        //WORKAROUND
//        $query_list = [];
//         $query = $request->getQueryString();
//        parse_str($query, $query_list);
//        
//        $timestamp = $query_list['token-created-at'] ;
        
        //return Carbon timestamp
        return Carbon::createFromTimestamp($timestamp);
    }
    
    /**
     * Check the validity of the request tokens
     */
    public function authenticateTokens(){
        
        //get the request token
        $token = $this->getRequestToken();
        
        //get the request time
        $request = $this->request->createFromGlobals();
        $timestamp = $request->server->get('REQUEST_TIME');
        
        //set the token status
        if($token !== FALSE){
            
            //get the project fetch interval
            $fetch_interval = Project::getConfigs()->fetch_interval;
            
             //get the token from the db
            $schema = RequestsSchema::class;
            $dbrequest = DB::schema($schema)
                                ->where('token', $token)->get();
        
            //check if expired
            if(!is_null($dbrequest)){
                
                $requesttime = Carbon::createFromTimestamp($timestamp);
                $lastrenewal = $request->headers->get('last-renewal-at');
                
                if(!is_null($lastrenewal)){
                    $lastrenewalobj = Carbon::createFromTimestamp($lastrenewal);
                }
                else{
                    
                    $lastdbrequest = end($dbrequest);
                    $lastrenewalobj = Carbon::createFromTimestamp($lastdbrequest->created_at);
                }

                //check the difference in minutes
                $diff = $requesttime->diffInMinutes($lastrenewalobj);

                 //if diff is larger than fetch interval 
                if((int) $diff > (int) $fetch_interval){
                    
                    //return status
                     return ['status' => 'expired'];
                }
                else{
                    return ['status' => 'valid'];
                }
            }
            else{
                
                $createdat = $this->getCreatedAt($request);
                
                //check diff in mins
                $diff = Carbon::now()->diffInMinutes($createdat);
                
                if($token !== Project::user()->token){
                    
                    if((int) $diff > (int) $fetch_interval){
                        //return expired
                         return ['status' => 'expired'];                        
                    }
                }
                
                return ['status' => 'new'];    
            }
        }
        else{
            
            //check if ajax request
            if($request->isXmlHttpRequest()){
                    //set the new token to be created
                    return ['status' => 'new'];                
            }
            else{
                    //set the new token to be created
                    return ['status' => 'browser'];      
            }
        }
    }
    
    /**
     * Change request attribute
     * @param type $attr
     * @param type $value
     */
    public function changeRequestAttr($attr, $value){
        
        $schema = RequestsSchema::class;
        
         //get the tken from the db
        $token = $this->getRequestToken();
        $request = DB::schema($schema)->where('token', $token)->first();
        
        //change the attribute
        if(!is_null($request)){
            
            $request->{$attr} = $value;

            //save the change
            if($request->save()){
                return TRUE;
            }
            else{
                return [
                    'status' => 0,
                    'request' => $request
                ];
            }
        }
    }
    
    /**
     * Deletes request from DB table
     * @return type
     */
    public function clearRequest(){
        
        $token = $this->getRequestToken();
        $schema = RequestsSchema::class;
        
        return DB::schema($schema)->where('token', $token)->delete();
    }
}
