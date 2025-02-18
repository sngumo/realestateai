<?php
namespace Jenga\MyProject\Upload\Schema\Tools;

use Jenga\MyProject\Upload\Schema\UploadSchema;
use Jenga\App\Database\Systems\Pdo\Schema\AnnotationsWriter as Annotate;

/**
 * This is the writer class for the UploadSchema class file
 *
 * @author stanley
 */
class UploadSchemaWriter {
    
    /**
     * Use the Annotator to create annotated columns in the UploadSchema class
     */
    public function write($update){
        
        return Annotate::in(UploadSchema::class)
                            ->column('id', ['INT(10)','NOT NULL','AUTO_INCREMENT'])
                                ->primary()
                            ->column('usersprofile_id', ['int','null'])
                                ->foreign('usersprofile', 'id', 'cascade', 'cascade')
                            ->column('filename', ['text','not null'])
                            ->column('asset_type', ['text','not null'])
                            ->column('perspective', ['text','not null'])
                        ->write($update);                 
    }
}
