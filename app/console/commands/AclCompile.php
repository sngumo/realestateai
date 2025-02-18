<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;

use Jenga\App\Core\App;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Security\Acl\ACLCompiler;

/**
 * Builds the project ACL level from the acl\level and acl\alias annotations
 *
 * @author sngumo
 */
class AclCompile extends Command {
    
    protected function configure() {
        
        $this->setName('acl:compile')
                ->setDescription('Builds the project wide ACL levels from the acl\role, acl\level, acl\action and acl\alias annotations')
                ->addOption('outputto', null, InputOption::VALUE_OPTIONAL, 'Options are console or inline')
                ->addOption('element', null, InputOption::VALUE_OPTIONAL, 'Select the element to compile');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output){
        
        $io = new SymfonyStyle($input, $output);
        $acl = App::get(ACLCompiler::class);
        
        //load project configurations
        $project = new Project();
        $config = $project->getConfigs();
        
        //get element option
        $element = strtolower($input->getOption('element'));
        $elements = array_keys($project->elements());
        
        if($element != "" && !in_array($element, $elements)){
            
            $io->error('No element named ['.$element.'] has been registered');
            exit;
        }
        
        //get outputto option
        $outputto = $input->getOption('outputto'); 
        $acls = $acl->run($element);   
        
        //if outputto is NULL then display acls in console
        if(is_null($outputto) || $outputto == 'console'){ 
            
            foreach ($acls as $element => $aclattributes){
                
                $table = new Table($output);
                $table->setHeaders(['Element Name', ucfirst($element)]);
                
                //build acl attributes
                $io->title($config->project.' ACL Settings for '.ucfirst($element));
                
                //check base acl
                if(array_key_exists('base', $aclattributes)){
                    
                    if(array_key_exists('role', $aclattributes['base']))
                        $base = 'Role: '.$aclattributes['base']['role']; 
                    elseif(array_key_exists('level', $aclattributes['base']))
                        $base = 'Level: '.$aclattributes['base']['level'];
                    else
                        $base = 'None Assigned - Level 0 is assumed';
                        
                    $aclattrs[] = ['Base',$base];
                }
                
                //add table separator
                $aclattrs[] = new TableSeparator();
                
                //check set actions
                $actions = (print_r($aclattributes['actions'], TRUE) != '' 
                                    ? str_replace('Array', 'Actions', print_r($aclattributes['actions'], TRUE)) 
                                    : '');  
                
                //check set aliases
                $aliases = (print_r($aclattributes['alias'], TRUE) != '' 
                                    ? str_replace('Array', 'Aliases', print_r($aclattributes['alias'], TRUE)) 
                                    : '');
                
                $actions .= $aliases;
                
                $aclattrs[] = ['Annoted Actions', ($actions == '' ? 'No functions marked as actions' : $actions)];
                
                $table->setRows($aclattrs);
                $table->render();
                
                $io->newLine();
                
                unset($table, $aclattrs);
            }
        }
        elseif($outputto == 'inline'){
            $output->writeln(json_encode($acls));
        }
        else{
            $io->error('Incorrect output: '.$outputto.' Please specify either console or inline');
        }
    }
}
