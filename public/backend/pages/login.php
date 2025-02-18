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
    
    <!-- ================================================
    jQuery Library
    ================================================ -->
    <script type="text/javascript" src="<?= TEMPLATE_URL ?>/backend/js/jquery.min.js"></script>
    
    <?php
    
        //include the secure ajax mechanism
        include ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'scripts' .DS. 'secure-ajax-mechanism-js.php';
    
        //add HTML head
        HTML::head();
        
        //notifications
        HTML::script(RELATIVE_VIEWS.'/notifications/notifications.js','file');
    ?>
    
    <!-- Parsley -->
    <link href="<?= RELATIVE_APP_HTML_PATH ?>/forms/scripts/parsley-js/parsley.css" rel="stylesheet">
    <script type="text/javascript" src="<?= RELATIVE_APP_HTML_PATH ?>/forms/scripts/parsley-js/parsley.min.js"></script>
    
  <!-- ========== CSS Files ========== -->
  <link href="<?= TEMPLATE_URL ?>/backend/style/css/root.css" rel="stylesheet">
  <style type="text/css">
    body {
        background: #F5F5F5;
    }
  </style>
</head>
<body>
    <div class="login-form admin">      
        <?php 
            $this->loadMainPanel(); 
        ?>
      <div class="footer-links row">
        <div class="text-right"><a href="#"><i class="fa fa-lock"></i> Forgot password</a></div>
      </div>
    </div>
</body>
</html>
