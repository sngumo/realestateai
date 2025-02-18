<?php
namespace Jenga\MyProject\DocAnalysis\Controllers;

use Jenga\App\Controllers\Controller;
use Jenga\MyProject\DocAnalysis\Traits;

use Jenga\MyProject\DocAnalysis\Models\DocAnalysisModel;
use Jenga\MyProject\DocAnalysis\Views\DocAnalysisView;

/**
 * Class DocAnalysisController
 * 
 * @property-read DocAnalysisModel $model
 * @property-read DocAnalysisView $view
 * 
 * @package Jenga\MyProject\DocAnalysis\Controllers
 */
class DocAnalysisController extends Controller{
    
    public $rules = [
        'BASE RENT' => [
            'RENT', 'The rent to be paid.'
        ],
        'PREMISES' => [
            'RENTABLE SPACE', 'The total rentable space','premises'
        ],
        'USE' => [
            'The Lessee shall be allowed to use'
        ],
        'LEASE TERM' => [
            'lease term','The term of the lease shall'
        ],
        'OPERATING EXPENSES' => [
            'expenses', 'costs', 'the tenant must pay'
        ],
        'SECURITY DEPOSIT' => [
            'security deposit', 'deposit'
        ],
        'TENANT IMPROVEMENTS' => [
            'tenant improvements', 'tenant', 'improvements'
        ],
        'ASSIGNMENT SUBLETTING' => [
            'subletting', 'assignment'
        ]
    ];
    
    public function index(){
    }
    
    public function showAnalysis(){
        
    }
    
    public function analyzeDocument($docid){
        
        $this->view->disable();
        
        //get the document text
        $document = $this->model->find($docid);
        
        //analyze the text
        $analysis = $this->analyzeText($document->doctext);
        
        //check analysis
        if(count($analysis) > 0){
            
            //save the analysis
            $document->doc_analysis = json_encode($analysis);
            $document->save();
            
            //analysis successfull
            die(json_encode([
                'status' => 1,
                'docid' => $docid,
                'analysis' => $analysis
            ]));
        }
        else{
            
            die(json_encode([
                'status' => 0,
                'message' => 'Analysis Failed'
            ]));
        }
    }
    
    public function analyzeText($text) {
        
        $results = [];
        
        foreach ($this->rules as $key => $terms) {
            $found = false;
            $positions = [];

            foreach ($terms as $term) {
                
                $position = stripos($text, $term); // Case-insensitive search
                
                if ($position !== false) {
                    $found = true;
                    $positions[] = [
                        'term' => ucwords(strtolower($term)),
                        'position' => $position
                    ];
                }
            }

            $uckey = ucwords(strtolower($key));
            
            if ($found) {
                $results[$uckey] = [
                    'status' => 'PRESENT',
                    'positions' => $positions
                ];
            } else {
                $results[$uckey] = [
                    'status' => 'NOT PRESENT',
                    'positions' => []
                ];
            }
        }
        
        return $results;
    }
    
    public function getDocumentText($doc){
        
        $this->view->disable();
        
        //get the document text
        $doctext = $this->model->find($doc);
        
        //display if found
        if(!is_null($doctext)){
            
            die(json_encode([
                'status' => 1,
                'filename' => $doctext->upload->filename,
                'text' => $doctext->doctext
            ]));
        }
        else{
            
            die(json_encode([
                'status' => 0,
                'message' => 'Error: Document not found'
            ]));
        }
    }
    
    public function retrieveDocumentRules(){
        $this->view->disable();
        
        //return the set rules for searching the document
        die(json_encode($this->rules));
    }
}
