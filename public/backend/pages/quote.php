<?php
use Jenga\App\Views\HTML;
use Jenga\App\Request\Url;
use Jenga\App\Views\Overlays;
use Jenga\App\Views\Notifications;

define('PAGE_NAME', 'Get Quote Comparison');
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= $this->getConfigs()->description ?>">
    <meta name="keywords" content="<?= $this->getConfigs()->keywords ?>" />
    <title><?= PROJECT_NAME ?></title>
    
    <!-- App Favicon -->
    <link rel="shortcut icon" href="<?php echo TEMPLATE_URL ?>/backend/img/logoicon.png">
    
    <!-- App title -->
    <title><?= PAGE_NAME.' | '.PROJECT_NAME ?></title>
    
     <!-- Sweet Alert css -->
    <link href="<?php echo TEMPLATE_URL ?>/backend/plugins/bootstrap-sweetalert/sweet-alert.css" rel="stylesheet" type="text/css" />
    
    <!-- jQuery  -->
    <script src="<?php echo TEMPLATE_URL ?>/backend/js/jquery.min.js"></script>
    <script src="<?php echo TEMPLATE_URL ?>/backend/js/popper.min.js"></script><!-- Tether for Bootstrap -->
    <script src="<?php echo TEMPLATE_URL ?>/backend/js/bootstrap.min.js"></script>

    <!-- Sweet Alert js -->
    <script src="<?php echo TEMPLATE_URL ?>/backend/plugins/bootstrap-sweetalert/sweet-alert.min.js"></script>

    <!-- Modernizr js -->
    <script src="<?php echo TEMPLATE_URL ?>/backend/js/modernizr.min.js"></script>
    
    <link href="<?= TEMPLATE_URL ?>/backend/style/css/root.css" rel="stylesheet">
    <style type="text/css">
        body {
            background: #F5F5F5;
        }
    </style>
    
    <?php
        HTML::head();
    ?>
  </head>
  <body>
    <?php
        HTML::notifications();
    ?>
    <div class="public-wrapper container-fluid jng-main-page">
        <!-- start card-box-->
        <div class="card-box-enlarged">
            <div class="col-3"> 
                <a href="index.html" class="logo"> 
                    <img src="<?php echo TEMPLATE_URL ?>/backend/images/cwlogohorizontal.jpeg" style="width: 70%;"> 
                </a> 
            </div>
            <div class="col-8 pull-right tilebox-three">
                <ul class="tool-icons nav nav-pills m-b-10">
                    <li class="col-sm-6">
                        <div class="bg-icon-small float-left bg-primary">
                            <i class="icon-phone" style="color: #fff; font-size: 30px;"></i>
                        </div>
                        <p class="m-t-10"><strong>+254 020 272 1555</strong><br/>
                        chanceryinfo@chancerywright.com</p>
                    </li>
                    <li class="col-sm-6 pull-right">
                        <a class="text-dark" data-toggle="modal" data-target="#addeditmodal" data-backdrop="static" 
                            href="<?= Url::link('/customer/login') ?>">
                            <div class="text-center">
                                <div class="bg-icon-small float-left bg-primary">
                                    <i class="icon-user-following" style="color: #fff; font-size: 30px;"></i>
                                </div>
                            </div>
                            <p class="m-t-10"><strong>Sign Into</strong><br/>
                            Customer Portal</p>
                        </a>
                    </li>
                </ul>
            </div>
      </div>
      <!-- end card-box-->
      <div class="row m-t-20">
          <div class="col-md-10 col-md-offset-1">
                <?php 
                    echo Notifications::Alert('This is the testing platform for the Chancery Wright portal. Data saved here will be overwritten every 24 hrs', 'warning');
                    $this->loadMainPanel(); 
                ?>
          </div>
      </div>
    </div>
      
    <?php
        HTML::end();
        echo Overlays::StrippedModal(['id'=>'addeditmodal']);
        echo Overlays::StrippedModal(['id'=>'modal-lg', 'size'=>'large']);
        echo Overlays::confirm();
    ?>
      
    <!-- end wrapper page -->
    <script>
            var resizefunc = [];
        </script>
    <!-- ================================================
        Toast Alert
    ================================================ -->
    <script src="<?php echo TEMPLATE_URL ?>/backend/js/toast/toast.min.js"></script>
    <!-- jQuery  -->
    <script src="<?php echo TEMPLATE_URL ?>/backend/js/detect.js"></script>
  </body>
</html>
