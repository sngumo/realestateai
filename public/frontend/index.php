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
    <!--<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>-->
     <!--<script src="https://unpkg.com/vue-router@4.5.0/dist/vue-router.global.js"></script>-->
    
    <!-- Parsley -->
    <link href="<?= RELATIVE_APP_HTML_PATH ?>/forms/scripts/parsley-js/parsley.css" rel="stylesheet">
    
  <!-- ========== CSS Files ========== -->
  <link href="<?= TEMPLATE_URL ?>/backend/style/css/root.css" rel="stylesheet">
  <link href="<?= TEMPLATE_URL ?>/frontend/style/css/style.css" rel="stylesheet">
  <link href="<?= TEMPLATE_URL ?>/backend/fonts/css/fonts.css" rel="stylesheet">
  <link href="<?= TEMPLATE_URL ?>/frontend/js/mobile/jquery.mobile-1.4.5.css" rel="stylesheet" type="text/css" />
  <style type="text/css">
    body {
        background: #F5F5F5;
    }
  </style>
  
  <!-- The Javascript Initialization Files-->
<script src="<?= TEMPLATE_URL ?>/frontend/js/jquery.min.js" type="text/javascript"></script>
<script src="https://code.jquery.com/mobile/1.5.0-rc1/jquery.mobile-1.5.0-rc1.min.js" type="text/javascript"></script>
  
<script src="https://acrobatservices.adobe.com/view-sdk/viewer.js"></script>
<script type="text/javascript" src="<?= RELATIVE_APP_HTML_PATH ?>/forms/scripts/parsley-js/parsley.min.js"></script>
    
    <?php
        HTML::head(false);
    ?>
<script type="text/javascript">
    $(function(){
        
        //set site path into local storage
        localStorage.setItem('SITE_PATH', '<?php echo SITE_PATH ?>');
    });
</script>

    <!-- ================================================
    Main App Js File
    ================================================ -->
<script src="<?= TEMPLATE_URL ?>/frontend/js/main_app.js" type="text/javascript"></script>

</head>
<body class="main-box p-t-30 p-b-30">
    <div data-role="page" class="login-page m-t-20" id="loginpage" data-url="loginpage">
        <div role="main">
        <?php 
            $this->loadMainPanel(); 
        ?>        
        </div>
    </div>
    <div data-role="page" class="upload-page" id="uploadpage" data-url="uploadpage">
        <div role="main">
        <?php
            $this->loadPanelPosition('upload');
        ?>
        </div>
    </div>
    <div data-role="page" class="doc-analysis-page" id="doc-analysis-page" data-url="doc-analysis-page">
        <div role="main">
        <?php
            $this->loadPanelPosition('doc-analysis');
        ?>
        </div>
    </div>
</body>
</html>
