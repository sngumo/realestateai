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
class UsersSchema implements SchemaInterface {
    
    use Traits\DataManager;
    
    public $table = 'users';
    
    /****** AUTO-GENERATED COLUMNS ******/
	/**
	* @var ["INT(10)","NOT NULL","AUTO_INCREMENT"]
	* @primary true 
	**/
	public $id;

	/**
	* @var ["varchar(200)","not null"]
	* @unique true 
	**/
	public $username;

	/**
	* @var ["varchar(300)","not null"]
	**/
	public $password;

	/**
	* @var ["int","null"]
	**/
	public $userkey;

	/**
	* @var ["int","null"]
	* @foreign {"usersprofile":{"column":"id","ondelete":"cascade","onupdate":"cascade"}} 
	**/
	public $usersprofile_id;

	/**
	* @var ["text","not null"]
	**/
	public $enabled;

	/**
	* @var ["int","null"]
	**/
	public $last_login;

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
            [
                'username' => 'test@gmail.com',
                'password' => md5('harry_b'),
                'userkey' => random_int(10000, 1000000),
                'enabled' => 'yes',
                'usersprofile_id' => 8
            ],
            [
                'username' => 'sngumo@gmail.com',
                'password' => md5('muchiri'),
                'userkey' => random_int(10000, 1000000),
                'enabled' => 'yes',
                'usersprofile_id' => 7
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
