<?php
/**
 * This is where the user can specify the specific routing for his/her elements
 */

use Jenga\App\Project\Routing\Route;

//admin page
Route::page('dashboard', [
    'upload' => 'UploadController@showUploadForm:upload',
    'doc-analysis' => 'DocAnalysisController@showAnalysis:docanalysis'
],[]);

//collect the User element routes
//Route::collect('Users');

Route::any('/userlogin', 'UsersController@loginUser')
        ->assignPanels(['_ajax' => true]);

//create auth firewall
Route::group(['before' => 'auth.check'], function (){

//        Route::any('/analyze', 'DocAnalysisController@showAnalysis');
//        Route::any('/upload', 'UploadController@showUploadForm');    
        
});

//start upload
Route::any('/startupload', 'UploadController@uploadDocument')
        ->assignPanels(['_ajax' => true]);

//get document text
Route::any('/gettext/{doc}', 'DocAnalysisController@getDocumentText');

//start analysis
Route::any('/analyze/{docid}', 'DocAnalysisController@analyzeDocument');

//return the set rules
Route::any('/rules', 'DocAnalysisController@retrieveDocumentRules');

//default route
//Route::any('/{id}', 'UsersController@showCustomerLogin')
//        ->pinToDashboard();

Route::any('/{id}', 'UsersController@showCustomerLogin')
        ->assignPanels(['_ajax' => true]);
