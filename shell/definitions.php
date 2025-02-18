<?php

//absolute paths
define('ABSOLUTE_PATH', ROOT);
define('ABSOLUTE_APP_PATH', ROOT . DS . 'app');
define('ABSOLUTE_PLUGIN_PATH', ROOT . DS . 'plugins');
define('ABSOLUTE_PROJECT_PATH', ROOT . DS . 'project');
define('ABSOLUTE_PUBLIC_PATH', ROOT . DS . 'public');
define('CACHE_PATH', ROOT .DS. 'tmp' .DS. 'cache');

define('MODELS', APP .DS. 'models');
define('CONTROLLERS', APP .DS. 'controllers');
define('VIEWS', APP .DS. 'views');

define('APP_CORE', APP .DS. 'core');
define('HELPERS', APP .DS. 'helpers');
define('DATABASE', APP .DS. 'database');
define('SERVICES', APP .DS. 'services');
define('APP_PROJECT', APP .DS. 'project');
define('APP_HTML', APP .DS. 'html');
define('APP_PROJECT_CORE', APP .DS. 'project' .DS. 'core');
define('APP_BOOTSTRAP', APP .DS. 'bootstrap');

//relative paths to main sections
define('SITE_PATH', RELATIVE_ROOT);
define('PLUGIN_PATH', RELATIVE_ROOT . DS . 'plugins');
define('RELATIVE_APP_PATH', RELATIVE_ROOT . '/app');
define('RELATIVE_PROJECT_PATH', RELATIVE_ROOT . '/project');
define('RELATIVE_PUBLIC_PATH', RELATIVE_ROOT . '/public');
define('RELATIVE_APP_PROJECT', RELATIVE_ROOT . '/app/project');
define('RELATIVE_APP_HTML_PATH', RELATIVE_ROOT . '/app/html');
define('RELATIVE_VIEWS', RELATIVE_ROOT . '/app/views');
define('RESOURCES', RELATIVE_ROOT . '/app/resources');
