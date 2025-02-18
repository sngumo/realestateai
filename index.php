<?php 
define('ROOT', __DIR__);
define('DS', DIRECTORY_SEPARATOR);
define('SHELL', ROOT .DS. 'shell');

//load the necessary files for the environment
require_once SHELL .DS. 'environment.php';

//initialize the Application Shell
$app = new Jenga\App\Core\App ( 
            PROJECT_PATH .DS. 'config.php',
            PROJECT_PATH .DS. 'routes.php',
            PROJECT_PATH .DS. 'events.php'
        );
   
if($app->mode != 'startup'){
    
    $response = $app->handle($app->request);

    if(!is_null($response)){

        $response->send();
        $app->terminate($app->request, $response);
    }
}
else if($app->mode == 'startup'){
    $app->project->startPage();
}
