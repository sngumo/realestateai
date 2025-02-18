<?php
namespace Jenga\MyProject\Upload\Models;

use Jenga\App\Models\Utilities\ObjectRelationMapper;
use Jenga\MyProject\Upload\Schema\UploadSchema as Schema;

class UploadModel extends ObjectRelationMapper {

    public function __construct(Schema $schema) {
        
        //link to table schema
        $this->schema = $schema;
    }

}