<?php
namespace Jenga\MyProject\Users\Schema\Tools;

use Jenga\MyProject\Users\Schema\AccessLevelSchema;
use Jenga\App\Database\Systems\Pdo\Schema\AnnotationsWriter as Annotate;

/**
 * This is the writer class for the AccessLevelSchema class file
 *
 * @author stanley
 */
class AccessLevelSchemaWriter {
    
    /**
     * Use the Annotator to create annotated columns in the AccessLevelSchema class
     */
    public function write($update){
        
        return Annotate::in(AccessLevelSchema::class)
                            ->column('id', ['INT(10)','NOT NULL','AUTO_INCREMENT'])
                                ->primary()
                         ->column('id', ['INT(10)','NOT NULL','AUTO_INCREMENT'])
                                ->primary()
                            ->column('name', ['varchar(100)', 'not null'])
                            ->column('role', ['varchar(100)', 'not null'])
                                ->unique()  
                            ->column('description', ['text', 'null'])
                            ->column('level', ['int(10)','not null'])
                            ->column('permissions', ['text','null'])
                        ->write($update);                 
    }
}
