<?php
use Jenga\App\Views\HTML;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= $this->getConfigs()->description ?>">
  <meta name="keywords" content="<?= $this->getConfigs()->keywords ?>" />
  <title><?= PROJECT_NAME ?></title>

    <!-- App Favicon -->
    <link rel="shortcut icon" href="<?php echo TEMPLATE_URL ?>/frontend/img/followupsicon.png">
    
    <!-- ================================================
    jQuery Library
    ================================================ -->
    <script type="text/javascript" src="<?= TEMPLATE_URL ?>/backend/js/jquery.min.js"></script>
    
    <?php
        HTML::head();
    ?>
    
    <!-- Parsley -->
    <link href="<?= RELATIVE_APP_HTML_PATH ?>/forms/scripts/parsley-js/parsley.css" rel="stylesheet">
    <script type="text/javascript" src="<?= RELATIVE_APP_HTML_PATH ?>/forms/scripts/parsley-js/parsley.min.js"></script>
    
  <!-- ========== CSS Files ========== -->
  <link href="<?= TEMPLATE_URL ?>/backend/style/css/root.css" rel="stylesheet">
  <link href="<?= TEMPLATE_URL ?>/frontend/style/css/style.css" rel="stylesheet">
  <link href="<?= TEMPLATE_URL ?>/backend/fonts/css/fonts.css" rel="stylesheet">
  <style type="text/css">
    body {
        background: #F5F5F5;
    }
  </style>
</head>
<body class="main-box p-t-30 p-b-30">
    <div class="login-form frontend p-t-0">   
        <div class="top m-b-30">
          <img src="<?= TEMPLATE_URL ?>/backend/img/followups-admin-logo.png" alt="icon" class="icon">
          <h1 style="font-size: 50px !important; letter-spacing: -2px;">Follow<span class="orange">Ups</span></h1>
          <p class="">Tired of chasing debts & pending payments? <br/>Automate Them</p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
            <?php 
                $this->loadMainPanel(); 
            ?>                
            </div>
            <div class="col-md-6">
            <?php
                $this->loadPanelPosition('login');
            ?>
            </div>
        </div>
        
        <div class="loader text-center" style="display: none;">
            <img src="<?= TEMPLATE_URL ?>/frontend/img/generic-loader.gif" />
        </div>
    </div>
</body>
</html>
