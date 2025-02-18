<?php
use Jenga\App\Views\HTML;
use Jenga\App\Request\Url;
use Jenga\App\Views\Overlays;
use Jenga\App\Views\Notifications;

define('PAGE_NAME', 'Get Quote Comparison');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Kode is a Premium Bootstrap Admin Template, It's responsive, clean coded and mobile friendly">
<meta name="keywords" content="bootstrap, admin, dashboard, flat admin template, responsive," />
<title><?= PAGE_NAME.' | '.PROJECT_NAME ?></title>

<!-- App Favicon -->
<link rel="shortcut icon" href="<?php echo TEMPLATE_URL ?>/frontend/img/logoicon.png">
<!-- ================================================
jQuery Library
================================================ -->
<script type="text/javascript" src="<?php echo TEMPLATE_URL ?>/frontend/js/jquery.min.js"></script>

<!-- ================================================
Bootstrap Core JavaScript File
================================================ -->
<script src="<?= TEMPLATE_URL ?>/backend/js/popper.min.js"></script><!-- Tether for Bootstrap -->
<script src="<?= TEMPLATE_URL ?>/backend/js/bootstrap/bootstrap.min.js"></script>
<script src="<?= TEMPLATE_URL ?>/backend/js/modernizr.min.js"></script>

<!-- ================================================
Sweet Alert
================================================ -->
<script src="<?= TEMPLATE_URL ?>/backend/js/sweet-alert/sweet-alert.min.js"></script>

<!-- ========== Css Files ========== -->
<link href="<?= TEMPLATE_URL ?>/frontend/style/css/style.css" rel="stylesheet">
<link href="<?= TEMPLATE_URL ?>/backend/fonts/css/fonts.css" rel="stylesheet">
<?php  
    //include the <head> resources
    HTML::head();
?>
</head>
<body>

<!-- Start Top -->
<div id="top" class="clearfix">
  <div class="col-md-5 col-lg-5 col-sm-4 col-xs-6 logo"><img src="<?php echo TEMPLATE_URL ?>/frontend/img/bimaguru.png"></div>
  <ul class="col-md-7 col-lg-7 col-sm-8 col-xs-6 menu">
    <li><a href="#">Home</a></li>
    <li><a href="#">Product Features</a></li>
    <li><a data-toggle="modal" data-target="#addeditmodal" data-backdrop="static" href="<?= RELATIVE_ROOT ?>/customer/login">Sign Up</a></li>
    <li><a href="#">Contact Us</a></li>
  </ul>
</div>
<!-- End Top -->

<!-- Start Quote Box -->
<div class="container">
  <?php
        $this->loadMainPanel(); 
  ?>
</div>
<!-- End Quote Box -->


<!-- Start Footer -->
<div id="footer">
  <p>2018 All right reserved - Nero Web Solutions</p>
</div>
<!-- End Footer -->

<?php
    HTML::end();
    echo Overlays::StrippedModal(['id'=>'addeditmodal']);
    echo Overlays::StrippedModal(['id'=>'modal-lg', 'size'=>'large']);
    echo Overlays::confirm();
?>

<!-- ================================================
Plugin.js - Some Specific JS codes for Plugin Settings
================================================ -->
<script type="text/javascript" src="<?= TEMPLATE_URL ?>/backend/js/plugins.js"></script>

<!-- ================================================
jQuery UI
================================================ -->
<?php
if($this->getCurrentRoute() !== 'get_admin_customers_matchcolumns_id'){
?>
<script type="text/javascript" src="<?= TEMPLATE_URL ?>/backend/js/jquery-ui/jquery-ui.min.js"></script>
<?php
}
?>

<!-- ================================================
Toast Alert
================================================ -->
<script src="<?= TEMPLATE_URL ?>/backend/js/toast/toast.min.js"></script>

<!-- ================================================
Bootstrap Toogle JavaScript File
================================================ -->
<script type="text/javascript" src="<?= TEMPLATE_URL ?>/backend/js/bootstrap-toggle/bootstrap-toggle.min.js"></script>

</body>
</html>
