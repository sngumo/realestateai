<?php
use Jenga\App\Views\HTML;
use Jenga\App\Request\Url;
use Jenga\App\Views\Overlays;
use Jenga\App\Request\Cookie;
use Jenga\App\Request\Session;
use Jenga\App\Project\Core\Project;
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
  
  <!-- ========== CSS Files ========== -->
  <?php
    
    //load page based on device mobile, tablet or desktop
    if($this->environment()->isMobile()){
  ?>
      <link href="<?= TEMPLATE_URL ?>/backend/style/css/root-bootstrap-4.css" rel="stylesheet">
  <?php
    }
    else{
  ?>
      <link href="<?= TEMPLATE_URL ?>/backend/style/css/root.css" rel="stylesheet">
  <?php
    }
  ?>
  <link href="<?= TEMPLATE_URL ?>/backend/fonts/css/fonts.css" rel="stylesheet">

    <?php
    if($this->environment()->isMobile()){
    ?>
        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.js"></script>
        <!-- ================================================
          jQuery mobile library
        ================================================ -->
        <link rel="stylesheet" href="https://code.jquery.com/mobile/1.4.5/jquery.mobile.structure-1.4.5.css" />
        <script type="text/javascript" src="https://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.js"></script>
        
        <link rel='stylesheet' id='fontawesome-css'  href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css?ver=4.9.12' type='text/css' media='all' />
        <script type="text/javascript" src="<?= RELATIVE_VIEWS ?>/notifications/notifications.js"></script>
    <?php
    }
    else{
    ?>
  
      <!-- ================================================
      jQuery Library
      ================================================ -->
      <script type="text/javascript" src="<?= TEMPLATE_URL ?>/backend/js/jquery.min.js"></script>
        
    <?php
    }
    
    //include the secure ajax mechanism
    include ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'scripts' .DS. 'secure-ajax-mechanism-js.php';
    
    ?>
    <!-- ================================================
    Bootstrap Core JavaScript File
    ================================================ -->
    <script src="<?= TEMPLATE_URL ?>/backend/js/popper.min.js"></script><!-- Tether for Bootstrap -->
    <script src="<?= TEMPLATE_URL ?>/backend/js/bootstrap/bootstrap.min.js"></script>
    <script src="<?= TEMPLATE_URL ?>/backend/js/modernizr.min.js"></script>
    <script src="<?= TEMPLATE_URL ?>/backend/js/screen-switcher.js"></script>
    <script src="<?= TEMPLATE_URL ?>/backend/js/jquery.cookie.js"></script>
    
  <?php  
  
    //include the <head> resources
    HTML::head();
    
    //load page based on device mobile, tablet or desktop
    if(!$this->environment()->isMobile()){
    
        //add the off canvas script
        include ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'scripts' .DS. 'off-canvas-scripts.php';
        
        //include the desktop head scripts
        include ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'scripts' .DS. 'desktop-head-scripts.php';
    }
    else{
        
        //include the mobile head scripts
        include ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'app-pages' .DS. 'scripts' .DS. 'mobile-head-scripts.php';
    }
  ?>
    
  </head>
  
<body environment="">
<?php
    //show notifications
    HTML::notifications(); 
    
    //load page based on device mobile, tablet or desktop
    if($this->environment()->isMobile()){
        
        require ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'index-mobile.php';
        
        //close session overlay
        require ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'close-session-overlay.php';

        //connection error overlay
        require ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'connection-error-overlay.php';

        //add the footer js scripts
        require ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'app-pages' .DS. 'scripts' .DS. 'footer-js.php';
    }
    else{
        require ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'index-desktop.php';
    }
    
    
?>
</body>
</html>