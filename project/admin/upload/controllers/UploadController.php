<?php
namespace Jenga\MyProject\Upload\Controllers;

use Jenga\App\Controllers\Controller;

use Jenga\App\Request\Input;

use Jenga\MyProject\Upload\Models\UploadModel;
use Jenga\MyProject\Upload\Views\UploadView;

/**
 * Class UploadController
 * 
 * @property-read UploadModel $model
 * @property-read UploadView $view
 * 
 * @package Jenga\MyProject\Upload\Controllers
 */
class UploadController extends Controller{
    
    public function index(){
    }
    
    public function showUploadForm(){
    }
    
    public function bypassCorsError() {
    
        // Allow from any origin
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            
            // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
            // you want to allow, and if so:
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');    // cache for 1 day
        }

        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])){
                // may also be using PUT, PATCH, HEAD etc
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            }

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])){
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }

//            exit(0);
        }
    }
    
    public function uploadDocument(){
        
        $this->bypassCorsError();
        
        $this->view->disable();
        
        //set the upload flag
        $uploadOk = 1;
        
        /* Getting file name */
        $filename = $_FILES['file']['name'];
        
        /* Location */
        $upload_dir = ABSOLUTE_PROJECT_PATH .DS. "storage";
        $location =  $upload_dir .DS. $filename;
        $docFileType = pathinfo($location,PATHINFO_EXTENSION);
        
        /* Valid Extensions */
        $valid_extensions = ['pdf'];
        
        /* Check file extension */
        if( !in_array(strtolower($docFileType),$valid_extensions) ) {
           $uploadOk = 0;
        }
        
        if($uploadOk == 0){
           die(json_encode([
               'status' => 0,
               'message' => 'Invalid File Type'
           ]));
        }else{
            
            if (is_dir($upload_dir) && is_writable($upload_dir)) {
                    
                   /* Upload file */
                   if(move_uploaded_file($_FILES['file']['tmp_name'],$location)){

                       //check if the filename has been saved before
                       $updateflag = 'update';
                       $upload = $this->model
                                                        ->where('filename', $filename)
                                                        ->where('usersprofile_id', Input::post('profile_id'))
                                                    ->first();
                       
                       if(is_null($upload)){
                           
                           $upload = $this->model;
                           $upload->usersprofile_id = Input::post('profile_id');
                           
                           //change the update flag to new
                           $updateflag = 'new';
                       }
                       
                       $upload->asset_type = Input::post('asset_type');
                       $upload->perspective = Input::post('perspective');
                       $upload->filename = $filename;
                       
                       //save the upload details
                       $upload->save();
                       
                       //load the pdf parser
                       $parser = new \Smalot\PdfParser\Parser();
                       $pdf = $parser->parseFile($location);
                       
                       //open doc analysis
                       $docanalysis = $this->call('DocAnalysis');
                       
                       //check flag
                       if($updateflag == 'new'){
                           
                           //get the document id
                           $uploadid = $upload->getLastInsertId();
                           
                           //mysql_insert_id doesnt work
                           if($uploadid == '0'){
                               
                               //get by query
                               $lastfile = $upload->where('filename', $filename)->first();
                               
                               if(!is_null($lastfile)){
                                   $uploadid = $lastfile->id;
                               }
                           }
                           
                           //save the analysed text using doc analysis
                           $doc = $docanalysis->model;
                           
                           //set the upload doc id
                           $doc->upload_docs_id = $uploadid;
                       }
                       else{
                           
                           //find by upload docs id
                           $doc = $docanalysis->model->where('upload_docs_id', $upload->id)->first();
                       }
                       
                       $doc->doctext = nl2br($pdf->getText());
                       $doc->save();
                       
//                       dump($doc->getLastQuery());
//                       dump($doc->getLastError());
                       
                       //save document text and send details
                       if($updateflag == 'new'){
                           
                            $docid = $doc->getLastInsertId();
                           
                               //show success message
                              die(json_encode([
                                    'status' => 1,
                                    'docid' => $docid,
                                    'filename' => $filename
                               ]));
                       }
                       elseif($updateflag == 'update'){
                           
                           die(json_encode([
                                    'status' => 1,
                                    'docid' => $doc->id,
                                    'filename' => $filename
                               ]));
                       }
                       else{
                           
                           die(json_encode([
                                   'status' => 0,
                                   'message' => $doc->getLastQuery()
                               ]));
                       }
                   }else{

                      die(json_encode([
                           'status' => 0
                       ]));
                   }
            }
            else{
                die(json_encode([
                        'status' => 0,
                        'message' => 'Upload folder not writable: '.$upload_dir
                ]));
            }
        }
    }
}
