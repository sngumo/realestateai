<?php
/* 
 * Register all the active db record handler and query builder classes for the available PDO drivers
 */

use Jenga\App\Database\Entities\ActiveRecord;

return [
    'PDO_MYSQL' => ActiveRecord::class
];
