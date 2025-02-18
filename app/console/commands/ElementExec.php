<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

//the Jenga Classes
use Jenga\App\Core\App;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Security\Gateway;
use Jenga\App\Project\Elements\XmlElements;

/**
 * This command executes any method in an element
 *
 * @author stanley
 */
class ElementExec extends Command {
    
    public $io;
    
    protected $namespace;
    protected $class;
    
    protected function configure(){
        
        $this->setName('element:exec')
            ->setDescription('Executes any method in an element')
            ->addArgument('element_controller', 
                        InputArgument::REQUIRED, 
                        'Specify the element schema using format: <element>/<controller>')
            ->addOption(
                'method',
                null,
                InputOption::VALUE_REQUIRED,
                'Enter the method to be executed'
            )
            ->addOption(
                'mvc',
                null,
                InputOption::VALUE_OPTIONAL,
                'Enter the mvc component to be loaded either the modelm controller(default) or view'
            )
            ->addOption(
                'args',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Enter any arguments that maybe required in the method'
            );
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $method = $mvc = null;
        $this->io = new SymfonyStyle($input, $output);
        
        //check element and controller
        if(is_null($input->getArgument('element_controller'))){
            $this->io->error('The element must be specified');
            exit;
        }
        
        //get the element controller
        $elm = $input->getArgument('element_controller');
        
        //check mvc
        if(!is_null($input->getOption('mvc'))){
            $mvc = $input->getOption('mvc');
        }
        
        //check method
        if(is_null($input->getOption('method'))){
            $this->io->error('The method for '.$elm.' must also be specified');
            exit;
        }
        else{
            //set the method
            $method = $input->getOption('method');
        }
        
        //initialize the element   
        $element = Project::call($elm, true);
        
        //get all options
        $options = $input->getOptions();
        
        //check for args key in options
        if(array_key_exists('args', $options)){
            
            //get the args
            $argsr = $options['args'][0];
            if(strpos($argsr, ',') !== FALSE){
                $args = explode(',', $argsr);
            }
            else{
                $args[] = $argsr;
            }
        }
        
        /*** hack to create useless user session so that later classes load ***/
        App::bind('gateway', Gateway::class);
        
        //run the element method
        if(is_null($mvc)){
            $response = call_user_func_array([$element, $method], $args);
        }
        else{
            $response = call_user_func_array([$element->{$mvc}, $method], $args);
        }
        
        //check response
        if($response !== FALSE){
            
            $this->io->success($method.'() response: '.$response);
            $this->io->success('The '.$method.'() method on '.$elm.' has been executed');
        }
        else{
            $this->io->error('The '.$method.'() method on '.$elm.' has failed');
        }
    }
}
