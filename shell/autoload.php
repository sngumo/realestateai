<?php
/** 
 * Autoload any classes that are required following the PSR-4 standard
 * 
 **/
function autoLoad($className) {
    
    $folders = explode('\\', $className);
    $class = end($folders); 
    
    if(count($folders) > 1){
        
        //create the separator between the framework(App) and the Project (Project)
        if(ucfirst($folders[1]) == 'App'){

            if(count($folders) > '2') {

                unset($folders[0]);
                $class = ucfirst(array_pop($folders));
                $path = join(DS, $folders);

                if(file_exists(ROOT .DS. strtolower($path) .DS. $class.'.php'))
                    require ROOT .DS. strtolower($path) .DS. $class.'.php';
                elseif(file_exists(ROOT .DS. $path .DS. '.class.php'))
                    require ROOT .DS. strtolower($path) .DS. '.class.php';    
            }
            elseif(count($folders) == '2'){

                require APP_CORE .DS. $class . '.php';
            }       

        }
    }
    //the Project autoload code is rendered later    
}

spl_autoload_register('autoLoad');
