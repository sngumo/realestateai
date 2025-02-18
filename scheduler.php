<?php require_once __DIR__.'/plugins/autoload.php';

use GO\Scheduler;

// Create a new scheduler
$scheduler = new Scheduler();

//set cron job to run every day at 06.00
$scheduler->php(__DIR__.'/jenga', PHP_BINARY,[
    'element:exec',
    'Reminders/RemindersController',
    '--method' => 'execCron'
])->daily("06:00");

// Let the scheduler execute jobs which are due.
$scheduler->run();
var_dump(__DIR__.'/jenga');
