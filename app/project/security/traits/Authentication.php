<?php
namespace Jenga\App\Project\Security\Traits;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Request\Session;
use Jenga\App\Models\Utilities\DB;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Security\User;
use Jenga\App\Project\Security\Acl\Guest;
use Jenga\App\Project\Security\Traits\UserSessionHandler;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Symfony\Component\HttpFoundation\Request;
use Jenga\MyProject\Agency\Schema\AgentsSchema;

trait Authentication{
    
    protected $config;
    
    /**
     * 
     * @var Symfony\Component\HttpFoundation\Request;
     */
    protected $request;
    public static $instance;

    public $user;
    public $roles;
    public $permissions;
    public $outputformat = 'array';
    public $auth_element;
    public $hydration = null;
    
    /**
     * 
     * @var Jaybizzle\CrawlerDetect\CrawlerDetect;
     */
    public $crawler;
    
    /**
     * @Inject("auth_source")
     */
    public $auth_source;
    
    public function init(Request $request) {
        
        //patch for symfony request header bug
        if(!$request->headers->has('host')){
            
            $headers = getallheaders();
            $request->headers->add($headers);
        }
        
        //set the request
        $this->request = $request;
        
        //set the configs
        $this->getConfigs();
        
        //create the user instance to be used throughout
        $this->createUserState();
        
        //set the sessions security token
        $this->setSecurityToken();
        
        //initiate crawler for bot detection
        $this->crawler = new CrawlerDetect();
        
        //set the assigned user attributes from User Defined Auth class
        $this->setUserAttributes();
        
        //set the user into the session
        $token = $this->user->token;
        
        //hydrate session with user info
        if(!is_null($this->hydration)){
            
            $agent = DB::schema(AgentsSchema::class)->find($this->hydration->uid);
            
            //assign all the user attributes
            $attributes = [
                'id' => $agent->id,
                'name' => $agent->names,
                'username' => $agent->username,
                'acl' => $this->hydration->acl,
                'agenciesid' => $agent->agencies_id,
                'profileid' => $agent->agencies_id,
                'lastlogin' => $agent->last_login,
                'loggedin' => time()
            ];
            
            //map to user
            $this->user->mapAttributes($attributes);
        }
        
        //add to session
        Session::add('user_'.$token, serialize($this->user));
        
        return $this;
    }
    
    /**
     * Get the system configurations from the App shell
     */
    protected function getConfigs() {        
        $this->config = App::get('_config');
    }
    
    /**
     * Get the configured roles
     * @param type $identifier
     * @param type $format
     * @return type
     */
    public function getRoles($identifier = null,$format = null){
        
        if(strtolower($this->auth_source) == 'database'){
            
            $this->format(($format==null ? $this->outputformat : $format))->table($this->auth_table, 'NATIVE');
            
            if(is_null($identifier)){
                return $this->orderBy('level', 'DESC')->show();
            }
            else{                
                return $this->find(['alias'=>$identifier])->data;
            }
        }
        elseif(strtolower($this->auth_source) == 'file'){
            
            $gateway = App::get('auth');
            $gateway->setAuthorizationElement();
            
            $auth = $gateway->getAuthorizationElement();
            $element = Project::elements()[$auth];
            
            $rolefiles = File::scandir(ABSOLUTE_PROJECT_PATH .DS. str_replace('/',DS,$element['path']) .DS. 'acl' .DS. 'roles');
            if(!is_null($rolefiles) && count($rolefiles) > 0){
                
                foreach($rolefiles as $rolefile){

                    $ex = explode(DS, $rolefile);
                    $role_php = end($ex);
                    $role = str_replace('.php', '', $role_php);
                    $class = 'Jenga\MyProject\\'.ucfirst($auth).'\Acl\Roles\\'.$role;

                    $class = App::get($class);
                    $class->path = $rolefile;
                    
                    $roles[] = $class;
                }
            }
            else{
                
                //if role classes havent been set
                $roles[] = App::get(Guest::class);
            }
            return $roles;
        }
    }
    
    /**
     * Selects role by alias
     * @param type $alias
     */
    public function getRoleByAlias($alias){
        
        $roles = $this->getRoles();
        
        foreach($roles as $role){            
            if($alias == $role->alias){
                return $role;
            }
        }
    }
    
    /**
     * Gets the role by sent level
     * @param type $level
     */
    public function getRoleByLevel($level){
        
        $roles = $this->getRoles();
        
        foreach($roles as $role){
            
            if($level == $role->level){
                $levels[] = $role;
            }
        }
        
        //check for multiple role with one level
        if(count($levels) == 1){
            return $levels[0];
        }
        else{
            return $levels;
        }
    }
    
    /**
     * Returns the lowest role/;
     * @return type
     */
    public function getLowestRole(){
        
        $roles = $this->getRoles();
        
        foreach($roles as $role){
            
            $lowest[] = $role->level;
            $list[$role->level] = $role;
        }
        arsort($lowest);
        
        return $list[end($lowest)];
    }
    
    /**
     * Add a security token to the user object
     */
    public function setSecurityToken($regenerate_token = FALSE){
        
        if(!is_null($this->hydration)){
            $newtoken = $this->hydration->bearer;
        }
        else{
            $newtoken = $this->token($regenerate_token);
        }
        
        if($regenerate_token){
            
            //get the old token
            $oldtoken = Session::getSecurityToken();

            //change the user token suffix
            $user = Session::get('user_'.$oldtoken);
            Session::delete('user_'.$oldtoken);
            
            //replace the old user
            Session::add('user_'.$newtoken, $user);
        }
        
        //set token into session
        Session::add('token', $newtoken);
        
        //set the token 
        $this->user->token = $newtoken;
    }
    
    /**
     * Generates random token for each session
     * @return type
    */
    public function token($regenarate  = FALSE){
        
        if($regenarate){
            session_regenerate_id();
        }
        
        return session_id();
    }
    
        /**
     * Return the JWT key
     * @return type
     */
    protected function jwt_key(){
        
        $config = App::get('_config');
        $jwtKey=strtoupper(substr(md5(strtotime($config->date_of_reg)), 4, 36));  
        
        return  implode("-", str_split($jwtKey, 6)); 
    }
    
    /**
     * Returns the authorizing element
     * @return type
     */
    public function getAuthorizationElement(){
        return $this->auth_element;
    }
    
     /**
     * Creates the user instance to be used through out the session
     * @return object the User instance
     */
    public function createUserState(){
        
        //get User object, instatiated as singleton and thus will be reused
        $user = App::$shell->get(User::class);
        
        //rehydrate the session in case session has been reset
        if($this->request->headers->has('hydrate-user-session')){
            
            //get the hydration data
            $this->hydration = json_decode($this->request->headers->get('hydrate-user-session'));
                
            //get the acl
            $acl  = $this->hydration->acl;

            //get the role
            $role = $this->getRoleByAlias($acl);
            
            //attach the role
            $user->attachRole($role);
        }
        else{
            
            //on initial login the lowest role is assigned to the user
            $lrole = $this->getLowestRole();
            $user->attachRole($lrole);
        }
        
        $this->user = $user;
        return $this->user;
    }
    
    /**
     * Destroy the user stored in the session
     * @return type
     */
    public function destroyUserState(){        
        return Session::delete('user_'.Session::getSecurityToken());     
    }
}