<?php
namespace Jenga\MyProject\Users\Controllers;

use Jenga\App\Request\Url;
use Jenga\App\Request\Input;
use Jenga\App\Views\Redirect;
use Jenga\App\Request\Cookie;
use Jenga\App\Request\Session;
use Jenga\App\Models\Utilities\DB;
use Jenga\App\Views\Notifications;
use Jenga\App\Project\Core\Project;
use Jenga\App\Controllers\Controller;
use Jenga\App\Project\Security\Acl\AclReader;

use Jenga\MyProject\Users\Acl\Gateway;

use Jenga\MyProject\Users\Models\UsersModel;
use Jenga\MyProject\Users\Models\UsersProfileModel;

use Jenga\MyProject\Users\Schema\UsersSchema;
use Jenga\MyProject\Agency\Schema\AgentsSchema;
use Jenga\MyProject\Customers\Schema\CustomersLoginSchema as LoginSchema;

use Jenga\MyProject\Users\Views\UsersView;
use Jenga\MyProject\Users\Controllers\Traits;

/**
 * Class UsersController
 * 
 * @property-read UsersModel $model
 * @property-read UsersView $view
 * 
 * @package Jenga\MyProject\Users\Controllers
 */
class UsersController extends Controller{
    
    use Traits\UserCookieTrait;
    use Traits\UserPolicyControllerTrait;
    use Traits\UsersProfileControllerTrait;
    
    public function index(){
        
        if (is_null(Input::get('action')) && is_null(Input::post('action'))) {
            $action = 'show';
        } else {

            if (!is_null(Input::get('action')))
                $action = Input::get('action');
            elseif (!is_null(Input::post('action')))
                $action = Input::post('action');
        }
        
        $this->$action();
    }
    
    /**
     * Displays the login form
     */
    public function showLogin(){
        $this->view->loginForm();
    }
    
    public function showCustomerMobileLogin(){
        $this->showCustomerLogin(true);
    }
    
    public function showCustomerLogin($mobile = false) {
        
        //$this->view->disable();
        $this->view->customerLoginForm($mobile);
    }
    
     public function showCustomerDeleteForm() {
        
        //$this->view->disable();
        $this->view->setViewPanel('forms'.DS.'customerdeletionform');
    }
    
    /**
     * Blank function since the request will be processed and return b4 it gets here
     */
    public function renewSession(){
        
        $this->view->disable();
        
        echo json_encode([
                'status' => 'renewed'
            ]);
    }
    
    /**
     * Validates and return a new session id if needed
     */
    public function requestValidationToken(){
        
        //disable view
        $this->view->disable();
        
        //get the request
        $request = $this->getRequest();
        
        //check if its an ajax request
        if($request->isXmlHttpRequest()){
            echo json_encode([
                'token' => $this->user()->token,
                'created_at' => time()
            ]);
        }
        else{
            return [
                'token' => $this->user()->token,
                'created_at' => time()
            ];
        }
    }
    
    /**
     * Displays the recover login form
     */
    public function recoverLogin(){
        $this->set('from', 'agency');
        $this->view->recoverForm();
    }
    
    /**
     * Displays the recover login form
     */
    public function recoverCustomerLogin(){
        $this->set('from', 'customer');
        $this->view->recoverForm();
    }
    
    /**
     * Send user details
     */
    public function sendUserDetails(){
        
        $this->view->disable();
        
        $result = null;
        $_isemail = false;
        
        //get input data
        $from = Input::post('from');
        $user = Input::post('user');
        
        //checl email address
        if(strpos($user, '@') !== FALSE){
            $_isemail = TRUE;
        }
        
        //filter between users
        if($from == 'customer'){
            
            $customer = $this->call('Customers')->model;
            if($_isemail){
                $result = $customer->where('emailaddress',$user)->first();
            }
            else{
                $result = $customer->login()->where('username', $user)->first();
            }
        }
        elseif($from == 'agency'){            
            $agency = $this->call('Agency')->model;
            $result = $agency->employees()->where('username', $user)->first();
        }
        
        //check result
        if(!is_null($result)){
            
            $email = null;
            if($from == 'agency'){                
                $contacts = json_decode($result->contacts);
                if(!is_null($contacts)){
                    $email = $contacts->email;
                }
            }
            else{
                $email = $result->emailaddress;
            }
            
            $token = base64_encode(json_encode([
                    'from' => $from,
                    'id' => $result->id
                ]));
        
            if(!is_null($email)){
                $notices = $this->call('Notifications');
                $notices->passwordReset($email, $token);
                
                if($from == 'agency'){  
                    Redirect::withNotice('Password reset email has been sent to '.$email, 'success')
                            ->to(Url::link('/login'));
                }
                elseif($from == 'customer'){
                    Redirect::withNotice('Password reset email has been sent to '.$email, 'success')
                            ->to(Url::link('/'));
                }
            }
        }
        
        //check agency or customer
        if($from == 'agency'){  
            Redirect::withNotice('Username or email not found', 'error')
                    ->to(Url::link('/login'));
        }
        elseif($from == 'customer'){
            Redirect::withNotice('Username or email not found', 'error')
                    ->to(Url::link('/'));
        }
    }
    
    /**
     * Send password reset code
     */
    public function sendResetCode(){
        
        $this->view->disable();
        
        $post = Input::post();
        $number = $post['phonenumber'];
        
        $agency = $this->call('Agency')->model;
        $result = $agency->agents()->where('username', $number)->first();
        
        if(!is_null($result)){
            
            //set the reset code
            $resetcode = rand(0, 1000000);
            
            //save the reset code
            $result->password = $resetcode;
            $result->save();
            
            if($result->hasNoErrors()){
                
                $usernumber = $number;
                
                $api = $this->call('Api');
                $api->filterNumber($number);
                
                $sms = $api->sms();
                $sms->setReceiver($number);
                
                if($api->isLocal() == false){
                    
                    $msg = 'Hi, your reset code is '.$resetcode;
                    $status = $sms->send($msg);
                    
                    //set user number
                    $status['usernumber'] = $usernumber;
                    
                    //return response
                    echo json_encode($status);
                }
                else{
                    echo json_encode([
                        'status' => 1
                    ]);
                }
            }
        }
        else{
            echo json_encode([
                'status' => 0,
                'text' => Notifications::Alert('Phone Number not found', 'error', true, false)
            ]);
        }
    }
    
    /**
     * Check the reset code
     */
    public function confirmResetCode(){
        
        $this->view->disable();
        
        $post = Input::post();
        $number = $post['number'];
        $code = $post['resetcode'];
        
        $agency = $this->call('Agency')->model;
        $result = $agency->agents()
                        ->where('username', $number)
                        ->where('password', $code)
                    ->first();
        
        if(!is_null($result)){
            echo json_encode([
                'status' => 1
            ]);
        }
        else{
            echo json_encode([
                'status' => 0,
                'text' => Notifications::Alert('Incorrect code', 'error', true, false)
            ]);
        }
    }
    
    public function saveNewPassword(){
        
        $this->view->disable();
        
        $post = Input::post();
        $number = $post['number'];
        $newpassword = $post['newpassword'];
        
        $agency = $this->call('Agency')->model;
        $result = $agency->agents()
                        ->where('username', $number)
                    ->first();
        
        if(!is_null($result)){
            
            $result->password = md5($newpassword);
            $result->save();
            
            if($result->hasNoErrors()){
                echo json_encode([
                    'status' => 1
                ]);
            }
            else{
                echo json_encode([
                    'status' => 0,
                    'text' => Notifications::Alert(print_r($result->getLastError(), true), 'error', true, false)
                ]);
            }
        }
        else{
            echo json_encode([
                'status' => 0,
                'text' => Notifications::Alert('User not found', 'error', true, false)
            ]);
        }
    }
    
    /**
     * Resets the user password
     * @param type $token
     */
    public function resetUserLogin($token){
        
        $details = json_decode(base64_decode($token));
        
        $this->set('id', $details->id);
        $this->set('from', $details->from);
        
        $this->view->resetPasswordForm();
    }
    
    /**
     * Reset user password
     */
    public function resetPassword(){
        
        $id = Input::post('id');
        $from = Input::post('from');
        
        if($from == 'agency'){
            $table = DB::schema(AgentsSchema::class)->find($id);
        }
        else{
            $table = DB::schema(LoginSchema::class)->find($id);
       }
        
        $table->password = md5(Input::post('password'));
        $table->save();
        
        if($table->hasNoErrors()){
            
            //check agency or customer
            if($from == 'agency'){  
                Redirect::withNotice('Password has been reset', 'success')
                        ->to(Url::link('/login'));
            }
            elseif($from == 'customer'){
                Redirect::withNotice('Password has been reset', 'success')
                        ->to(Url::link('/'));
            }
        }
    }
    
    
    
    /**
     * 
     * @param type $username
     * @param type $password
     */
    public function inlineLogin($username, $password){
        
        //set the variables into the POST variable
        Input::set('usertype', 'smeowner', "POST");
        Input::set('username', $username, "POST");
        Input::set('password', $password, "POST");
        
        //login user
        $this->loginUser();
    }
    
    /**
     * Login mobile user
     */
    public function loginMobileUser(){
        $this->loginUser('true');
    }
    
    /**
     * Login the user
     */
    public function loginUser($mobile = 'false'){
        
        $this->view->disable();
        
        //determine user type
        $user = $this->model->check(Input::post('username'), Input::post('password'));
        $profileid = $user->profile->id;
        
        //if null
        if(is_null($user)){
            $user = FALSE;
        }
        else{
            
            //get the agency linked to the user
            if ($user->enabled === '0') {      
            
                $cfgs = Project::getConfigs();
                die(json_encode([
                    'status' => 0,
                    'message' => 'Your account has been suspended. Please contact the administrator @ '.$cfgs->error_report_email,
                ]));
            } 
        }
        
        //header access control      
        if ($user === FALSE) {
            
            die(json_encode([
                'status' => 0,
                'message' => 'Invalid Username or Password',
            ]));
        } 
        elseif ($user->enabled === '0') {      
            
            die(json_encode([
                'status' => 0,
                'message' => 'Your account has been suspended. Please contact the administrator',
            ]));
        } 
        else {

            //set the last login to now
            $user->last_login = time();

            //set user attributes
            $attributes = [
                'status' => 1,
                'name' => $user->profile->name,
                'username' => $user->username,
                'profile' => $user->profile->id,
                'lastlogin' => $user->last_login,
                'loggedin' => time(),
                'priviledges' => null,
                'message' => 'You have been successfully logged in',
                'created_at' => $user->profile->created_at
            ];
            
            //map user attributes
            Project::user()->mapAttributes($attributes);
            
            //attach role to user
            $role = $this->auth->getRoleByAlias($user->acl);
            Project::user()->attachRole($role);
            
            if(!is_null($user->permissions)){
                Project::user()->addPermissions($user->permissions);
            }

            //commit to gateway
            Project::user()->commit();            
            
            //get configs
            $cfgs = Project::getConfigs();

            //check if from app
            $username = Input::post('username');
            $password = Input::post('password');

            if(is_null($user->userkey)){
                $userkey = $this->setUserKey($username, $password);
            }
            else{
                $userkey = $user->userkey;
            }

            //save the user if needed
            $user->save();
            
            if($userkey !== false){
                $attributes['userkey'] = $userkey;
            }
            
            //set the session variables
            Session::add('logid', Session::id());
            Session::add('userid', $user->id);
            Session::add('accesslevels_id', $user->accesslevels_id);

            //commit user changes to request gateway
            Project::user()->commit();
            
            //return user data
            die(json_encode($attributes));
        }
    }
    
    /**
     * Create a guest user for the app
     */
    public function createGuestUser(){
        
        //disable view
        $this->view->disable();
        
        //get the config
        $config = $this->getConfigs();
        
        //get the gateway
        $gateway = $this->getGateway();
        $role = $gateway->getLowestRole();
        
         //set user data
        $data = [
            'status' => 1,
            'redirecturl' => null,
            'id' => null,
            'name' => 'guest',
            'fullnames' => 'guest',
            'username' => 'guest',
            'acl' => $role->alias,
            'bearer' => $this->user()->token,
            'profileid' => null,
            'agenciesid' => null,
            'lastlogin' => null,
            'fetchinterval' => $config->fetch_interval,
            'minamt' => $config->min_followup_amt,
            'loggedin' => null,
            'priviledges' => null,
            'message' => '',
            'created_at' => time()
        ];
        
        //return the guest
        echo json_encode($data);
    }
    
    /**
     * Logs the user out of the system
     */
    public function logout(){
        
        $this->view->disable();
        $id = Input::get('sessid');

        if (!is_null($id)){
            
            if($this->user()->role->alias == 'smeuser' || $this->user()->role->alias == 'smeowner'){
                $user = $this->call('Agency')->model->agents()->find($this->user()->id);
            }
            else{
                $user = $this->model->find(Session::get('userid'));
            }
            
            //delere session request entry
            $this->getGateway()->clearRequestEntry();
            
            //destroy the user session
            $this->auth->destroyUserState();
            
            $user->save();

            if($user->hasNoErrors()){
                
                //clear cookie
                Cookie::delete('user-timestamp');
                Cookie::delete('user-remember');

                //clear session
                Session::destroy();
                clearstatcache();
                
                //clear localstorage
                echo '<script ype="text/javascript">localStorage.clear();</script>';
                
                //redirect to default
                echo '<script ype="text/javascript">window.location.replace(\''.$this->getConfigs()->frontend_url.'\');</script>';
                
//                Redirect::withNotice('You have been logged out. Thank you come again')
//                              ->to($this->getConfigs()->frontend_url);
            }
        }
    }
    
    /**
     * Log out by user key
     * @param type $key
     */
    public function logoutByUserkey($key){
        
        $this->view->disable();
        
        //delere session request entry
        $gateway = $this->getGateway();
        $gateway->clearRequestEntry();
        
        //get userkey
        $user = $this->call('Agency')->model->agents()->ignoreContext()->find(['userkey' => $key]);
        
        if(!is_null($user)){
            
            $user->last_login = time();
            $user->save();

            if($user->hasNoErrors()){
                
                //clear session
                Session::destroy();
                clearstatcache();
                
                //return status
                die("1");
            }
        }
        
        die("0");
    }
    
    /**
     * List all registered users
     */
    public function show(){
        
        $users = $this->model->linkProfile()->all();        
        foreach($users as &$user){
            
            //get access levels
            $user->access = $this->call('Navigation')->model->access()->find($user->accesslevels_id)->name;
            
            //get login details
            $login = $this->model->find(['usersprofile_id'=> $user->id]);
            
            if(!is_null($login)){
                
                $user->loginid = $login->id;
                $user->username = $login->username;
                $user->enabled = ($login->enabled == 'yes' ? 'Yes' : 'No');
                
                //set last login
                if(!is_null($login->last_login))
                    $user->login = date('F j, Y', $login->last_login);
                else
                    $user->login = 'Not Logged';
                
                //set permissions
                if(!is_null($login->permissions)){
                    $user->perms = 'Open Permissions View';
                }
                else{
                    $user->perms = 'Inherited ('.ucfirst($user->access).')';
                }
            }
            else{
                $user->username = 'Not Available';
                $user->login = 'Not Logged';
                $user->perms = 'Inherited ('.ucfirst($user->access).')';
            }
            
            //reconfiure the rest
            $user->date = date('F j, Y', $user->registered_date);
        }
        
        $this->view->usersListing($users);
    }
    
    /**
     * Deletes users
     */
    public function delete(){
        
        $this->view->disable();
        
        if(Input::has('ids')){
            $ids = Input::post('ids');        
        }
        else{
            $ids[] = Input::get('id');  
        }
        
        $errors = [];
        foreach($ids as $id){
            $this->model->linkProfile()->where('id', $id)->delete();
            
            if($this->model->linkProfile()->hasNoErrors() === FALSE){
                $errors[] = $this->model->linkProfile()->getLastError();
            }
        }
        
        if(count($errors) > 0){
            die(json_encode([
                    "status" => 0,
                    'title' => "Error deleting users",
                    "text" => print_r($errors, TRUE)
                ]));
        }
        else{
            die(json_encode([
                    "status" => 1,
                    'title' => "Deletion Complete",
                    "text" => count($ids)." users deleted"
                ]));
        }
    }
    
    /**
     * Verify user then delete the account
     */
    public function verifyDeleteUserAccount(){
        
        $this->view->disable();
        
        $agencymdl = $this->call('Agency')->model;
        $agents = $agencymdl->agents();
        
        $user = $agents->check(Input::post('username'),  Input::post('password'));   
        
        if(!is_null($user)){
            
            //get the userkey
            $userkey = $user->userkey;
            
            //set into the Input class
            Input::set('userkey', $userkey);
            
            //delete the user
            $del = $this->deleteUserAccount(true);
            if($del){
            
                die(json_encode([
                        'status' => 1,
                        'message' => 'User Account removed successfully'
                    ]));
            }
        }
        else{
            
            die(json_encode([
                'status' => 0,
                'message' => 'User Account Not Found'
            ]));
        }
    }
    
    /*
     * Remove all billing related to the deleted account
     */
    public function removeDeletedAgencyBilling($agencyid){
        
        $billing = $this->call('Billing')->model;
        $bills =  $billing->where('agencies_id', $agencyid)->get();
        
        if(!is_null($bills)){
        
        //remove the combined bills
        $billing->combined()->where('agencies_id', $agencyid)->delete();
       
            //get the billing followups    
            $bfollowups = $billing->followUps()->where('agencies_id', $agencyid)->get();
            
            if(!is_null($bfollowups)){
                
                $invoice = $this->call('Invoices')->model;
                foreach($bfollowups as $bfollowup){

                    //delete the billing followup
                    $invoice->where('id', $bfollowup->invoices_id)->delete();
                }

                //delete the billing
                $billing->where('agencies_id', $agencyid)->delete();
            }
            
            return true;
        }
        
        return null;
    }
    
    /***
     * Delete user account
     */
    public function deleteUserAccount($return = false){
        
        $this->view->disable();
        
        $key = Input::any('userkey');
        $agency = $this->call('Agency');
        $user = $agency->model->agents()->ignoreContext()->find(['userkey' => $key]);
        
        if(!is_null($user)){
            
            $agencyid = $user->agencies_id;
            
            //remove billing linked to the agency
            $this->removeDeletedAgencyBilling($agencyid);
            
            //get invoices by agency id
            $inv = $this->call('Invoices')->model;
            $invoices = $inv->where('agencies_id', $user->agencies_id)->get();
            
            if(!is_null($invoices)){
                foreach($invoices as $invoice){
                    
                    //deletethe invoices
                    $invoice->delete();
                }
            }
            
            //delete the agency
            if($agency->model->where('id', $agencyid)->delete()){
                
                //delete the user
                $user->delete();
                
                if($return){
                    return TRUE;
                }
                
                echo json_encode([
                    'status' => 1
                ]);
            }
        }
    }
    
    /**
     * Add/Edit User form
     * @param type $id
     */
    public function addEditUser($id = null){
        
        //disable view
        $this->view->disable();
        
        //show user form
        $this->view->directAddEditPanel($id);
    }
    
    /**
     * Load the distinct user configuration sections
     * @param type $load
     * @param type $id
     */
    public function load($load, $id = null){
        
        $this->view->disable();
        
        $user = null;
        switch($load){
            
            case 'profile':          
                
                //get user 
                if(!is_null($id))
                    $user = $this->model->linkProfile()->find($id);
                
                //get acls
                $acls = $this->getAccessLevels();
                
                //get user profile form
                $this->view->getUserProfileForm($acls, $user);
                break;
            
            case 'credentials':      
                
                //get user
                if(!is_null($id)){
                    $user = $this->model->profile()->where('usersprofile.id', $id)->first();
                
                    //if empty just load the profile
                    if(is_null($user)){
                        $user = $this->model->linkProfile()->find($id);
                    }
                }
                
                //get the user credentials form
                $this->view->getUserCredentials($user);
                break;
            
            case 'permissions':
                
                //get user
                if(!is_null($id)){
                    $user = $this->model->profile()->where('usersprofile.id', $id)->first();
                    
                    //set user acl
                    $user->acl = $this->call('Navigation')->model->access()
                                        ->find($user->profile->accesslevels_id)->role;
                    
                }
                
                $acls = $this->getElementACLs();
                $this->view->getPermissionsForm($acls, $user);
                break;
        }
    }
    
    /**
     * Save the user details
     * @param type $load
     * @param type $id
     */
    public function save($load, $id = null){
        
        $this->view->disable();
        
        switch($load){
            
            case 'profile':
                
                //get the user profile model
                if(!is_null($id))
                    $user = $this->model->linkProfile()->find($id); 
                else
                    $user = $this->model->linkProfile();
                
                $save = $this->saveProfile($user, $id);
                break;
            
            case 'credentials':
                
                //get the user model
                if(Input::has('id'))
                    $user = $this->model->find(Input::post('id')); 
                else
                    $user = $this->model;
                
                $save = $this->saveCredentials($user, Input::post());
                break;
            
            case 'permissions':
                
                //get the user model
                $user = $this->model->find(Input::post('id')); 
                $profile = $this->model->linkProfile()->find(Input::post('profileid'));
                
                //save permissions
                $save = $this->savePermissions($user, $profile, Input::post());
                break;
        }
        
        if($save === TRUE){
            die(json_encode([
                "status" => 1,
                'title' => "Save succesfull",
                "text" => "User ".ucfirst($load)." saved"
            ]));
        }
        else{
            die(json_encode([
                "status" => 0,
                'title' => "Error saving ".$load,
                "text" => print_r($save, TRUE)
            ]));
        }
    }
    
    /**
     * Save user profile
     * @param UsersModel $user
     * @return boolean
     */
    public function saveProfile(UsersProfileModel $user){
        
        //perform user check
        $count = DB::schema(UsersSchema::class)
                            ->where('mobile_no', Input::post('mobileno'))
                            ->orWhere('email', Input::post('email'))
                        ->getCount();
        
        if($count > 0){
            die(json_encode([
                "status" => 2,
                'title' => "User exists ",
                "text" => "User details: "
                            . Input::post('mobileno') .' or '. Input::post('email')
                            . ' already in use'
            ]));
        }
        
        $user->accesslevels_id = Input::post('user_role');
        $user->name = Input::post('names');
        $user->mobile_no = Input::post('mobileno');
        $user->email = Input::post('email');
        $user->address = Input::post('postal');
        $user->location = Input::post('location');
        $user->verified = Input::post('verified');
        $user->registered_date = time();
        
        $user->save();
        
        if($user->hasNoErrors()){
            return TRUE;
        }
        else{
            return $user->getLastError();
        }
    }
    
    /**
     * Save user credentials
     * @param type $user
     * @param type $data
     */
    public function saveCredentials(UsersModel $user, $data) {
        
        $user->username = $data['username'];
        $user->usersprofile_id = $data['usersprofile_id'];
        
        //check password
        if(array_key_exists('password', $data)){
            $user->password  = md5($data['password']);
        }
        
        $user->enabled = $data['enabled'];
        $user->save();
        
        if($user->hasNoErrors()){
            return TRUE;
        }
        else{
            return $user->getLastError();
        }
    }
    
    /**
     * Save User permissions
     * @param type $user
     * @param type $profile
     * @param type $settings
     * @return boolean
     */
    public function savePermissions($user, $profile, $settings) {
      
        $this->view->disable();
        
        //save profile
        $profile->accesslevels_id = $settings['user_role'];
        $profile->save();
        
        if($profile->hasNoErrors() === FALSE){
            return FALSE;
        }
        
        //get the elments
        $elements = Project::elements(null, null, ['disable'=>'disable','visibility'=>'private']);  
        $elementkeys = array_keys($elements);
        
        $elmlist = [];
        foreach ($elementkeys as $element) {
              
            //check for the default element and add the access action and set to TRUE
            if(array_key_exists('default',$elements[$element])){

                if($elements[$element]['default'] == TRUE){
                    $elmlist[$element]['access'] = TRUE;
                }
            }

            //check if element is present
            $keys = $this->_isElementPresent($element, $settings);
            if($keys !== FALSE){

                foreach($keys as $key){
                    
                    if(Input::post($key) == 'yes'){
                        $value = TRUE;
                    }
                    else{
                        $value = FALSE;
                    }

                    $elmlist[$element][$key] = $value;
                }
            }
        }
        
        $user->permissions = json_encode($elmlist);        
        $user->save();
        
        if($user->hasNoErrors()){
            return TRUE;
        }
        else{
            return $user->getLastError();
        }
    }
    
    /**
     * Checks if element is present to the sent settings
     * @param type $element
     * @param type $settings
     */
    private function _isElementPresent($element, $settings){
        
        $keys = array_keys($settings);
        
        //loop through keys and check
        $list = [];
        foreach($keys as $key){
            
            //split the settings key
            $split = explode('_', $key);
            
            //check against element name
            if(strtolower($element) == $split[0]){
                $list[] = $key;
            }
        }
        
        if(count($list) > 0){
            return $list;
        }
        
        return FALSE;
    }
    
    /**
     * Get user access levels
     * @return type
     */
    public function getAccessLevels(){   
        
        $access = $this->call('Navigation')->model->access();
        $acls = $access->toArray();
        
        $list = [];
        foreach($acls as $acl){
            $list[$acl['id']] = $acl['name'];
        }
        
        return $list;
    }
    
     /**
     * Gets the ACL settings for each element
     */
    public function getElementACLs(){
        
        $elements = array_keys(Project::elements());
        
        $acl = [];
        foreach ($elements as $name) {
            
            $reader = new AclReader($name);
            
            $acl[$name]['base'] = $reader->getBase();
            $acl[$name]['actions'] = $reader->getActions();
            $acl[$name]['roles'] = $reader->getRoles();
            $acl[$name]['aliases'] = $reader->getAliases();
        }
        
        return $acl;
    }
    
    /**
     * handles the passive status operations
     * @param type $section
     * @param type $id
     * @param type $state
     */
    public function setStatus($section, $id, $state){
        
        $this->view->disable();
        
        switch ($section) {
            
            //enable / disable
            case 'activate':
                $model = $this->model->find($id);

                $model->enabled = $state;
                $model->save();                
                break;
            
            //verify / unverify
            case 'verify':
                $model = $this->model->linkProfile()->find($id);

                $model->verified = $state;
                $model->save();   
                break;
        }
       
        if($model->hasNoErrors()){            
            die(json_encode([
                    "status" => 1,
                    'title' => "State Changed to ".ucfirst($state),
                    "text" => "Link status changed"
                ]));
        }
        else{
            die(json_encode([
                    "status" => 0,
                    'title' => "Error changing state",
                    "text" => print_r($model->getLastError(), TRUE)
                ]));
        }
    }
}
