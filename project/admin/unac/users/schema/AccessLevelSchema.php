<?php
namespace Jenga\MyProject\Users\Schema;

use Jenga\App\Models\Traits;
use Jenga\App\Models\Interfaces\SchemaInterface;

use Jenga\App\Database\Systems\Pdo\Schema\Tasks;

/**
 * 
 * This class serves as the primary representation of the db table within the project.
 * Each column within the table will march the class properties created here
 * 
 */
class AccessLevelSchema implements SchemaInterface {
    
    use Traits\DataManager;
    
    public $table = 'accesslevels';
    
    /****** AUTO-GENERATED COLUMNS ******/
	/**
	* @var ["INT(10)","NOT NULL","AUTO_INCREMENT"]
	* @primary true 
	**/
	public $id;

	/**
	* @var ["varchar(100)","not null"]
	**/
	public $name;

	/**
	* @var ["varchar(100)","not null"]
	* @unique true 
	**/
	public $role;

	/**
	* @var ["text","null"]
	**/
	public $description;

	/**
	* @var ["int(10)","not null"]
	**/
	public $level;

	/**
	* @var ["text","null"]
	**/
	public $permissions;


	/****** END OF AUTO-GENERATED COLUMNS ******/
    
    /**
     * This will be run when seeding table and during migrations
     */
    public function seed() {
        
         
        $rows = [
            ['name' => 'Guest',
                'role' => 'guest',
                'description' => 'The entry level for every user',
                'level' => 0],
            ['name' => 'Tenant',
                'role' => 'tenant',
                'description' => 'The entry level for every tenant for each agent',
                'level' => 1],
            ['name' => 'Agent',
                'role' => 'agent',
                'description' => 'The entry level for an agent',
                'level' => 2],
            ['name' => 'System Administrator',
                'role' => 'sysadmin',
                'description' => 'The entry level for a system administrator',
                'level' => 5]
        ]; 
        return Tasks::seed($rows);
    }
    
    /**
     * This will be run when exporting during migrations
     */
    public function export() {
        return TRUE;
    }

    /**
    * This is for running advanced operations on the table
    */
    public function run() {
        return TRUE;
    }
}
