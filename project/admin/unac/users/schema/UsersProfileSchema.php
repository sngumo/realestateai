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
class UsersProfileSchema implements SchemaInterface {
    
    use Traits\DataManager;
    
    public $table = 'usersprofile';
    
    /****** AUTO-GENERATED COLUMNS ******/
	/**
	* @var ["INT(10)","NOT NULL","AUTO_INCREMENT"]
	* @primary true 
	**/
	public $id;

	/**
	* @var ["int(10)","not null"]
	* @foreign {"accesslevels":{"column":"id","ondelete":"cascade","onupdate":"cascade"}} 
	**/
	public $accesslevels_id;

	/**
	* @var ["text","not null"]
	**/
	public $name;

	/**
	* @var ["text","null"]
	**/
	public $mobile_no;

	/**
	* @var ["varchar(100)","null"]
	**/
	public $email;

	/**
	* @var ["varchar(100)","null"]
	**/
	public $address;

	/**
	* @var ["text","null"]
	**/
	public $location;

	/**
	* @var ["text","null"]
	**/
	public $verified;

	/**
	* @var ["int(10)","not null"]
	**/
	public $created_at;

	/****** END OF AUTO-GENERATED COLUMNS ******/

    /**
     * This will be run when seeding table and during migrations
     */
    public function seed() {
        
        $rows = [
            [
                'accesslevels_id' => 2,
                'address' => '00100',
                'email' => 'sngumo@gmail.com',
                'location' => 'Kenya',
                'mobile_no' => '0722958720',
                'name' => 'Stanley Ngumo',
                'registered_date' => time()
            ],
            [
                'accesslevels_id' => 2,
                'address' => '00100',
                'email' => 'test@gmail.com',
                'location' => 'US',
                'mobile_no' => '1234567890',
                'name' => 'Harry B',
                'registered_date' => time()
            ]
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
