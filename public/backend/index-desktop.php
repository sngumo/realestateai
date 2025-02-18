<?php
    use Jenga\App\Views\HTML;
    use Jenga\App\Request\Url;
    use Jenga\App\Views\Overlays;
    use Jenga\App\Request\Session;
?>      
<div class="loading">
    <div class="loader">
      <span>
          <img src="<?= TEMPLATE_URL ?>/backend/img/followups-admin-logo.png" alt="loading-img">
      </span>
    </div>
</div>

<div class="drawer drawer-container" style="display: none;">
    <div class="drawer drawer-backdrop" style="display: none;"></div>
    <div class="drawer-content p-t-15 p-b-20" style="display: none;">
    </div>
</div>
      
  <!-- End Page Loading -->
<div id="top" class="clearfix mobile-only" env="mobile">

    <!-- Start App Logo -->
    <div class="applogo">
        <a href="#" class="logo">Follow<span class="orange">Ups</span></a>
    </div>
    <!-- End App Logo -->
    
    <!-- Start Sidebar Show Hide Button -->
    <a href="#" class="sidebar-open-button-mobile"><i class="fa fa-bars"></i></a>
    <!-- End Sidebar Show Hide Button -->
    
</div>
<div id="desktop" class="clearfix" env="desktop">
    
    <div class="row p-0 m-0">
        <div class="col-md-1 logo-container">
            <?php
            if($this->user()->role->alias != 'sysadmin'){
            ?>
            <img src="<?= TEMPLATE_URL ?>/backend/img/followups-admin-logo.png" alt="icon">
            <?php
            }
            ?>
        </div>
        <div class="col-md-5">
            <?php
            if($this->user()->role->alias != 'sysadmin'){
            ?>
            <span class="searchform">
              <input type="text" class="searchbox" id="searchbox" placeholder="Search By Customer or Product">
              <span class="searchbutton"><i class="fa fa-search"></i></span>
              <span class="cancelsearch" style="display: none;"><i class="fa fa-close"></i></span>
            </span>
            <?php
            }
            ?>
        </div>
        <div class="col-md-4 p-t-15">
            <?php
            if($this->user()->role->alias != 'sysadmin'){
            ?>
            <a href="#" class="btn btn-rounded btn-followup btn-primary pull-right">
                <i class="zmdi zmdi-file-plus p-r-10"></i>
                Create New FollowUp
            </a>
            <?php
            }
            ?>
        </div>
        <div class="col-md-2">
            <div class="row">
                <div class="col-md-12 icons">
                    <ul>
                    <li class="dropdown link">
                        <a href="#" class="dropdown-toggle sidepanel-btn" aria-expanded="false">
                          <?php
                            $this->loadPanelPosition('notifications-icon', ['user' => $this->user()]);
                          ?>
                        </a>
                    </li>
                    <li class="dropdown link">
                      <a href="#" data-toggle="dropdown" class="dropdown-toggle profilebox" aria-expanded="false">
                          <i class="fa fa-2x fa-user-circle-o"></i>
                          <span class="caret"></span>
                      </a>
                    <?php $this->loadPanelPosition('login-panel', ['user' => $this->user()]); ?>
                    </li>
                </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- //////////////////////////////////////////////////////////////////////////// --> 
<!-- START SIDEBAR -->
<?php if($this->user()->role->alias == 'sysadmin'){ ?>
<div class="sidebar clearfix minimized">

<!-- Start App Logo -->
    <div class="applogo" env="desktop">
      <a href="#" class="logo">
          <img src="<?= TEMPLATE_URL ?>/backend/img/followups-admin-logo.png" height="60px" />
      </a>
    </div>
<!-- End App Logo -->
    
    <?php
        $this->loadPanelPosition('left-panel', ['user' => $this->user()]);
    ?>
</div>
<?php
}
else{
?>
<div role="tabpanel" class="sidepanel" style="display: none;">
    <?php
        $this->loadPanelPosition('notices-panel');
    ?>
</div>
<?php
}
?>
<!-- END SIDEBAR -->
<!-- //////////////////////////////////////////////////////////////////////////// --> 
<!-- START CONTENT -->
<?php
$fullwidth = '';

if($this->user()->role->alias != 'sysadmin'){
    $fullwidth = 'full-width';
}
?>
<div class="content <?= $fullwidth ?>">
    <div class="container-widget jng-main-page">
        <?php
            //load main panel
            $this->loadMainPanel();
        ?>
    </div>
    <?php
        //$this->loadPanelPosition('shortcuts');
    ?>

<!-- END CONTAINER -->
<!-- //////////////////////////////////////////////////////////////////////////// --> 

<!-- Start Footer -->
<div class="row footer">
  <div class="col-md-6 text-left">
    Copyright © 2018
  </div>
  <div class="col-md-6 text-right weblinks">
    Design and Developed by <a href="http://www.nerosolutions.com" target="_blank">Nero Web Solutions</a>
  </div> 
</div>
<!-- End Footer -->
<?php

    echo Overlays::StrippedModal(['id'=>'addeditmodal']);
    echo Overlays::StrippedModal(['id'=>'modal-lg', 'size'=>'large']);
    echo Overlays::confirm();
    
?>
</div>
<!-- End Content -->
<!-- START SIDEPANEL -->
<div class="sidebar-right-panel" style="display: none;">
    <div class="login-panel">
        <?php
            $this->loadPanelPosition('login-panel', ['user' => $this->user()]);
        ?>
    </div>
    <div class="right-details-panel">
    <?php
        $this->loadPanelPosition('right-panel', ['user' => $this->user()]);
    ?>
    </div>
</div>
<?php
    HTML::end();
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
Sweet Alert
================================================ -->
<script src="<?= TEMPLATE_URL ?>/backend/js/sweet-alert/sweet-alert.min.js"></script>

<!-- ================================================
Toast Alert
================================================ -->
<script src="<?= TEMPLATE_URL ?>/backend/js/toast/toast.min.js"></script>

<!-- ================================================
Bootstrap Toogle JavaScript File
================================================ -->
<script type="text/javascript" src="<?= TEMPLATE_URL ?>/backend/js/bootstrap-toggle/bootstrap-toggle.min.js"></script>
