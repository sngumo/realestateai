<?php
    require ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'scripts' .DS. 'record-manual-payment-js.php';
?>
<div id="fields-container"></div>
<div id="panel-1" class="section-pane active" style="padding-bottom: 5vh">
    <div class="section-body active">
        <div id="input-1" class="form-group m-b-10" data-field-name="Date">
            <div class="row">
                <div class="col">
                    <label for="paydate" id="label_method">Date of Payment <span style="color:red">*</span></label>
                </div>
            </div>
            <div class="row input-holder">
                <div id="pay-date-wrapper" class="col date-wrapper" data-validate="required">
                    <i class="far fa-calendar-times"></i>
                    <?= $paydate ?>
                </div>
                <div id="pay-date-display" class="col text-center" style="display: none;">
                <span></span>
            </div>
            </div>
        </div>
        <div id="input-2" class="form-group" data-field-name="Method">
            <div class="row">
                <div class="col">
                    <?= $label_method ?>
                </div>
            </div>
            <div class="row input-holder">
                <div class="col" data-validate="required">
                    <?= $method ?>
                </div>
            </div>
        </div>
        <div id="input-3" class="form-group" data-field-name="Amount" data-min-val="50">
            <div class="row">
                <div class="col" data-validate="required">
                    <label for="amount" class="active" id="label_amount">Amount <span style="color:red">*</span></label>
                </div>
            </div>
            <div class="row input-holder">
                <div class="col" data-validate="required">
                    <?= $amount ?>
                </div>
            </div>
        </div>
        <div id="input-4" class="form-group" data-field-name="Comment">
            <div class="row">
                <div class="col" data-validate="optional">
                    <?= $label_memo ?>
                    <?= $memo ?>
                </div>
            </div>
        </div>
        <div class="form-group bottom-btn-row">
            <div class="row">
                <div class="col">
                    <button id="savebtn-partial" type="button" class="btn btn-success btn-block btn-rounded disabled">
                        <i class="fas fa-2x fa-receipt"></i>
                        Record Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="panel-2" class="section-pane" style="padding-bottom: 10vh; font-weight: normal;">
    <div class="section-body" style="display: none;">
        <input type="hidden" name="billing_charges" id="billing_charges" value="" />
        <div id="billing-details" class="invoice no-border p-0">
            <div class="text-center">
                <img src="<?= RELATIVE_APP_PATH ?>/views/loading/fups-loader.gif" />
            </div>
        </div>
        <div id="exit-summary-box" class="btn-holder bottom-btn-row">
            <a class="btn btn-block btn-rounded btn-primary close-summary">
                Exit Summary
                <i class="fas fa-external-link-alt m-l-10"></i> 
            </a>
        </div>
    </div>
</div>
