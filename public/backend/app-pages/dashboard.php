<?php

//load combined billing
$this->loadPanelPosition('combined-billing'); 
    
//load dashboard js
require_once ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'scripts' .DS. 'dashboard-js.php';
?>
<div id="dashboard-container" role="main">
    <div class="app-header">
        <div class="container">
            <div class="row app-mobile-logo">
                <div class="col-2 p-t-10">
                    <img class="logo" src="<?= TEMPLATE_URL ?>/backend/img/followups-icon-small.png" />
                </div>
                <div class="col-8 text-center p-t-10">
                  <h1>
                      <span>Follow</span>
                      <span class="orange">Ups</span>
                  </h1>
                </div>
                <div class="col-2 p-t-15">
                    <i class="fa fa-2x fa-search" aria-hidden="true"></i>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <?php
                $this->loadPanelPosition('mobile-filter-toolbar');
            ?>
        </div>
    </div>
    <div class="app-main">
        <div style="display: none;" id="filter-indicator" class="panel bg-white m-l-15 m-r-15 m-b-10 p-10">
            <div class="row">
                <div class="col-2 p-t-10">
                    <a class="close-filter">
                        <i class="ti-angle-left"></i>
                    </a>
                </div>
                <div class="col text-center">
                    <div class="row">
                        <div class="col text-left p-l-5">
                            <h4 class="m-0 filter-type"></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
        <?php
            $this->loadPanelPosition('mobile-invoice-summary'); 
        ?>
        </div>
    </div>
    <!--App Footer-->
    <?php
        require_once ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'dashboard-footer.php';
    ?>
    <div id="dashboard-overlay" class="overlay" style="display: none;"></div>
    
    <!--Search Box-->
    <?php
        require_once ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'search-box.php';
    ?>
    
    <!--Preview Box-->
    <?php
        require_once ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'preview-box.php';
    ?>
    
</div>