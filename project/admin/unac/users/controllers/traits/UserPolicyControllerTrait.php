<?php
namespace Jenga\MyProject\Users\Controllers\Traits;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Helpers\Help;
use Jenga\App\Request\Input;
use Jenga\App\Views\Redirect;
use Jenga\App\Project\Core\Project;
use Jenga\App\Controllers\Controller;

use Jenga\MyProject\Navigation\Controllers\Traits;
use Jenga\MyProject\Navigation\Models\NavigationModel;
use Jenga\MyProject\Navigation\Views\NavigationView;

/**
 * Handles the system policies
 * @author stanley
 */
trait UserPolicyControllerTrait {
    
    /**
     * Add the policy form
     */
    public function addPolicy(){
        $this->view->addPolicyForm();
    }
    
    /**
     * Creates new ACL Policy
     */
    public function createPolicy(){
        
        $this->view->disable();
        
        //load the command arguments
        $arguments = [
            'type' => 'role',
            '--name' => Input::post('name'),
            '--alias' => Input::post('alias'),
            '--level' => Input::post('level'),
            '--description' => Input::post('description')
        ];
        
        $command = App::terminal('acl:create', $arguments, FALSE, FALSE);
        
        if($command){
            die(json_encode([
                    "status" => 1,
                    'title' => ucfirst(Input::post('name')). ' Policy Created',
                    "text" => "New policy file created"
                ]));
        }
        else{
            die(json_encode([
                    "status" => 0,
                    'title' => ucfirst(Input::post('name')). ' not created',
                    "text" => print_r($command, TRUE)
                ]));
        }
    }
    
    /**
     * Creates the edit form for the policy
     * @param type $alias
     */
    public function editPolicy($alias){
        
        $gateway = App::get('gateway');
        
        $authpath = Project::elements()[$gateway->auth_element]['path'];
        $fullabspath = ABSOLUTE_PROJECT_PATH .DS. str_replace('/', DS, $authpath) 
                        .DS. 'acl' .DS. 'roles' .DS. ucfirst($alias).'.php'; 
        
        if(File::exists($fullabspath)){
            $rolefile = File::get ($fullabspath);
        }
        
        $this->view->editPolicyForm($alias, $rolefile, $fullabspath);
    }
    
    /**
     * Show the system policies
     */
    public function showSystemPolicies(){
        $gateway = App::get('gateway');
        $roles = $gateway->getRoles(); 
        
        $this->view->policiesListing($roles);
    }
    
    /**
     * Saves the policy edits
     * @param type $alias
     */
    public function savePolicy($alias){
        
        $this->view->disable();
        
        $path = Input::post('rolepath');
        $data = Input::post('editor');
        
        if(File::put($path, $data)){
            
            die(json_encode([
                    "status" => 1,
                    'title' => ucfirst($alias). ' Saved',
                    "text" => "Policy edits saved"
                ]));
        }
        else{
            die(json_encode([
                    "status" => 0,
                    'title' => "Error changing state",
                    "text" => 'File not saved'
                ]));
        }
    }
    
    /**
     * Deletes ACL Policy by alias
     * @param type $alias
     */
    public function deletePolicy($alias = null){
        
        $this->view->disable();
        $gateway = App::get('gateway');
        
        $authpath = Project::elements()[$gateway->auth_element]['path'];
        
        //check alias
        if(is_null($alias)){
            $aliases = Input::post('ids');
        }
        else{
            $aliases = [$alias];
        }
        
        $response = false;
        foreach($aliases as $alias){
            
            $fullabspath = ABSOLUTE_PROJECT_PATH .DS. str_replace('/', DS, $authpath) 
                            .DS. 'acl' .DS. 'roles' .DS. ucfirst($alias).'.php'; 
            
            if(File::exists($fullabspath)){
                $response = File::delete ($fullabspath);
            }
        }
        
        if($response === TRUE){            
            die(json_encode([
                    "status" => 1,
                    'title' => ucfirst($alias). ' Deleted',
                    "text" => "Policy removed"
                ]));
        }
        else{
            die(json_encode([
                    "status" => 0,
                    'title' => "Error deleting policy",
                    "text" => 'File not deleted. Response: ' . print_r($response)
                ]));
        }
    }
}
