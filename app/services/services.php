<?php
use Jenga\App\Core\Ajax;
use Jenga\App\Core\Cache;
use Jenga\MyProject\Users\Acl\Gateway;

/**
 * Register all the Application and Project services here
 */
return [
    'handlers' => [
        'cache' => [
                'class' => Cache::class,
                'mode' => 'lazy'
            ],
        'auth' => [
                'class' => Gateway::class,
                'mode' => 'lazy',
                'auth_source' => 'file', // or file - This configuration determines if the system access levels and policies are stored in a database or a flat file
            ]
    ]
];