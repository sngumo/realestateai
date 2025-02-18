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
use Jenga\App\Project\Elements\XmlElements;

class ElementModify extends Command{
    
    protected function configure(){
        
        $this->setName('element:modify')
            ->setDescription('Modifies a property inside an element [acl | disable | visibility]')
            ->addArgument(
                'element',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Enter the name of the Element'
            )
            ->addOption(
                'acl',
                null,
                InputOption::VALUE_OPTIONAL,
                'Enter the acl property of the Element [ --acl [acl level number] ]'
            )
            ->addOption(
                'disable',
                null,
                InputOption::VALUE_OPTIONAL,
                'Enter the property of the Element [ --disable [disable | none] ]'
            )
            ->addOption(
                'visibility',
                null,
                InputOption::VALUE_OPTIONAL,
                'Enter the visibility property of the Element [ --visibility [ public | private ] ]'
            )
            ->addOption(
                'remove',
                null,
                InputOption::VALUE_OPTIONAL,
                'Enter the property you want removed from the Element [ --remove [property] ]'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output){

        //get the Project elements
        $project = new Project();
        $elements = $project::elements();
        
        //get the Symfony styling class
        $io = new SymfonyStyle($input, $output);
        
        //get the specified element
        $sentelements = $input->getArgument('element');
        
        foreach($sentelements as $element){
            
            if(!is_null($element) && array_key_exists($element, $elements)){

                $xml = App::get(XmlElements::class);
                $xml->loadXMLFile('map.xml', PROJECT_PATH);

                $xml->selectXMLElement($element);

                //check for acl option
                if($input->hasOption('acl')){

                    $visvalue = $input->getOption('acl');

                    if(!is_null($visvalue)){
                        $xml->createAttribute('acl',$visvalue);
                        $message = 'ACL level of element: '.$element.' has been changed.';
                    }
                }
                
                //check for visibility option
                if($input->hasOption('visibility')){

                    $visvalue = $input->getOption('visibility');

                    if(!is_null($visvalue)){
                        $xml->createAttribute('visibility',$visvalue);
                        $message = 'Visibility of element: '.$element.' has been changed.';
                    }
                }

                //check for disable option
                if($input->hasOption('disable')){

                    $disvalue = $input->getOption('disable');

                    if(!is_null($disvalue)){
                        $xml->createAttribute('disable',$disvalue);
                        $message = 'Element: '.$element.' has been disabled.';
                    }
                }

                //check the remove option
                if($input->hasOption('remove')){

                    $remvalue = $input->getOption('remove');

                    if(!is_null($remvalue)){
                        $xml->removeAttribute($remvalue);
                        $message = 'The '.$remvalue.' property for element: '.$element.' has been removed.';
                    }
                }

                if($xml->save()){
                    $io->success($message);
                }
            }
            elseif(!is_null($element) && !array_key_exists($element, $elements)){

                $io->error('The specified element: '.$element.' does not exist');
                exit;
            }
        }
    }
}

