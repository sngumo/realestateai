<?php
namespace Jenga\MyProject\Users\Schema\Tools;

use Jenga\MyProject\Users\Schema\RequestsSchema;
use Jenga\App\Database\Systems\Pdo\Schema\AnnotationsWriter as Annotate;

/**
 * This is the writer class for the RequestsSchema class file
 *
 * @author stanley
 */
class RequestsSchemaWriter {
    
    /**
     * Use the Annotator to create annotated columns in the RequestsSchema class
     */
    public function write($update){
        
        return Annotate::in(RequestsSchema::class)
                            ->column('id', ['INT(10)','NOT NULL','AUTO_INCREMENT'])
                                ->primary()

                                ->column('user_id_ip',['text','not null'])
                                ->column('user_type',['text','not null'])
                                ->column('request_agent', ['text','null'])
                                ->column('request_url', ['text','null'])
                                ->column('token', ['text','null'])
                                ->column('fetch_interval', ['int','null'])
                                ->column('created_at', ['int(10)','not null'])

                        ->write($update);                 
    }
}
