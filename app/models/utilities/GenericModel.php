<?php
namespace Jenga\App\Models\Utilities;

use Jenga\App\Models\Utilities\ObjectRelationMapper;

/**
 * Generic Model class to be used for generic loading of schema
 *
 * @author stanley
 */
class GenericModel extends ObjectRelationMapper {
    
    /**
     * @Inject("__schema")     
     */
    public function __construct($__schema) {
        $this->schema = $__schema;
    }
}
