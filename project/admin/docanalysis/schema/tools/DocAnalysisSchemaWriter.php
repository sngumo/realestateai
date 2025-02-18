<?php
namespace Jenga\MyProject\DocAnalysis\Schema\Tools;

use Jenga\MyProject\DocAnalysis\Schema\DocAnalysisSchema;
use Jenga\App\Database\Systems\Pdo\Schema\AnnotationsWriter as Annotate;

/**
 * This is the writer class for the DocAnalysisSchema class file
 *
 * @author stanley
 */
class DocAnalysisSchemaWriter {
    
    /**
     * Use the Annotator to create annotated columns in the DocAnalysisSchema class
     */
    public function write($update){
        
        return Annotate::in(DocAnalysisSchema::class)
                            ->column('id', ['INT(10)','NOT NULL','AUTO_INCREMENT'])
                                ->primary()
                            ->column('upload_docs_id', ['int','null'])
                                ->foreign('upload_docs', 'id', 'cascade', 'cascade')
                            ->column('doctext', ['text','not null'])
                            ->column('doc_analysis', ['text','null'])

                        ->write($update);                 
    }
}
