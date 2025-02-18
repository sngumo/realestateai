<?php

//the admin template behind the login wall
use Jenga\App\Project\Routing\Route;

Route::any('/user/ac/delete', 'UsersController@verifyDeleteUserAccount')
        ->assignPanels(['_ajax' => true]);

Route::any('/user/delete', 'UsersController@showCustomerDeleteForm')
        ->assignPanels(['_ajax' => true]);

Route::post('/mobile/session/renew', 'UsersController@renewSession')
        ->assignPanels(['_ajax' => true]);

Route::get('/mobile/request/token', 'UsersController@requestValidationToken')
        ->assignPanels(['_ajax' => true]);

Route::post('/mobile/guest', 'UsersController@createGuestUser')
        ->assignPanels(['_ajax' => true]);

Route::any('/login/cookie', 'UsersController@getUserCookieData')
        ->attachTemplate('backend/pages/login.php');

Route::any('/login/recover', 'UsersController@recoverLogin')
        ->attachTemplate('backend/pages/login.php');

Route::get('/inline/login/{username}/{password}', 'UsersController@inlineLogin')
        ->assignPanels(['_ajax' => true]);

Route::get('/inline/loginbykey/{username}/{userkey}', 'UsersController@inlineLoginViaRememberKey')
        ->assignPanels(['_ajax' => true]);

Route::get('/inline/setcookie/{username}/{password}', 'UsersController@setUserCookie')
        ->assignPanels(['_ajax' => true]);

Route::post('/mobile/customer/reset', 'UsersController@sendResetCode')
        ->assignPanels(['_ajax' => true]);

Route::post('/mobile/customer/confirmpassword', 'UsersController@saveNewPassword')
        ->assignPanels(['_ajax' => true]);

Route::post('/mobile/customer/confirmreset', 'UsersController@confirmResetCode')
        ->assignPanels(['_ajax' => true]);

Route::get('/mobile/logout/{key}', 'UsersController@logoutByUserkey')
        ->assignPanels(['_ajax' => true]);

Route::post('/mobile/login/user', 'UsersController@loginMobileUser')
        ->assignPanels(['_ajax' => true]);

Route::any('/login/user', 'UsersController@loginUser')
        ->assignPanels(['_ajax' => true]);

Route::any('/login', 'UsersController@showLogin')
        ->attachTemplate('backend/pages/login.php');

Route::any('/customer/login', 'UsersController@showCustomerLogin')
        ->assignPanels(['_ajax' => true]);

Route::any('/customer/recover', 'UsersController@recoverCustomerLogin')
        ->attachTemplate('backend/pages/login.php');

Route::any('/login/senduserdetails', 'UsersController@sendUserDetails')
        ->assignPanels(['_ajax' => true]);

Route::any('/login/reset/{token}', 'UsersController@resetUserLogin')
        ->attachTemplate('backend/pages/login.php');

//create auth firewall
Route::group(['before' => 'auth.check'], function () {

    //user logout
    Route::get('/user/logout/{sessid}', 'UsersController@logout:logout');
    
    //user delete account
    Route::any('/users/deleteac/{userkey}', 'UsersController@deleteUserAccount');
    
    //system policies routes
    Route::any('/settings/users/policies/delete/{alias}', 'UsersController@deletePolicy')
                ->assignPanels(['_ajax' => true]);

    Route::post('/settings/users/policies/create', 'UsersController@createPolicy')
                ->assignPanels(['_ajax' => true]);

    Route::get('/settings/users/policies/new', 'UsersController@addPolicy')
                ->assignPanels(['_ajax' => true]);

    Route::any('/settings/users/policies/save/{alias}', 'UsersController@savePolicy')
            ->assignPanels(['_ajax' => true]);

    //get navigation paths
    Route::get('/settings/users/policies/edit/{alias}', 'UsersController@editPolicy')
            ->pinToDashboard()
            ->assignResources([
                '<script src="'. RELATIVE_ROOT . '/public/resources/codemirror/lib/codemirror.js"></script>',
                '<link rel="stylesheet" href="'. RELATIVE_ROOT . '/public/resources/codemirror/lib/codemirror.css">',
                '<script src="'. RELATIVE_ROOT . '/public/resources/codemirror/addon/edit/matchbrackets.js"></script>',
                '<script src="'. RELATIVE_ROOT . '/public/resources/codemirror/mode/htmlmixed/htmlmixed.js"></script>',
                '<script src="'. RELATIVE_ROOT . '/public/resources/codemirror/mode/xml/xml.js"></script>',
                '<script src="'. RELATIVE_ROOT . '/public/resources/codemirror/mode/javascript/javascript.js"></script>',
                '<script src="'. RELATIVE_ROOT . '/public/resources/codemirror/mode/css/css.js"></script>',
                '<script src="'. RELATIVE_ROOT . '/public/resources/codemirror/mode/clike/clike.js"></script>',
                '<script src="'. RELATIVE_ROOT . '/public/resources/codemirror/mode/php/php.js"></script>',
                '<script type="text/javascript">'
                . 'var editor = CodeMirror.fromTextArea(document.getElementById("editor"), {
                        lineNumbers: true,
                        matchBrackets: true,
                        mode: "application/x-httpd-php",
                        indentUnit: 4,
                        indentWithTabs: true
                    });'
                . '</script>'
            ])->toBody();

    Route::get('/settings/users/policies/show', 'UsersController@showSystemPolicies')
            ->pinToDashboard();
    
    //users settings
    Route::any('/settings/users/configure/status/{section}/{id}/{state}', 'UsersController@setStatus')
            ->assignPanels(['_ajax' => TRUE]);
    
    Route::any('/settings/users/configure/save/{load}/{id}', 'UsersController@save')
            ->assignPanels(['_ajax' => TRUE]);

    Route::get('/settings/users/configure/{load}/{id}', 'UsersController@load')
            ->assignPanels(['_ajax' => TRUE]);
    
    Route::get('/settings/users/addedit/{id}', 'UsersController@addEditUser')
            ->assignPanels(['_ajax' => TRUE]);

    Route::get('/settings/users/show', 'UsersController@show')
            ->pinToDashboard();
    
    Route::any('/settings/users/{action}/{id}', 'UsersController@index')
            ->pinToDashboard();
});
