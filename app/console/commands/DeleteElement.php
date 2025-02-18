<?php
namespace Jenga\App\Console\Commands;

/**
 * This command deletes an element from the Jenga project
 *
 * @author sngumo
 */
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Jenga\App\Core\App;
use Jenga\App\Core\File;
use Jenga\App\Project\Core\Project;
use Jenga\App\Project\Elements\XmlElements;

class DeleteElement extends Command {
    
    protected function configure() {
        
        $this->setName('delete:element')
                ->setDescription('Deletes an element from the Jenga project')
                ->addArgument('element_name',  InputArgument::REQUIRED);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $element = $input->getArgument('element_name');
        $io = new SymfonyStyle($input, $output);
        
        //create new line
        $io->newLine();
        
        //confirm
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('This action will delete your element and its related tables. Do you want to continue? [ y / n ] ', false);

        if (!$helper->ask($input, $output, $question)) {
            return;
        }
        
        $xmldoc = App::get(XmlElements::class);
        $file = App::get(File::class);
        
        if($xmldoc->loadXMLFile('map.xml', PROJECT_PATH)){
            
            if($xmldoc->selectXMLElement($element)){
                
                $elementpath = Project::elements()[$element]['path'];
                
                //process element oath
                if(strstr($elementpath,'/') !== FALSE){
                    $elementpath = str_replace('/', DS, $elementpath);
                }
                
                //delete the schemas
                $elm = Project::elements()[$element];
                $schemas = array_keys($elm['schema']);
                
                //get the db:schema:drop command
                $command = $this->getApplication()->find('db:schema:drop');
                foreach ($schemas as $schema) {
                    
                    $arguments = array(
                        'command' => 'db:schema:drop',
                        'element_schema' => ucfirst($element) .'/'. ucfirst($schema)
                    );

                    $cmdInput = new ArrayInput($arguments);
                    $command->run($cmdInput, $output);
                }
                
                //remove the actual folder
                if($file->deleteFolder(ABSOLUTE_PROJECT_PATH .DS. $elementpath, TRUE)){
                    
                    //delete the related panels folder                    
                    $public_path = ABSOLUTE_PUBLIC_PATH .DS. 'panels' .DS. $element;
                    if(is_dir($public_path)){
                        
                        $file->deleteFolder($public_path, TRUE);
                        $io->success($element.' folder was deleted from the Jenga public folder');
                    }
                    else{
                        $io->error($element.' folder was not deleted in the Jenga public folder: '.$public_path);
                    }
                    
                    //remove the XML node in maps
                    $xmldoc->deleteXMLElement();            
                    
                    //save xml
                    $xmldoc->save();
                    
                    //remove the element and recompile the elements file
                    $elements = unserialize($file->get(APP_PROJECT .DS. 'elements.php'));  
                    unset($elements[$element]);
                    
                    //resave the elements file
                    if($file->put(APP_PROJECT .DS. 'elements.php' ,serialize($elements))){
                    
                        //set success notice
                        $io->success($element.' has been deleted.');    
                    
                        //add route note
                        $io->success('Please manually remove the '.$element.' routes from the Routes file');   
                    }
                }
                else{
                    $io->error($element.' folder was not deleted in the Jenga project folder');
                }
            }
            else{
                $io->error($element.' has not been found in your Jenga Project');
            }
        }
    }
}
