<?php
namespace Jenga\MyProject\DocAnalysis\Models;

use Jenga\App\Models\Utilities\ObjectRelationMapper;
use Jenga\MyProject\DocAnalysis\Schema\DocAnalysisSchema as Schema;

class DocAnalysisModel extends ObjectRelationMapper {

    public function __construct(Schema $schema) {
        
        //link to table schema
        $this->schema = $schema;
        
        //link to uploaded document
        $this->hasOne('Upload/UploadSchema')->alias('upload');
    }

}