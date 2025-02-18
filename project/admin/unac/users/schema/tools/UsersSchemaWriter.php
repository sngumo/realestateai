<?php
namespace Jenga\MyProject\Users\Schema\Tools;

use Jenga\MyProject\Users\Schema\UsersSchema;
use Jenga\App\Database\Systems\Pdo\Schema\AnnotationsWriter as Annotate;

/**
 * This is the writer class for the UsersSchema class file
 *
 * @author stanley
 */
class UsersSchemaWriter {
    
    /**
     * Use the Annotator to create annotated columns in the UsersSchema class
     */
    public function write($update){
        
        return Annotate::in(UsersSchema::class)
                            ->column('id', ['INT(10)','NOT NULL','AUTO_INCREMENT'])
                                ->primary()
                            ->column('username', ['varchar(200)','not null'])
                                ->unique()
                            ->column('password', ['varchar(300)','not null'])
                            ->column('userkey', ['int','null'])
                            ->column('usersprofile_id', ['int','null'])
                                ->foreign('usersprofile', 'id', 'cascade', 'cascade')
                            ->column('enabled', ['text','not null'])
                            ->column('last_login', ['int','null'])
                            ->column('permissions', ['text','null'])
                            
                        ->write($update);                 
    }
}
