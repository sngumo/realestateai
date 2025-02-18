<?php
namespace Jenga\App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Style\SymfonyStyle;

use Jenga\App\Core\App;
use Jenga\App\Database\Systems\Pdo\Mysqli\Database;

class CreateDatabase extends Command {
    
    protected function configure() {
        
        $this->setName('create:database')
                ->setDescription('Creates the database for a Jenga project')
                ->addArgument('database_name',  InputArgument::REQUIRED)
                ->addOption('host', null, 
                        InputOption::VALUE_REQUIRED, 
                        'Enter the host name')
                ->addOption('username', null, 
                        InputOption::VALUE_REQUIRED, 
                        'Enter the database username')
                ->addOption('password', null, 
                        InputOption::VALUE_REQUIRED, 
                        'Enter the host name')
                ->addOption('prefix', null, 
                        InputOption::VALUE_OPTIONAL, 
                        'Enter the databse table prefix')
                ->addOption('port', null, 
                        InputOption::VALUE_OPTIONAL, 
                        'Enter the port number');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        
        $io = new SymfonyStyle($input, $output);
        $db = $input->getArgument('database_name');
        
        $host = $input->getOption('host');
        $username = $input->getOption('username');
        $password = $input->getOption('password');
        
        $connect = Database::connectionPing($host, $username, $password);
        $io->write(' [..] Testing connection ..');
        
        if($connect !== FALSE){
            
            $io->write('TRUE', TRUE);
            $create = Database::createDatabase($db, $connect);
            
            if($create){                
                $io->write(' [..] '.$db.' database created ', TRUE);
                return TRUE;
            }
        }
        else{
            $io->write('FALSE', TRUE);
            $io->error(' [..] Database connection failed');
        }
    }
}
