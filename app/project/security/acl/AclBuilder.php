<?php
namespace Jenga\App\Project\Security\Acl;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Project\Security\Gateway;
use Jenga\App\Project\Security\Permissions;

use Jenga\App\Project\Core\Project;

/**
 * This class builds the ACL roles and gateway
 *
 * @author Stanley Ngumo
 */
class AclBuilder {
    
    public $io;
    public $file;
    public $mouldpath;
    public $progress;
    public $name;
    public $elements;
    
    public function __construct(File $file) {     
        
        //assign the IO handler
        $this->io = App::get('io');
        
        //assign file handler
        $this->file = $file;
        
        $this->elements = Project::elements();
        $this->mouldpath = ABSOLUTE_PATH .DS. 'app' .DS. 'build' .DS. 'moulds';
    }
    
    /**
     * Creates the ACL gateway
     * @param type $element
     */
    public function createGateway($element) {
        
        $services = include APP .DS. 'services' .DS. 'services.php';
        
        //get the gateway class
        $auth = $services['handlers']['auth']['class'];
        
        if($auth == Gateway::class){
            
            if($this->file->exists($this->mouldpath .DS. 'acl' .DS. 'gateway.mld')){
                
                if(array_key_exists($element, $this->elements)){
                    
                    //get element path
                    $select = $this->elements[$element];
                    $path =  ABSOLUTE_PROJECT_PATH .DS. str_replace('/', DS, $select['path']);
                    
                    //get the gateway mould file
                    $gateway = $this->file->get($this->mouldpath .DS. 'acl' .DS. 'gateway.mld');
                    $gateway_nmsp = 'Jenga\MyProject\\'.ucfirst($element).'\Acl';
                    
                    //replace the meta variables
                    $gateway = str_replace('{{{gateway_namespace}}}', $gateway_nmsp, $gateway);
                    $gateway = str_replace('{{{gateway_element}}}', $element, $gateway);
                    
                    //save the gateway file
                    if($this->file->put($path .DS. 'acl' .DS. 'Gateway.php', $gateway, true)){
                        
                        $this->io->newLine();
                        $this->io->success('Gateway class created in '.$element.' element');
                        
                        $services = $this->file->get(SERVICES .DS. 'services.php');
                        
                        if(strpos($services, 'use '.Gateway::class) !== FALSE){
                            
                            $gatestr = 'use '.Gateway::class;
                            $gatereplace = 'use '.$gateway_nmsp.'\Gateway';
                            
                            $services = str_replace($gatestr, $gatereplace, $services);
                            
                            //services delete
                            $this->file->delete(SERVICES .DS. 'services.php');
                            
                            //save services 
                            $this->file->put(SERVICES .DS. 'services.php', $services);
                            $this->io->success('Services file saved');
                        }
                    }
                }
                else{
                    $this->io->error('Element: '.$element.' not found');
                }
            }
        }
        else{
            $this->io->error('An ACL Gateway already exixts: '.$auth);
        }
    }
    
    /**
     * Creates the ACL role in the authorizing element
     * 
     * @param type $name
     * @param type $element
     */
    public function createRole($name, $element, $alias, $level = 0, $description = ''){
        
        $rolemould = $this->mouldpath .DS. 'acl' .DS. 'role.mld';
        
        if($this->file->exists($rolemould)){
            
            $element = $this->elements[$element];
            $aclpath = ABSOLUTE_PROJECT_PATH .DS. str_replace('/', DS, $element['path']) .DS. 'acl' .DS. 'roles';
            
            $role = $this->file->get($rolemould);
            
            //replace namespace
            $role_namespace = 'Jenga\MyProject\\'.ucfirst($element['name']).'\Acl\Roles';
            $role = str_replace('{{{role_namespace}}}', $role_namespace, $role);
            
            //replace name
            $role = str_replace('{{{role_name}}}', $name, $role);
            
            //replace alias
            if($alias == ''){
                $alias = strtolower(str_replace(' ', '_', $name));
            }
            
            $role = str_replace('{{{role_alias}}}', $alias, $role);
            
            //replace class
            $role_class = ucfirst($alias);
            $role = str_replace('{{{role_classname}}}', $role_class, $role);
            
            //replace level
            if(is_null($level))
                $level = 0;
            
            $role = str_replace('{{{role_level}}}', $level, $role);
            
            //replace description
            $role = str_replace('{{{role_description}}}', $description, $role);
            
            //save the role class
            if($this->file->put($aclpath .DS. ucfirst($alias).'.php', $role, true, 0777)){
                $this->io->success("ACL Role: ".ucfirst($name)." has been created within the ".$element['name']." element");
            }
        }
    }
}
