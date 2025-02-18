<?php
use Jenga\App\Request\Url;

header('Access-Control-Allow-Origin: *'); 
?>
<div class="login-form frontend p-t-0">   
    <div class="top m-b-30 p-t-20">
        <h2 style="font-weight: bold;">Real Estate Document AI</h2>
        <h5 style="line-height: 1.3">Your AI-powered assistant for analyzing real estate documents.
            Upload your letters of intent and term sheets to automatically extract key business terms.</h5>
    </div>        
    <div class="row">
        <div class="col-md-12">
        <form method="post" id="customerlogin" class="m-t-20" action="<?= Url::link('/login/user') ?>" data-parsley-validate>
            <div class="form-area">
                <?= $userhidden ?>    
                <div class="form-group row">
                    <div class="col-12">
                        <input name="username" @click="onVueClick" class="form-control" type="text" data-parsley-required="1" placeholder="Username or Email Address">
                        <i class="fa fa-user username"></i>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-12">
                        <input name="password" class="form-control" type="password" data-parsley-required="1" placeholder="Password">
                        <i class="fa fa-key password"></i>
                    </div>
                </div>
                <div class="form-group row m-t-10">
                    <div class="col-sm-12">
                        <button class="btn btn-block btn-outline" type="submit">LOGIN</button>
                    </div>
                </div>
            </div>
        </form>        
            
        <div id="preloader-login" class="loader text-center" style="display: none;">
            <img src="<?= TEMPLATE_URL ?>/frontend/img/generic-loader.gif" style="width: 100px;" />
        </div>
            
        </div>
    </div>
</div>
