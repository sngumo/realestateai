<?php

use Jenga\App\Request\Url;
use Jenga\App\Request\Session;

?>
<div id="connection-error" style="display: none">
    <div id="close-followup-overlay" class="overlay-connection cls-followup"></div>
    <div id="close-followup-box" class="cls-followup">

        <div class="row" id="customer-row">
            <div class="col text-left m-t-10">
                <div class="title p-t-5" data-map-to="customer">
                </div>
            </div>
        </div>

        <div id="preview-body">
            <div class="row">
                <div class="col text-center p-0 m-0">
                    <div class="panel-heading" data-map-to="title">
                        <strong>Connection Error</strong>
                    </div>
                </div>
            </div>

            <hr class="hide-on-minimize m-t-5" style="">
            <div class="row hide-on-minimize" style="">
                <div class="col"style="font-weight: normal;">
                    There appears to be an error in the connection. Please login again.
                </div>
            </div>
            <hr class="hide-on-minimize" style="">
        </div>

        <!--Open FollowUp-->
        <div id="exit-followup" class="btn-holder">
            <a class="btn btn-block btn-primary close-summary" rel="external" href="<?= Url::route('/user/logout/'.Session::id())?>">
                Login Again
                <i class="fas fa-external-link-alt m-l-10"></i> 
            </a>
        </div>
    </div>
</div>

