<?php
namespace Jenga\MyProject\Users\Views\Traits;

use Jenga\App\Views\View;
use Jenga\App\Request\Url;
use Jenga\App\Helpers\Help;

use Jenga\App\Html\Table;
use Jenga\App\Views\Overlays;

use Jenga\App\Html\ToolBar;
use Jenga\App\Html\Tables\Generate;

use Jenga\App\Html\Form;

/**
 * Handles the view for the policies section
 * @author stanley
 */
trait UserPolicyViewTrait {
    
    /**
     * Displays the policies/roles
     * @param type $roles
     */
    public function policiesListing($roles){
        
        $tablename = 'policies_table';
        $table = new Table($tablename,['class' => 'striped hovered']);
        
        //set columns
        $columns = ['alias' => ['grid' => ['hide','hide','hide'],'attrs' => ['hidden'=>TRUE]],
                        'Level' => ['grid' => [2,1,1]],
                        'Name' => ['grid' => [2,3,6]],
                        'Alias' => ['grid' => [1,2,6]],
                        'Description' => ['grid' => [3,3,12]],
                        'Path' => ['grid' => [3,3,12]]
                    ];
        
        list($id, $level, $name, $alias, $description, $path) = Generate::Columns($columns);
        
        //add header row
        $table->addHeaderRow([$id, $level, $name, $alias, $description, $path]);
        
        //populate rows
        foreach($roles as $role){   
            
            $id = json_encode([$role->alias, $role->name, $role->alias]);
            $table->addRow([htmlentities($id), $role->level, $role->name, $role->alias, $role->description, Help::shortenTxt($role->path, '30', TRUE)])
                    ->attachShortcuts(1, [
                        '<a href="'.Url::link('/settings/users/policies/edit/'.$role->alias).'">'
                            . '<i class="fa falist fa-file-code-o"></i>Open/Edit <strong>'.$role->name.'</strong> Rolicy'
                        . '</a>',
                        '<a href="'.Url::link('/settings/users/policies/delete').'" onclick="jng.confirmAction(event, \'Do you want to delete the '.$role->name.' policu?\')">'
                            . '<i class="fa falist fa-trash-o"></i>Delete <strong>'.$role->name.'</strong> Policy'
                        . '</a>'
                    ]);
        }
        
        //attach batch tools
        $table->attachBatchTools(['delete']);
        
        //order
        $table->orderBy($level, 'desc');
        
        //add polices toobar
        $this->set('toolbar', $this->buildPoliciesBar($tablename));
        
        //set table 
        $this->set('policiestable', $table->render());
        
        //set panel
        $this->setViewPanel('policies');
    }
    
    /**
     * Build the policies toolbar
     * @param type $name
     */
    public function buildPoliciesBar($name) {
        
        $tool = new ToolBar($name, ['class'=>'panel quick-menu clearfix']);
        
        //add page ttle
        $tool->add('title', 
            '<div class="row pagetoolbar">
                <div class="col-md-2 titles">
                    <span class="icon">
                        <img src="'.RELATIVE_PROJECT_PATH.'/public/backend/icons/policies-icon.png" style="padding-top: 10px;" width="70px"/>
                    </span>
                </div>
                <div class="col-md-9 titles">
                    <h1 class="pull-left">Policies <span style="font-weight: bold">Manager</span></h1>
                </div>
            </div>', null,['class'=>'col-sm-10']);
        
        
        //add add button
        $tool->add('add', '<i class="fa fa-plus-square"></i>Add', Url::link('/settings/users/policies/new'),['class'=>'col-sm-1'])
                ->modal('#addeditmodal');
        
        //add delete button
        $tool->add('delete', '<i class="fa fa-trash-o"></i>Delete', Url::link('/settings/users/policies/delete'), ['class' => 'col-sm-1']);
        
        return $tool->create();
    }
    
    /**
     * The policy addition form
     */
    public function addPolicyForm(){
        
        $name = 'addpolicyform';
        $policyform = new Form($name, Url::link('/settings/users/policies/create'));
        
        $policyform->addTextField('Policy Name', 'name')->validate(['required']);
        $policyform->addTextField('Alias', 'alias')->validate(['required']);
        $policyform->addTextField('Level', 'level')->validate(['required', 'number']);
        $policyform->addTextArea('Description', 'description')->validate(['required']);
        
        $policyform->map([1,2,1]);
        $policy = $policyform->render('vertical', TRUE);
        
        $modal_settings = [
            'id' => 'addrolemodal',
            'formid' => 'addpolicyform',
            'role' => 'dialog',
            'title' => 'Add New ACL Policy',
            'buttons' => [
                'Cancel' => [
                    'class' => 'btn btn-white',
                    'data-dismiss' => 'modal'
                ],
                'Create Policy' => [
                    'type' => 'submit',
                    'class' => 'btn btn-primary',
                    'id' => 'savebutton'
                ]
            ]
        ];

        $policymodalform = Overlays::ModalDialog($modal_settings, $policy);
        
        $this->set('policyform',$policymodalform);
        $this->setViewPanel('policy-new');
    }
    
    
    /**
     * The edit policy form
     * @param type $alias
     * @param type $role
     * @param type $path
     */
    public function editPolicyForm($alias, $role, $path){
        
        $name = 'editorform';
        $policyform = new Form($name, Url::link('/ajax/settings/users/policies/save/'.$alias));
        
        //the code editor
        $policyform->addHidden('rolepath', $path);
        $policyform->addTextArea('Policy Code Editor', 'editor', $role, ['style'=>'width: 100%; height: 450px']);
        
        $editor = $policyform->render('vertical', TRUE);
        
        $this->set('editor', $editor);
        $this->set('alias', $alias);
        
        $this->set('toolbar', ucfirst($alias).' Policy');
        $this->setViewPanel('policy-edit');
    }
    
    /**
     * The policy edit toolbar
     * @param type $alias
     * @return type
     */
    public function buildPolicyEditToolbar($alias){
        
        $tool = new ToolBar('policyedit', ['class'=>'panel quick-menu clearfix']);
        
        //add page ttle
        $tool->add('title', 
            '<div class="row pagetoolbar">
                <div class="col-md-2 titles">
                    <span class="icon">
                        <img src="'.RELATIVE_PROJECT_PATH.'/public/backend/icons/policies-icon.png" style="padding-top: 10px;" width="70px"/>
                    </span>
                </div>
                <div class="col-md-9 titles">
                    <h1 class="pull-left">'.ucfirst($alias).' <span style="font-weight: bold">Policy</span> Edit</h1>
                </div>
            </div>', null,['class'=>'col-sm-10']);
        
        
        //add save button
        $tool->add('save', '<a href="#" onclick="jng.saveFromCodeMirror(\'#editorform\', \'Saving Policy Edit ...\', false, editor)">'
                                . '<i class="fa fa-save"></i>Save'
                            . '</a>', null, ['class'=>'col-sm-1']);
        
        //add close button
        $nav = $this->call('Navigation');
        $tool->add('close', '<i class="fa fa-sign-out"></i>Close', $nav->getLinkByAlias('user-policies'), ['class' => 'col-sm-1']);
        
        return $tool->create();
    }
}
