<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

use Jenga\App\Core\App;
use Jenga\App\Build\Foundation;

class Create extends Command {
    
    public $initfiles;
    public $io;
    public $project;
    
    protected function configure() {
        
        $this->setName('create')
                ->setDescription('Creates the initial required files and database for a Jenga project')
                ->addArgument('project_name', 
                        InputArgument::REQUIRED, 
                        'Specify the project name');
    }
    
    protected function interact(InputInterface $input, OutputInterface $output) {
        
        $this->io = new SymfonyStyle($input, $output);
        $this->project = $input->getArgument('project_name');
        
        if(is_null($this->project)){
            
            $this->io->error ('Please enter the project name. Please put it in double quotes e.g. "Jenga Framework"');
            exit;
        }
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        App::bind('io', $this->io);
        App::bind('output', $output);

        //load the core files
        $this->io->newLine();
        
        $this->io->title('Creating '.$this->project.' project');
        $this->io->title('Step 1/2: Create and load the core files');
        
        $foundation = App::get(Foundation::class);    
        
        if($foundation->check()){
            
            $this->io->warning('Jenga core files detected. Continuing with creation will overwrite the existing files');
            
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with creating the project? [ y / n ] ', false);
            
            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }
        
        //load the inital project files
        $this->initfiles = $foundation->loadProjectInitializationFiles($this->project);
        
        //ask for the database details
        $this->io->title('Step 2/2: Create the project database');
        
        $helper = $this->getHelper('question');
        
        //get the database name
        $dbquestion = new Question('  Enter the new database name: ', '');
        $dbquestion->setValidator(function ($answer) {
            if ($answer == '') {
                throw new \RuntimeException(
                    'The database name must be provided'
                );
            }

            return $answer;
        });

        $db['name'] = $helper->ask($input, $output, $dbquestion);
        $this->io->newLine();
        
        //get the mysqli host
        $host_question = new Question('  Enter the hostname: (For most local mysqli, its localhost) ', 'localhost');
        $host_question->setValidator(function ($answer) {
            if (!is_string($answer)) {
                throw new \RuntimeException(
                    'The answer must be a string'
                );
            }

            return $answer;
        });

        $db['host'] = $helper->ask($input, $output, $host_question);
        $this->io->newLine();
        
        //get the mysqli username
        $username_question = new Question('  Enter the database username: (For most local mysqli, its usually root) ', 'root');
        $username_question->setValidator(function ($answer) {
            if ($answer === '') {
                throw new \RuntimeException(
                    'The database username must be provided'
                );
            }

            return $answer;
        });

        $db['username'] = $helper->ask($input, $output, $username_question);
        $this->io->newLine();
        
        //get the mysqli password
        $password_question = new Question('  Enter the database password: (For most local mysqli, its usually blank) ', '');
        $password_question->setHidden(true);
        $password_question->setHiddenFallback(false);

        $db['password'] = $helper->ask($input, $output, $password_question);
        $this->io->newLine();        
        
        //get the mysqli port
        $port_question = new Question('  Enter the database port: (Leave blank for default mysqli port to be used) ', '');
        $port = $helper->ask($input, $output, $port_question);
        
        if($port == '')
            $db['port'] = ini_get('mysqli.default.port');
        else
            $db['port'] = $port;
        
        $this->io->newLine();
        
        //get the mysqli port
        $prefix_question = new Question('  Enter the database table prefix: ', '');
        $db['prefix'] = $helper->ask($input, $output, $prefix_question);
        $this->io->newLine();
        
        $command = $this->getApplication()->find('create:database');
        $arguments = [
            'command' => 'create:database',
            'database_name' => $db['name'],
            '--host' => $db['host'],
            '--username' => $db['username'],
            '--password' => $db['password'],
            '--prefix' => $db['prefix'],
            '--port' => $db['port']
        ];
        
        $arginput = new ArrayInput($arguments);
        $result = $command->run($arginput, $output);
        
        if($result == 0){
            
            $config = $foundation->writeDbSettings($db);
            
            if($config){
                
                $this->io->newLine (2);
                $this->io->title($this->project.' has been successfully created');
            }
            else{
                $this->io->error($this->project.' creation has failed');
            }
        }
    }
}
