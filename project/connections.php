<?php
/* 
 * Define the database connections to be used in the project
 */

return [

//    'default' => 'remote',
    'default' => 'live.local',
    'dbal' => 'PDO',
    'connections' => [

        # local database connection
        'local' => [
            'driver'    => 'mysql',
            'persistent' => true,
            'host'      => '127.0.0.1',
            'dbname'  => 'followups_db',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'latin1',
            'prefix'    => 'ups_',
            'port'      => ''
        ],
        
        'live.local' => [
            'driver'    => 'mysql',
            'persistent' => true,
            'host'      => '127.0.0.1',
            'dbname'  => 'real_estate_docs',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'latin1',
            'prefix'    => 'ups_',
            'port'      => ''
        ],

        # beta testing database connection
        'testing' => [
            'driver'    => 'mysql',
            'persistent' => true,
            'host'      => 'localhost',
            'dbname'  => 'nerosolu_real_estate_ai',
            'username'  => 'nerosolu_realuser',
            'password'  => 'harry_b_ai',
            'charset'   => 'utf8',
            'prefix'    => '',
            'port'      => ''
        ],

        # remote database connection
        'remote' => [
            'driver'    => 'mysql',
            'persistent' => true,
            'host'      => 'localhost',
            'dbname'  => 'followup_db_main',
            'username'  => 'followup_mainuse',
            'password'  => 'wedzame04-01-1983',
            'charset'   => 'utf8',
            'prefix'    => 'ups_',
            'port'      => ''
        ]
    ],
];