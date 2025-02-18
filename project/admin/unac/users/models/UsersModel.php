<?php
namespace Jenga\MyProject\Users\Models;

use Jenga\App\Models\Utilities\ObjectRelationMapper;

use Jenga\MyProject\Users\Schema\UsersSchema as Schema;
use Jenga\MyProject\Users\Models\UsersProfileModel;

class UsersModel extends ObjectRelationMapper {

    public function __construct(Schema $schema) {
        
        //link to table schema
        $this->schema = $schema;
        
        //link to user profiles
        $this->hasOne('Users/UsersProfileSchema')->alias('profile');
    }
    
    /**
     * Connects UsersProfileModel::class
     * @return UsersProfileModel
     */
    public function linkProfile() {
        return $this->load(UsersProfileModel::class);
    }

    /**
     * Check if sent credentials work
     * @param type $username
     * @param type $password
     * @return boolean
     */
    public function check($username, $password){
        
        //check the user
        $login = $this->where('username', '=', $username)->where('password', '=', md5($password))
                    ->first();

        if ($this->count() == 1){
            return $login;
        }
        else{ 
            return FALSE;
        }
    }
}