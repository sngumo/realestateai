<?php

use Jenga\App\Request\Url;
use Jenga\App\Request\Session;

?>
<div id="close-session" style="display: none">
    <div id="close-followup-overlay" class="overlay cls-followup"></div>
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
                        <strong>Session Expired</strong>
                    </div>
                </div>
            </div>

            <hr class="hide-on-minimize m-t-5" style="">
            <div class="row hide-on-minimize" style="">
                <div class="col"style="font-weight: normal;">
                    The current session appears to have expired. Please exit the session and login again.
                </div>
            </div>
            <hr class="hide-on-minimize" style="">
        </div>

        <!--Open FollowUp-->
        <div id="exit-followup" class="btn-holder">
            <a class="btn btn-block btn-primary close-summary" rel="external" href="<?= Url::route('/user/logout/'.Session::id())?>">
                Exit Session
                <i class="fas fa-external-link-alt m-l-10"></i> 
            </a>
        </div>
    </div>
</div>

