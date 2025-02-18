<?php

//app root
define('APP', ROOT .DS. 'app');

//project path
define('PROJECT_PATH', ROOT .DS. 'project');

//public path
define('PUBLIC_PATH', ROOT .DS. 'public');

//load the user defined configurations
if(file_exists( PROJECT_PATH .DS. 'config.php' ))
    require_once PROJECT_PATH .DS. 'config.php';

//configure the project relative url
if(file_exists( SHELL .DS. 'url.environment.php' ))
    require SHELL .DS. 'url.environment.php';

define('RELATIVE_ROOT', $relative_url);
 
if(file_exists(SHELL .DS. 'definitions.php'))
    require_once SHELL .DS. 'definitions.php' ;

//load the class autoload shell file
if(file_exists(SHELL .DS. 'autoload.php'))
    require SHELL .DS. 'autoload.php';

//load the composer  plugin for the system
if(file_exists(ABSOLUTE_PLUGIN_PATH . DS . 'autoload.php'))
    require ABSOLUTE_PLUGIN_PATH .DS. 'autoload.php';


