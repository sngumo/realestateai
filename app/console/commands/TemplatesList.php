<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

//the Jenga Classes
use Jenga\App\Project\Core\Project;

class TemplatesList extends Command{
    
    protected function configure(){
        
        $this
            ->setName('templates:list')
            ->setDescription('Lists the templates used in the current project');
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        
        $project = new Project();
        $templates = $project::elements()['templates'];
        
        $table = new Table($output);
        
        //General Template Terms
        $table->setHeaders(['Name','Path','Scope']);
        
        $table->addRow([$templates['name'],'/'.$templates['path'],$templates['scope']]);
        
        $io = new SymfonyStyle($input, $output);
        
        $io->newLine();
        $io->section('General Template Properties');
        
        $table->render();
        
        $table = new Table($output);
        $table->setHeaders(['Folder Name','Linked to folder in project','Default']);
        
        $folders = $templates['folders'];
        
        $count = 0;
        foreach($folders as $folder){
            
            $default = (in_array($folder,$templates['primary']) ? 'true' : 'false');
            $row = ['/'.$folder, '/'.$templates['attachto'][$count],$default];
            
            $table->addRow($row);
            $count++;
        }
        
        $io->newLine();
        $io->section('Specific Template Properties');
        
        $table->render();
    }
}

