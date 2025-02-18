<?php
namespace Jenga\MyProject\Upload\Schema;

use Jenga\App\Models\Traits;
use Jenga\App\Models\Interfaces\SchemaInterface;

use Jenga\App\Database\Systems\Pdo\Schema\Tasks;

/**
 * 
 * This class serves as the primary representation of the db table within the project.
 * Each column within the table will march the class properties created here
 * 
 */
class UploadSchema implements SchemaInterface {
    
    use Traits\DataManager;
    
    public $table = 'upload_docs';
    
    /****** AUTO-GENERATED COLUMNS ******/
	/**
	* @var ["INT(10)","NOT NULL","AUTO_INCREMENT"]
	* @primary true 
	**/
	public $id;

	/**
	* @var ["int","null"]
	* @foreign {"usersprofile":{"column":"id","ondelete":"cascade","onupdate":"cascade"}} 
	**/
	public $usersprofile_id;

	/**
	* @var ["text","not null"]
	**/
	public $filename;

	/**
	* @var ["text","not null"]
	**/
	public $asset_type;

	/**
	* @var ["text","not null"]
	**/
	public $perspective;

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
