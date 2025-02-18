<?php
namespace Jenga\MyProject\Users\Schema\Tools;

use Jenga\MyProject\Users\Schema\UsersProfileSchema;
use Jenga\App\Database\Systems\Pdo\Schema\AnnotationsWriter as Annotate;

/**
 * This is the writer class for the UserProfileSchema class file
 *
 * @author stanley
 */
class UsersProfileSchemaWriter {
    
    /**
     * Use the Annotator to create annotated columns in the UsersProfileSchema class
     */
    public function write($update){
        
        return Annotate::in(UsersProfileSchema::class)
                            ->column('id', ['INT(10)','NOT NULL','AUTO_INCREMENT'])
                                ->primary()
                            ->column('accesslevels_id', ['int(10)','not null'])
                                ->foreign('accesslevels', 'id', 'cascade', 'cascade')
                            ->column('name',['text','not null'])
                            ->column('mobile_no',['text','null'])
                            ->column('email', ['varchar(100)','null'])
                            ->column('address', ['varchar(100)','null'])
                            ->column('location', ['text','null'])
                            ->column('verified', ['text','null'])
                            ->column('created_at', ['int(10)','not null'])
                        ->write($update);                 
    }
}
