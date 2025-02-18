<?php
namespace Jenga\MyProject\Users\Models;

use Jenga\App\Models\Utilities\ObjectRelationMapper;
use Jenga\MyProject\Users\Schema\UsersProfileSchema as Schema;

class UsersProfileModel extends ObjectRelationMapper {

    public function __construct(Schema $schema) {
        
        //link to table schema
        $this->schema = $schema;
        
        //link to accesslevel
        $this->hasOne('Navigation/AccessLevelSchema')->alias('acl');
    }
}