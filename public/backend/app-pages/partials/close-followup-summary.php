<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
$(function(){
    $('#close-followup-box #open-notice').on('click', function(){
        var notice = $('#close-followup-box #followup-notice');

        if(notice.is(':hidden')){
            notice.show('fast');
        }
        else{
            notice.hide('fast');
        }
    });
});
</script>
<div id="close-followup-overlay" class="overlay cls-followup"></div>
<div id="close-followup-box" class="cls-followup shadow">
    <!--Customer Name-->
    <div class="row" id="customer-row">
        <div class="col text-left m-t-10">
            <div class="title p-t-5" data-map-to="customer">
                <?= $invoice->customer->name ?>
            </div>
        </div>
        <div class="col-1 text-right m-t-10 m-r-20 p-t-5">
            <!--<i class="fas fa-2x fa-times close-preview" style="opacity: 0.7"></i>-->
            <i class="ti-close close-summary" style="font-weight: bold;"></i>
        </div>
    </div>
    <hr>

    <div id="preview-body">

        <!--Title Full Amount-->
        <div class="row">
            <div class="col text-center p-0 m-0">
                <div class="panel-heading p-b-0 p-t-0" data-map-to="title">
                    <strong><?= $invoice->title ?></strong>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col text-center p-0 m-0">
                <span class="small">(<?= $bill['numbers'] ?>) numbers in follow-up</span>
            </div>
        </div>

        <!--FollowuP Fee-->
        <div class="row m-t-10">
            <div class="col text-center">
                <div class="admin-fee m-t-5">
                    Followup Bill: 
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col text-center">
                <div class="m-t-5">
                    <span class="full_amounts"><?= $bill['currency'].' '.$bill['fee'] ?></span><br/>
                    <span id="open-notice" class="btn m-t-5 btn-xs btn-light btn-rounded">What is this?</span>
                </div>
            </div>
        </div>

        <!--Total and Remaining-->
        <hr class="hide-on-minimize" style="">
        <div class="row hide-on-minimize" style="">
            <div class="col">
                <span class="app-label">
                    <i class="fas fa-cash-register m-r-5"></i>
                    Collected <br>
                </span>
                <span class="amounts" data-map-to="full_amount">
                    <?= $invoice->currency->code.' '.number_format($invoice->total, 2) ?>
                </span>
            </div>
            <div class="col text-right">
                <span class="app-label">
                    Remaining <br>
                </span>
                <span class="amounts" data-map-to="remaining">
                    <?php
                        echo $invoice->currency->code.' '.number_format($invoice->amount_due, 2);
                    ?>
                </span>
            </div>
        </div>

        <!--Reminder Type-->
        <hr class="hide-on-minimize" style="">
        <div class="row hide-on-minimize" style="">
            <div class="col">
                <span data-map-to="remindertype"></span>
            </div>
        </div>

        <div class="row">
            <div class="col">
                <span class="app-label">
                    Start Date<br>
                </span>
                <span data-map-to="created_at">
                    <?= date('jS  F Y', $invoice->created_at) ?>
                </span>
            </div>
            <div class="col text-right">
                <span class="app-label">
                    End Date <br>
                </span>
                <span data-map-to="modified_at">
                    <?= date('jS  F Y', time()) ?>
                </span>
            </div>
        </div>
        <hr>
        <div class="row" id="followup-notice" style="display: none;">
            <div class="col p-b-10">
                <p>
                    <strong>Followups isn't completely free</strong>, after a successful followup a <strong>0.93%</strong> fee 
                    is charged against the collected amount. The Charge is only applied when 
                    FollowUps has sent reminders to the client, if no reminders have been sent, the charge is zero.
                </p>
            </div>
        </div>
    </div>

    <!--Open FollowUp-->
    <div id="exit-followup" class="btn-holder">
        <a class="btn btn-block btn-primary close-summary">
            Close Bill
            <i class="fas fa-external-link-alt m-l-10"></i> 
        </a>
    </div>
</div>

