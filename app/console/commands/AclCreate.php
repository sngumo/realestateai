<?php

namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Jenga\App\Core\App;
use Jenga\App\Build\Foundation;
use Jenga\App\Project\Security\Acl\AclBuilder;

/**
 * Creates the ACL role 
 *
 * @author Stanley Ngumo
 */
class AclCreate extends Command {
    
    public $io = null;
    public $name;
    public $type = null;
    public $element;
    public $role;
    
    protected function configure() {
        
        $this->setName('acl:create')
                ->setDescription('Creates the ACL role class and saves it in the authorizing element')
                ->addArgument('type',  InputArgument::OPTIONAL, 'Options in the ACL are: gateway or role')
                ->addOption('element', null, InputOption::VALUE_REQUIRED, 
                        'Enter the element to be set as the authorizing element')
                ->addOption('name', null, InputOption::VALUE_REQUIRED, 
                        'Enter the new role name')
                ->addOption('alias', null, InputOption::VALUE_OPTIONAL, 
                        'Enter the role alias')
                ->addOption('level', null, InputOption::VALUE_OPTIONAL, 
                        'Enter the role level')
                ->addOption('description', null, InputOption::VALUE_OPTIONAL, 
                        'Enter the role description');
    }
    
    protected function interact(InputInterface $input, OutputInterface $output) {
        
        $this->io = new SymfonyStyle($input, $output);
        $this->type = strtolower($input->getArgument('type'));
        
        //check ACL type
        if(is_null($this->type) || $this->type == ''){            
            $this->io->error ('Please enter the ACL type to be created');
            exit;
        }
        
        //check gateway
        if($this->type == 'gateway'){
            
            $this->element = $input->getOption('element');
            
            if(is_null($this->element)){            
                $this->io->error ('Please enter the authorizing element using --element');
                exit;
            }
        }
        
        //check role
        if($this->type == 'role'){
            
            $this->role = $input->getOption('name');
            
            if(is_null($this->role)){     
                
                $this->io->error ('Please enter the name of the role class to be created');
                exit;
            }
        }
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        //check input type
        if(!is_null($this->type)){
            App::bind('io', $this->io);
            App::bind('output', $output);
        }
        else{
            $this->io = new SymfonyStyle($input, $output);
            $this->type = strtolower($input->getArgument('type'));
            
            App::bind('io', $this->io);
            App::bind('output', $output);
            
            if($this->type == 'role'){
                $this->role = $input->getOption('name');
            }            
        }
        
        $build = App::get(AclBuilder::class);    
        
        if($this->type == 'gateway'){
            $build->createGateway($this->element);
        }
        elseif($this->type == 'role'){
            
            //get the auth element
            $auth = App::get('auth');
            $auth->setAuthorizationElement();
            
            $auth_element = $auth->getAuthorizationElement();
            
            if(!is_null($auth_element) && $auth_element != ''){
                
                //create the role in the auth element
                $alias = $input->getOption('alias');
                $level = $input->getOption('level');
                $description = $input->getOption('description');
                
                $build->createRole($this->role, $auth_element, $alias, $level, $description);
            }
            else{
                $this->io->error("No authorizing element detected. Please set the ACL Gateway first.");
            }
        }
    }
}
