<?php
namespace Jenga\App\Annotations;

use Jenga\App\Core\App;

use DocBlockReader\Reader;
use Jenga\App\Models\Interfaces\SchemaInterface;

/**
 * Reads the annotations written into a schema
 *
 * @author stanley
 */
class Schema {
    
    /**
     * The element schema to be read
     * @var SchemaInterface
     */
    public $schema;
    
    public function __construct(SchemaInterface $elmschema) {
        $this->schema = $elmschema;
    }
    
    /**
     * Initiates the Docreader and reads annotations
     * 
     * @param type $class
     * @param type $methodproperty
     * @param type $type
     * @return DocBlockReader\Reader
     */
    public function read($class, $methodproperty, $type = null){
        
        if(!is_null($type))
            return new Reader ($class, $methodproperty, $type);
        else
            return new Reader ($class, $methodproperty);
    }
}
