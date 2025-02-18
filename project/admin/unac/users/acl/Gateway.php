<?php
namespace Jenga\MyProject\Users\Acl;

use Jenga\App\Core\App;
use Jenga\App\Request\Input;
use Jenga\App\Request\Cookie;
use Jenga\App\Request\Session;
use Jenga\App\Models\Utilities\DB;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Security\RequestValidator;
use Jenga\App\Project\Security\GatewayInterface;
use Jenga\App\Project\Security\Traits\Authentication;

use Jenga\MyProject\Users\Schema\RequestsSchema;
use Jenga\MyProject\Agency\Schema\AgentsSchema;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Symfony\Component\HttpFoundation\Request;

class Gateway implements GatewayInterface {
    
    public $validator;
    public $isHpInputValid = null;
    
    use Authentication;
    
    /**
     * Set the user attributes in the attributes array
     * @example $attributes = ['id','name','username','password','accesslevel','loggedin'];
     *
     * The attributes set here will be accessed from the global $this->user() function
     * @example $this->user()->fullname or $this->user()->getFullname()
     */
    public function setUserAttributes() {  
        
        $attributes = ['id','name','username','password','accesslevel','loggedin'];
        $this->user->setAttributes($attributes);
    }
    
    /**
     * This set the element that will be used to authenticate the system users and 
     * therefore isn't subject to any authorization events which may restrict its access
     */
    public function setAuthorizationElement() {
        $this->auth_element = 'users';
    }
    
    /**
     * Checks if user is logged in
     * @return boolean
     */
    public static function isLogged(){        
        
        if(is_int(Project::user()->loggedin)){
            return TRUE;
        }        
        
        //check rememeber cookie and login again
        $remember = 'user-remember';
        if(Cookie::has($remember)){
            
            $data = json_decode(Cookie::get($remember));
            return Project::call('Users')->inlineLoginViaRememberKey($data->username, $data->userkey);
        }
        
        return FALSE;
    }
    
    /**
     * 
     * @param Request $server
     */
    public function isServerSecure($server) {
        
        if(!empty($server['HTTP_X_FORWARDED_PROTO'])){
            $protocol = $server['HTTP_X_FORWARDED_PROTO'];
        }
        else{ 
            $protocol = !empty($server['HTTPS']) ? "https" : "http";
        }
        
        return ($protocol === 'https');
    }

    /**
     * @return array
     */
    public static function getRole(){
        if (isset(Project::user()->role)){
            return Project::user()->role;
        }
        return FALSE;
    }
    
    /**
     * 
     * @param Request $request
     */
    public function validateRequest(Request $request){
        
        //check if the request is secure
        $config = App::get('_config');

        //check for a secure request
        if($config->development_environment === FALSE){
        
            //check if server connection is secure
            if($this->isServerSecure($_SERVER) == false){
                return FALSE;
            }
        }
        
        //check element visibility
        $controller = $request->attributes->all()['_controller'];
        $ctrclass = explode('::', $controller);
        
        $elm = Project::elements($ctrclass[0]);
        if(!is_null($elm) && $elm['visibility'] == 'public'){
            return TRUE;
        }
        else{
            
            //check if static, allow rendering of the static content
            if($ctrclass[0] == '_static'){
                return TRUE;
            }
        }
        
        //check for bot crawler, if crawler reject request
        if($this->crawler->isCrawler()){
            die(json_encode([
                    'status' => 'bot_detected'
                ]));
        }
        
        //patch for symfony request header bug
        if(!$request->headers->has('host')){
            
            $headers = getallheaders();
            $request->headers->add($headers);
        }
        
         //set bypass of request validation
        if($request->headers->has('bypass-req-validation')){
            
            $bypass = $request->headers->get('bypass-req-validation');
            if($bypass == 'true'){
                return TRUE;
            }
        }
        
         //set request expiry by force
        if($request->headers->has('force-request-expiry') && $request->headers->has('refresh-req-timestamp') === false){
            $forceexpiry = $request->headers->get('force-request-expiry');
            if($forceexpiry == 'true'){
            
                die(json_encode([
                    'status' => 'expired'
                ]));
            }
        }
        
        //checl honeypot validation
        if($request->headers->has('validate-hp-input')){
            
            $hp = $request->headers->get('validate-hp-input');
            if(Input::any($hp) !== ''){
                die(json_encode([
                    'status' => 'invalid'
                ]));
            }
            else{
                $this->isHpInputValid = true;
            }
        }
        
        //dont record requests by guests
        $sysuser = Project::user();
        
        //see if its a browser request
        if($this->checkIfBrowserRequest($request)){
            $userverify = !is_null($sysuser->token);
        }
        else{
            $userverify = $this->verifyUserByKey($request, $sysuser);
        }
        
        //check the user level
        if($sysuser->role->level > 0 && $userverify == true){
            
            //get the validator and run
            $validator = new RequestValidator($request);

            //run the validator
            $auth = $validator->authenticateTokens();

            //check the token auth statis
            switch ($auth['status']) {

                case 'new':
                        $validate_status = $validator->createOrRefreshRequestEntry();
                    break;

                case 'expired':

                    //check refresh request timestamp
                    if($request->headers->has('refresh-req-timestamp')){
                        $validate_status = $validator->createOrRefreshRequestEntry(true);
                    }
                    else{                    
                        die(json_encode([
                                'status' => 'expired'
                            ]));
                    }
                    break;

                default: 

                        //the token is current
                        $validate_status = TRUE;

                        //update/refresh the request
                        $validator->createOrRefreshRequestEntry();
                    break;
            }

            //save the validator intothe App container
            $this->validator = $validator;
            App::set('validator', $validator);

            //return validate status
            return $validate_status;
        }
        else{
            
            //check honeypot validation flag
            if(is_null($this->isHpInputValid)){
                die(json_encode([
                    'status' => 0,
                    'message' => 'Invalid Request'
                ]));
            }
            else{
                return $this->isHpInputValid;
            }
        }
    }
    
    /**
     * Checks if the sent request is from a browser
     * @param type $request
     */
    public function checkIfBrowserRequest($request){
        
        //get the user agent
        $userAgent = $request->headers->get('User-Agent');

        // List of common browser strings to check against
        $knownBrowsers = [
            'Mozilla', 'Chrome', 'Safari', 'Opera', 'IE', 'Edge', 'Firefox', 'Netscape', 'Konqueror', 'SeaMonkey', 'Avant', 'Maxthon',
            'Flock', 'AOL', 'Dillo', 'Links', 'Lynx', 'w3m', 'curl', 'wget'
        ];

        // Check if the user agent contains any of the known browser strings
        $isBrowser = false;
        
        foreach ($knownBrowsers as $browser) {
            if (stripos($userAgent, $browser) !== false) {
                $isBrowser = true;
                break;
            }
        }
        
        return $isBrowser;
    }
    
    public function verifyUserByKey($request, $user){
        
        //check for userkey
        if($request->headers->has('userkey')){
                if($request->isXmlHttpRequest() && $user->role->level > 0){
                
                $userkey = $request->headers->get('userkey');
//                var_dump($userkey);
                $agent = DB::schema(AgentsSchema::class)->find(['userkey' => $userkey]);
                
                //if not found cancel the request
                if(!is_null($agent)){
                    return TRUE;
                }
                else{
                    die(json_encode([
                        'status' => 0,
                        'message' => 'Invalid Userkey Not Found'
                    ]));
                }
            }
        }        
        else{
            return FALSE;
        }
    }
    
    /**
     * Returns the request validator
     * @return RequestValidator
     */
    public function getValidator(){
        
       //get validator from session
        $gateway = Project::getGateway();
        
        if(!is_null($gateway->validator)){
            return $gateway->validator;
        }
        
        return NULL;
    }
    
    /**
     * Deletes request from DB table
     * @return type
     */
    public function clearRequestEntry(){
        
        $token = Project::user()->token;
        $schema = RequestsSchema::class;

        return DB::schema($schema)->where('token', $token)->delete();
    }
}
