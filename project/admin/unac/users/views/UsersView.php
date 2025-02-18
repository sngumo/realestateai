<?php
namespace Jenga\MyProject\Users\Views;

use Jenga\App\Core\App;
use Jenga\App\Views\View;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Security\UserPermissions;

use Jenga\App\Html\Table;
use Jenga\App\Request\Url;
use Jenga\App\Views\Overlays;

use Jenga\App\Html\ToolBar;
use Jenga\App\Html\Tables\Generate;
use Jenga\App\Html\Tables\Components\Row;
use Jenga\App\Html\Tables\Components\Grid;
use Jenga\App\Html\Tables\Components\Column;

use Jenga\App\Html\Form;
use Jenga\MyProject\Users\Views\Traits;

class UsersView extends View {

    use Traits\UserPolicyViewTrait;
    use Traits\UsersProfileViewTrait;
    
    public function loginForm(){
        
        //get acl gateway
        $gateway = App::get('auth');
        $roles = $gateway->getRoles();
        unset($roles[0]);
        
        //create list by level
        $list = [];
        foreach($roles as $key => $role){
            $list[$role->level] = $key;
        }
        
        ksort($list);
        
        //build select form
        $select = '<select name="usertype" data-parsley-required="1" class="form-control">';
        $select .= '<option value="">Select User Type</option>';
        
        foreach($list as $key){
            $select .= '<option value="'.$roles[$key]->alias.'">'.$roles[$key]->name.'</option>';
        }
        
        $select .= '</select>';
        
        $this->set('userselect', $select);
        $this->setViewPanel('forms'.DS.'loginform');
    }
    
    /**
     * Create the customer login form
     */
    public function customerLoginForm($mobile = false) {
        
        //add hidden
        $hidden = '<input type="hidden" name="usertype" value="smeowner">';
        $this->set('userhidden', $hidden);
        
        if($mobile){
            $this->setViewPanel('forms'.DS.'customermobileloginform');
        }
        else{
            $this->setViewPanel('forms'.DS.'customerloginform');
        }
    }
    
    /**
     * Overlay for customer login
     * @param type $form
     */
    public function customerOverlay($form){
        
        //create overlay
        $modal_settings = [
            'id' => 'contactmodal',
            'title' => 'Log into BimaGuru Portal',
            'formid' => 'customerlogin',
            'submitmode' => 'normal',
            'role' => 'dialog'
        ];
        echo  Overlays::ModalDialog($modal_settings, $form, true);
    }
    
    public function recoverForm(){
        $this->setViewPanel('forms'.DS.'recoverform');
    }
    
    public function resetPasswordForm(){
        $this->setViewPanel('forms'.DS.'resetform');
    }
    
    /**
     * Display users in table
     * @param type $users
     */
    public function usersListing($users){
        
        $tablename = 'users_table';
        $table = new Table($tablename,['class' => 'striped hovered']);
        
        //show children on load
        $table->showChildRowsOnLoad(FALSE);
        
        //set columns
        $columns = ['id' => ['grid' => ['hide','hide','hide'],'attrs' => ['hidden'=>TRUE]],
                        '<span style="padding-left: 25px;">Full Names</span>' => ['grid' => [2,2,9]], 
                        'Status' => ['grid' => [2,2,3]],
                        'Username' => ['grid' => [2,2,3]],
                        'Access Level' => ['grid' => [2,2,12]],
                        'Last Login' => ['grid' => [2,2,12]],
                        'Permissions' => ['grid' => [2,2,12]]
                    ];
        
        //align columns
        list($id, $fullnames, $status, $username, $accesslevel, $logdate, $perms) = Generate::Columns($columns);
        
        if(count($users) > 0){
            
            //set header row
            $table->addHeaderRow([$id, $fullnames, $status, $username, $accesslevel, $logdate, $perms]);
            
            //populate rows
            foreach($users as $user){
                
                $id = json_encode([$user->id, $user->name, $user->access]);
                
                //add verfied and enabled badges
                if($user->enabled == 'No'){
                    $status = '<span class="badge label-danger">'
                                . '<a data-placement="top" data-trigger="hover" data-toggle="popover" title="Status" data-content="Not Enabled">'
                                    . '<i class="fa fa-times"></i>'
                                . '</a>'
                            . '</span>';
                }
                else{
                    $status = '<span class="badge label-success">'
                                . '<a data-placement="top" data-trigger="hover" data-toggle="popover" data-content="Enabled">'
                                    . '<i class="fa fa-check"></i>'
                                . '</a>'
                            . '</span>';
                }
                
                if($user->verified == 'no'){
                    $status .= '<span class="badge label-danger">'
                                . '<a data-placement="top" data-trigger="hover" data-toggle="popover" data-content="Not Verified">'
                                    . '<i class="fa fa-times"></i>'
                                . '</a>'
                            . '</span>';
                }
                else{
                    $status .= '<span class="badge label-success">'
                                . '<a data-placement="top" data-trigger="hover" data-toggle="popover" data-content="Verified">'
                                    . '<i class="fa fa-check"></i>'
                                . '</a>'
                            . '</span>';
                }
                
                if(!is_null($user->enabled)){
                    $enabled = '<a class="dropdown-item" onclick="jng.passiveExecute(event)" href="'.Url::link('/settings/users/configure/status/activate/'.$user->loginid.'/'.($user->enabled == 'No' ? 'yes' : 'no')).'">'
                                            . ($user->enabled == 'No' ? 
                                                    '<i class="fa falist fa-check-square"></i> Enable ' 
                                                    : '<i class="fa falist fa-times-circle-o"></i> Disable ').$user->name
                                        . '</a>';
                }
                else{
                    $enabled = '<a class="dropdown-item" style="color:red"><i class="fa falist fa-terminal"></i> Credentials not found </a>';
                }
                
                $table->addRow([htmlentities($id), '<strong>'.$user->name.'</strong>', $status, $user->username, $user->access, $user->login, $user->perms])
                                ->attachShortcuts(0, [
                                        '<a class="dropdown-item shortcut-modal" href="'.Url::link('/settings/users/addedit/'.$user->id.'?tab=profile').'">'
                                            . '<i class="fa falist fa-edit"></i> Open/Edit '.$user->name
                                        . '</a>',
                                        '<a class="dropdown-item shortcut-modal modal-backdrop-static" href="'.Url::link('/settings/users/addedit/'.$user->id.'?tab=credentials').'">'
                                            . '<i class="fa falist fa-terminal"></i> Update Login Credentials'
                                        . '</a>',
                                        '<a class="dropdown-item shortcut-modal modal-backdrop-static" href="'.Url::link('/settings/users/addedit/'.$user->id.'?tab=permissions').'">'
                                            . '<i class="fa falist fa-sort-amount-asc"></i> Change Access Permissions'
                                        . '</a>',
                                        $enabled,
                                        '<a class="dropdown-item" onclick="jng.passiveExecute(event)" href="'.Url::link('/settings/users/configure/status/verify/'.$user->id.'/'.($user->verified == 'no' ? 'yes' : 'no')).'">'
                                            . ($user->verified == 'no' ? 
                                                    '<i class="fa falist fa-unlock-alt"></i> Verify ' 
                                                    : '<i class="fa falist fa-unlock"></i> Unverify ').$user->name
                                        . '</a>',
                                        '<a class="dropdown-item" href="'.Url::link('/ajax/settings/users/delete/'.$user->id).'" onclick="jng.confirmAction(event, \'Do you want to delete '.$user->name.'?\')">'
                                                . '<i class="fa falist fa-trash-o"></i> Delete '.$user->name
                                        . '</a>'
                                    ]);
            }
        }
        else{
            $table->addHeaderRow([
                    Column::cell('<div class="kode-alert kode-alert-icon alert2" style="text-align: center">
                                <i class="fa fa-eye-slash" style="font-size: 20px; margin-top: -5px;"></i>
                                No records found
                              </div>',[
                    Grid::onMediumDevices(12), 
                    Grid::onLargeDevices(12), 
                    Grid::onSmallDevices(12)], null)
                ]);
        }
        
        //add footer row
        $table->addFooterRow([
            Column::cell('<p class="pull-right"><small>Note: Select by clicking the row</small></p> ',[
                            Grid::onMediumDevices(12), 
                            Grid::onLargeDevices(12), 
                            Grid::onSmallDevices(12)], null)
        ]);
        
        //attach batch tools
        $table->attachBatchTools(['delete']);
        
        //set the user count
        $this->set('usercount', count($users));
        
        //set toolbar
        $this->set('toolbar', $this->usersPageToolBar($tablename));
        
        //set table 
        $this->set('userslistingtable', $table->render());
    }
    
    /**
     * Create user listing toolbar
     * @param type $name
     * @return type
     */
    public function usersPageToolbar($name){
        
        $tool = new ToolBar($name, ['class'=>'tool-icons nav nav-pills m-b-10']);
        
        //add page ttle
        $tool->add('title','<div class="bg-icon pull-left bg-success">
                                <i class="icon-user-following" style="color:#fff"></i>
                            </div>
                            <h2 class="m-t-10">
                                Users Manager
                            </h2>
                            <span class="text-muted">'
                                .$this->get('usercount').' listed user(s)'
                        . '</span>', null,['class'=>'col-sm-10']);
        
        
        //add user button
        $tool->add('add', '<div class="text-center">
                                <div class="bg-icon-small float-left">
                                    <i class=" icon-plus"></i>
                                </div>
                                <span class="text-muted" style="display: block;">Add</span>
                            </div>', 
                            Url::link('/settings/users/addedit'),['class'=>'nav-item col-sm-1'])
                ->modal('#addeditmodal','static',['data-keyboard' => 'false']);
        
        //add delete button
        $tool->add('delete', '<div class="text-center">
                                <div class="bg-icon-small float-left">
                                    <i class=" icon-close"></i>
                                </div>
                                <span class="text-muted" style="display: block;">Delete</span>
                              </div>', 
                            Url::link('/ajax/settings/users/delete'), ['class' => 'nav-item col-sm-1']);
        
        return $tool->create();
    }
    
    /**
     * The add/edit user form
     * @param type $id
     */
    public function directAddEditPanel($id = null){
        
        //insert the panel directly and it will load the forms via ajax
        $this->getPanelFile('addedituser', ['id' => $id], FALSE);
    }
    
    /**
     * The user profile form
     * @param type $acl
     * @param type $user
     * @return type
     */
    public function getUserProfileForm($acls, $user = null){
        
        $formname = 'profileform';
        $userform = new Form($formname, Url::link('/settings/users/configure/save/profile'
                            . (!is_null($user) ? '/'.$user->id : '')));
        
        //get the user roles
        $userform->addSelect('User Role', 'user_role', (!is_null($user) ? $user->accesslevels_id : ''), $acls)
                    ->validate(['required']);
        
        $userform->addTextField('Full Names', 'names', (!is_null($user) ? $user->name : ''))
                    ->validate(['required']);
        
        $userform->addTextField('Mobile Number', 'mobileno', (!is_null($user) ? $user->mobile_no : ''))
                    ->validate(['required']);
        $userform->addTextField('Email Address', 'email', (!is_null($user) ? $user->email : ''))
                    ->validate(['required', 'email']);
        
        $userform->addTextField('Physical Location', 'location', (!is_null($user) ? $user->location : ''));
        $userform->addTextField('Postal Address', 'postal', (!is_null($user) ? $user->address : ''));
        
        $userform->addSelect('Verified', 'verified', 0,[1 => 'Yes', 0 => 'No'])
                    ->validate('required');
        
        $userform->addButton('<i class="fa fa-times"></i> Close Panel', 'closeprofile', '', ['class' => 'pull-left btn btn-white', 'data-dismiss' => 'modal']);
        $userform->addButton('<i class="fa fa-save"></i> Save Profile', 'saveprofile', '', 
                                ['class' => 'pull-right btn btn-default',
                                 'onclick' => 'jng.saveFromOverlay("#'.$formname.'", "Saving User Profile")'],'button');
        
        $userform->map([1,1,2,2,1,2]);        
        
        $userform->render('vertical');
    }
    
    /**
     * Returns the user login form
     * @param type $user
     */
    public function getUserCredentials($user = null){
        
        if(!is_null($user)){
            
            $formname = 'credentialsform';
            $credform = new Form($formname, Url::link('/settings/users/configure/save/credentials'));

            //check user id
            if(property_exists($user, 'id')){
                $credform->addHidden('id', $user->id);
            }
            
            //check users profile_id
            if(property_exists($user, TABLE_PREFIX.'usersprofile_id'))
                $credform->addHidden('usersprofile_id', $user->{TABLE_PREFIX.'usersprofile_id'});
            else
                $credform->addHidden('usersprofile_id', $user->id);
            
            $credform->addTextField('Username', 'username', (!is_null($user) ? $user->username : ''))
                        ->validate(['required']);
            
            $credform->addPassword('Password', 'password', '', ['placeholder' => 'Min chars 6'])
                        ->validate(['minlength' => '6']);
            $credform->addPassword('Confirm Password', 'cpassword')
                        ->validate(['equalto'=>'password']);
            
            $credform->addSelect('Enabled', 'enabled', (!is_null($user) ? $user->enabled : 'yes'), ['yes' => 'Yes', 'no' => 'No'])
                        ->validate('required');
            
            $credform->addButton('<i class="fa fa-times"></i> Close Panel', 'close', '', ['class' => 'pull-left btn btn-white', 'data-dismiss' => 'modal']);
            $credform->addButton('<i class="fa fa-save"></i> Save Credentails', 'save', '', 
                                    ['class' => 'pull-right btn btn-default',
                                     'onclick' => 'jng.saveFromOverlay("#'.$formname.'", "Saving User Credentials")']);

            $credform->map([1,2,1,2]);        

            $credform->render('vertical');
        }
        else{
            die('<div class="kode-alert kode-alert-icon kode-alert-click alert5">
                    <i class="fa fa-warning"></i>
                    The user credemtials cannot be set without the user
                  </div>');
        }
    }
    
    /**
     * Returns user permissions form
     * @param type $acls
     * @param type $user
     */
    public function getPermissionsForm($acls, $user = null){
        
        if(!is_null($user)){
            
            $permform = new Form('permissionsform', Url::link('/settings/users/configure/save/permissions'));

            //get the acl roles
            $accesslevels = $this->call('Navigation')->model->access()->all();
            $acllist = [];
            foreach($accesslevels as $access){
                $acllist[$access->id] = $access->name;
                
                if($access->role == $user->acl){
                    $user->aclid = $access->id;
                }
            }
            
            $permform->addHidden('id', $user->id);
            $permform->addHidden('profileid', $user->profile->id);
            $permform->addHidden('enabled','no');
            
            $permform->addCheckBox('Full User Access','enabled', 'yes', (!is_null($user) && $user->enabled == 'yes' ? ['checked'=>'checked'] : []));

            //add roles
            $permform->addSelect('User Role','user_role', (!is_null($user) ? $user->aclid : ''), $acllist);

            //get the individual element permisiions
            $controls = $this->getElementPermissions($permform, $user, $acls);

            //render form
            $permform->render(ABSOLUTE_PATH
                                .DS. 'public' .DS. 'panels' .DS. 'users' .DS. 'forms' 
                                .DS. 'permissions.php', FALSE, 
                                ['user' => $user,
                                 'acls' => $acls,
                                 'methods' => $controls['element_methods'],
                                 'ctrls' => $controls['status']
                                ]);
        }
        else{
            die('<div class="kode-alert kode-alert-icon kode-alert-click alert5">
                    <i class="fa fa-warning"></i>
                    The user permissions cannot be set without the user
                  </div>');
        }
    }
    
    /**
     * Gets the ACL Role listing
     * @param type $as_array
     * @return type
     */
    public function getAclRolesList($as_array = true){   
        
        $gateway = App::get('gateway');
        $roles = $gateway->getRoles();      
        
        if($as_array){
            
            $list = [];
            foreach ($roles as $role) {
                $list[$role->alias] = $role->name;
            }

            return $list;
        }
        else{
            return $roles;
        }
    }
    
    /**
     * Build the element permissions into the form
     * @param type $permform
     * @param type $user
     * @param type $acls
     */
    protected function getElementPermissions(Form &$permform, $user, $acls) {
        
        //get the project elements
        $elms = Project::elements();
        
        //get the gateway
        $gateway = App::get('gateway');
        
        //get and process roles
        $roles = $this->getAclRolesList(FALSE);
        
        foreach($roles as $role){
            
            //check user against role
            if($role->alias == $user->acl)
                $userrole = $role;
        }
        
        //build acls
        foreach($acls as $element => $acl){
            
            //list element actions
            $elm = $elms[$element];
            
            if(($elm['visibility'] == 'public' || is_null($elm['visibility']) && !array_key_exists('disable', $elm))){
                
                $elements[] = $element;

                //check if element role is equal or greater the user role level
                if(is_null($user->permissions)){
                    
                    if($userrole->level >= $acl['base']->level)
                        $access = true;
                    else
                        $access = false;
                }
                else{
                    
                    $userperms = new UserPermissions($user->permissions);
                    $perm = $userperms->evaluatePerm($element, 'root');
                    
                    if($perm == 'yes')
                        $access = true;
                    elseif($perm == 'no' || $perm === false)
                        $access = false;
                }

                //root access controls
                $controls['controls'][$element.'_root_access'] = ['checkbox', $element.'_root_access', 'yes',($access ? ['checked'=>'checked'] : [])];
                $controls['status'][$element] = ($access ? 'Allowed' : 'Denied');
                
                $permform->addCheckBox($element.'_root_access', $element.'_root_access', 'yes', ($access ? ['checked'=>'checked'] : []));
                
                $controls['controls']['{'.$element.'_root_access}'] = ['hidden', $element.'_root_access', 'no'];
                $permform->addHidden($element.'_root_access', 'no');
                
                $actions = [];
                if(!is_null($acl['actions'])){

                    foreach($acl['actions'] as $index => $method){
                        
                        $role = $acl['roles']->{$index};                    
                        $fullrole = $gateway->getRoleByAlias($role);

                        //check 
                        $actionname = $acl['aliases']->{$index};

                        if(is_null($actionname)){
                            $actionname = ucfirst ($index);
                        }

                        if(is_null($user->permissions)){
                            if($userrole->level >= $fullrole->level){
                                $access = true;
                            }
                            else{
                                $access = false;
                            }
                        }
                        else{
                    
                            $perm = $userperms->evaluatePerm($element, $index);

                            if($perm == 'yes')
                                $access = true;
                            elseif($perm == 'no' || $perm === false)
                                $access = false;
                        }

                        $controls['controls'][$actionname] = ['checkbox', $element.'_'.$index.'_access', 'yes',($access ? ['checked'=>'checked'] : [])];
                        $permform->addCheckBox($actionname, $element.'_'.$index.'_access', 'yes', ($access ? ['checked'=>'checked'] : []));
                        
                        $controls['controls']['{'.$actionname.'}'] = ['hidden', $element.'_'.$index.'_access', 'no'];
                        $permform->addHidden($element.'_'.$index.'_access', 'no');
                        
                        $actions[$index] = $method;
                    }
                }            
            
                $controls['element_methods'][$element] = $actions;
            }
        }
        
        //list element names
        $controls['list'] = $elements;        
        
        //add save user permissions button
        $permform->addButton('<i class="fa fa-times"></i> Close Panel', 'btnclose', '', ['class' => 'pull-left btn btn-white', 'data-dismiss' => 'modal']);
        $permform->addButton('<i class="fa fa-save"></i> Save Users Permission', 'btnsave', '', 
                        [
                            'class' => 'btn btn-default pull-right',
                            'onclick' => 'jng.saveFromOverlay("#'.$permform->getFormName().'", "Saving Access Permissions ...", false, event)'
                        ]);
        
        return $controls;
    }
}

