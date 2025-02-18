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
class RequestsSchema implements SchemaInterface {
    
    use Traits\DataManager;
    
    public $table = 'requests';
    
    /****** AUTO-GENERATED COLUMNS ******/
	/**
	* @var ["INT(10)","NOT NULL","AUTO_INCREMENT"]
	* @primary true 
	**/
	public $id;

	/**
	* @var ["text","not null"]
	**/
	public $user_id_ip;

	/**
	* @var ["text","not null"]
	**/
	public $user_type;

	/**
	* @var ["text","null"]
	**/
	public $request_agent;

	/**
	* @var ["text","null"]
	**/
	public $request_url;

	/**
	* @var ["text","null"]
	**/
	public $token;

	/**
	* @var ["int","null"]
	**/
	public $fetch_interval;

	/**
	* @var ["int(10)","not null"]
	**/
	public $created_at;

	/****** END OF AUTO-GENERATED COLUMNS ******/
    
    /**
     * This will be run when seeding table and during migrations
     */
    public function seed() {
        
        $rows = []; 
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
