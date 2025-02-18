<?php
use Jenga\App\Views\HTML;
//var_dump(Jenga\App\Request\Session::all());
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
    
    <!-- ================================================
    VueJs Library
    ================================================ -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
     <script src="https://unpkg.com/vue-router@4.5.0/dist/vue-router.global.js"></script>
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
    <?php 
        $this->loadMainPanel(); 
    ?>        
</body>
</html>
