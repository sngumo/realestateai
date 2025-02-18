<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Style\SymfonyStyle;

//the Jenga Classes
use Jenga\App\Project\Core\Project;

class ElementsList extends Command{
    
    protected function configure(){
        
        $this
            ->setName('elements:list')
            ->setDescription('Lists the elements used in the current project')
            ->addOption(
                'specify',
                null,
                InputOption::VALUE_OPTIONAL,
                'Enter the element you want to displayed: [--specify [element name]'
            )
            ->addOption(
                'output',
                null,
                InputOption::VALUE_OPTIONAL,
                'Enter the formats you want to display the elements: [--output [basic | properties | raw]]'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        
        $project = new Project();
        $io = new SymfonyStyle($input, $output);
        
        $elements = $project->consoleBuild();    
        
        $specified = $input->getOption('specify');
        
        if(!is_null($specified) 
                && array_key_exists($specified, $elements) 
                && is_null($input->getOption('output'))){
            
            $element = strtolower($specified);
            $attributes = $elements[$element];
            
            $io->newLine();

            $io->title('Element: '.ucfirst($element));
            $titles = [];
            $mvc = 1;

            //process element
            $io->section(ucfirst($element).' - Properties');
            foreach($attributes as $name => $component){
                
                //check for the element properties
                if(is_string($component)){

                    $io->text($name.' = '.($component == '' ? 'none' : $component));
                }
                elseif(is_array($component)){

                    foreach($component as $key => $string){

                        //check folder section
                        if(is_int($key)){

                            if(!in_array('folders', $titles)){

                                $io->section(ucfirst($element).' - MVC Folders');
                                array_push($titles, 'folders');
                            }

                            $io->text( DS.$string );
                        }
                        else{

                            //process the models
                            if($name == 'models'){

                                if(!in_array('models', $titles)){

                                    $io->section(ucfirst($element).' - Models');
                                    array_push($titles, 'models');
                                }

                                $io->text( ['Model Class: '.$key,
                                    'Folder: '.DS.$string['folder'],
                                    'Path: '.'/'.$string['path']] );
                            }
                            elseif($name == 'schema'){

                                if(!in_array('schema', $titles)){

                                    $io->section(ucfirst($element).' - Schema');
                                    array_push($titles, 'schema');
                                }

                                $io->text( ['Schema Class: '.$key,
                                    'Folder: '.DS.$string['folder'],
                                    'Path: '.'/'.$string['path']] );
                            }
                            elseif($name == 'controllers'){

                                if(!in_array('controllers', $titles)){

                                    $io->section(ucfirst($element).' - Controllers');
                                    array_push($titles, 'controllers');
                                }

                                $io->text( ['Controller Class: '.$key,
                                    'Folder: '.DS.$string['folder'],
                                    'Path: '.'/'.$string['path']] );
                            }
                            elseif($name == 'views'){

                                if(!in_array('views', $titles)){

                                    $io->section(ucfirst($element).' - Views');
                                    array_push($titles, 'views');
                                }

                                $io->text( ['View Class: '.$key,
                                    'Folder: '.DS.$string['folder'],
                                    'Path: '.'/'.$string['path']] );
                            }

                            $io->newLine();
                        }
                    }
                }
            }

            exit;
        }
        elseif(!is_null($specified) && !array_key_exists($specified, $elements)){
            
            $io->error('The specified element: '.$specified.' does not exist');
            exit;
        }
        
        $name = $input->getOption('output');        
        
        switch ($name) {
            
            case 'basic':
                
                $table = new Table($output);
                $table->setHeaders(['Elements']);
                
                foreach($elements as $key => $element){
                    
                    if($key != 'templates')
                        $rows[] = [$key];
                }
                
                $table->setRows($rows);
                $table->render();
                
                break;
                
            case 'properties':
                
                $table = new Table($output);
                $table->setHeaders(['Elements','Properties']);
                
                $elementcount = count($elements);
                $elmcount = 0;
                foreach($elements as $key => $element){
                    
                    if($key != 'templates'){
                        
                        //process element
                        $rowcount = 0;
                        foreach($element as $name => $component){

                            //check for the element properties
                            if(is_string($component)){

                                $strings[] = $name.' = '.($component == '' ? 'none' : $component);
                                $rowcount++;
                            }
                        }

                        //creating row
                        $count = 0;
                        $rows = []; 
                        
                        foreach($strings as $string){

                            if($count == 0){
                                $frow = array(new TableCell($key, ['rowspan' => $rowcount]),
                                        $string);
                                
                                array_push($rows, $frow);
                            }
                            else{
                                $row = array($string);
                                array_push($rows, $row);
                            }    
                            
                            $count++;
                        }
                        
                        if($elementcount != $elmcount){
                            array_push($rows, new TableSeparator());
                        }
                        
                        $table->addRows($rows);
                        unset($frow,$row,$rowcount,$strings);
                    }
                    
                    $elmcount++;
                }        
                
                //$table->setStyle('borderless');
                $table->render();
                
                break;
            
            case 'raw' :
                
                if($input->hasOption('specify') && !is_null($input->getOption('specify'))){
                    
                    $specified = $input->getOption('specify');
                    
                    if(!is_null($specified) && array_key_exists($specified, $elements)){
                        
                        $text = print_r($elements[$specified], true);
                    }
                }
                else{
                    
                    $text = print_r($elements, true);
                }
                
                $output->writeln($text);                
                break;
            
            default :
                
                foreach($elements as $element => $attributes){
                    
                    $io->newLine();
                    
                    if($element != 'templates'){
                        
                        $io->title('Element: '.ucfirst($element));
                        $titles = [];
                        $mvc = 1;
                        
                        //process element
                        $io->section(ucfirst($element).' - Properties');
                        foreach($attributes as $name => $component){
                            
                            //check for the element properties
                            if(is_string($component)){

                                $io->text($name.' = '.($component == '' ? 'none' : $component));
                            }
                            elseif(is_array($component)){
                                
                                foreach($component as $key => $string){
                                    
                                    //check folder section
                                    if(is_int($key)){
                                        
                                        if(!in_array('folders', $titles)){
                                            
                                            $io->section(ucfirst($element).' - MVC Folders');
                                            array_push($titles, 'folders');
                                        }
                                        
                                        $io->text( DS.$string );
                                    }
                                    else{
                                        
                                        //process the models
                                        if($name == 'models'){
                                            
                                            if(!in_array('models', $titles)){
                                                
                                                $io->section(ucfirst($element).' - Models');
                                                array_push($titles, 'models');
                                            }
                                            
                                            $io->text( ['Model Class: '.$key,
                                                'Folder: '.DS.$string['folder'],
                                                'Path: '.'/'.$string['path']] );
                                        }
                                        elseif($name == 'schema'){
                                            
                                            if(!in_array('schema', $titles)){
                                                
                                                $io->section(ucfirst($element).' - Schema');
                                                array_push($titles, 'schema');
                                            }
                                            
                                            $io->text( ['Schema Class: '.$key,
                                                'Folder: '.DS.$string['folder'],
                                                'Path: '.'/'.$string['path']] );
                                        }
                                        elseif($name == 'controllers'){
                                            
                                            if(!in_array('controllers', $titles)){
                                                
                                                $io->section(ucfirst($element).' - Controllers');
                                                array_push($titles, 'controllers');
                                            }
                                            
                                            $io->text( ['Controller Class: '.$key,
                                                'Folder: '.DS.$string['folder'],
                                                'Path: '.'/'.$string['path']] );
                                        }
                                        elseif($name == 'views'){
                                            
                                            if(!in_array('views', $titles)){
                                                
                                                $io->section(ucfirst($element).' - Views');
                                                array_push($titles, 'views');
                                            }
                                            
                                            $io->text( ['View Class: '.$key,
                                                'Folder: '.DS.$string['folder'],
                                                'Path: '.'/'.$string['path']] );
                                        }
                                        
                                        $mvc++;
                                    }
                                }
                            }
                        }
                    }
                }      
                
            break;
        }
    }
}

