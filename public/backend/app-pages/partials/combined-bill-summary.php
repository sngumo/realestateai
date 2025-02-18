<?php
use Jenga\App\Request\Url;
use Jenga\App\Request\Session;
?>
<script type="text/javascript">
    var checkloop = setInterval(function(){
        checkStatus(<?= $bill->agency->id ?>);
    }, 8000);
    
    function checkStatus(id){
        
        //assign url
        var url = "<?= Url::link('/api/business/billing/status') ?>/" + id;
        
        //set the id into local storage
        if(localStorage.getItem('cleared:id') == null){
            localStorage.setItem('cleared:id', id);
        }
        
        //check status via ajax
        $.ajax({
            method: "GET",
            url: url,
            success: function(feedback){
                
                var response = JSON.parse(feedback);
                
                if(response.status === 'TRUE' && localStorage.getItem('cleared:id') == response.id){
                    
                    //success swal
                    swal({
                        title: "Payment Received",
                        text: "Thank you for choosing FollowUps",
                        type: "success",
                        button: "Ok",
                        width: "530px"
                    }, function(){
                        
                        clearInterval(checkloop);
                        localStorage.removeItem('cleared:id');
                        
                        //clear combined bill
                        $('#combined-bill-overlay').remove();
                        $('#combined-bill').remove();
                        
                        //refresh page
                        window.location.reload(true);
                    });
                }
            }
        });
    }
    
    $(function(){
        
        //exit followUps
        $('div.exit-followup a').on('click', function(){
            
            //logout app
            logoutApp(checkloop);
            
            //set logout bypass
            localStorage.setItem('bypass-firebase', 'true');
        });
    });
</script>
<div id="combined-bill-overlay" class="cbill-overlay"></div>
<div id="combined-bill">
    
    <!--Customer Name-->
    <div class="row" id="customer-row">
        <div class="col text-left m-t-10">
            <div class="title p-t-5">
                Billing Summary
            </div>
        </div>
    </div>
    <hr>
    
    <!--Title Full Amount-->
    <div class="row">
        <div class="col text-center p-0 m-0">
            <p class="m-b-0">Pay to</p>
            <span><?= $cfgs->company_name ?></span>
        </div>
    </div>
    <hr>
    
    <!--FollowuP Fee-->
    <div class="row m-t-10">
        <div class="col text-left">
            <p class="m-b-0">PayBill</p>
            <span><?= $bill->shortcode ?></span>
        </div>
        <div class="col text-right">
            <p class="m-b-0">Account Name</p>
            <span><?= $bill->agency->alias ?></span>
        </div>
    </div>
    <hr>
    
    <div class="row">
      <div class="col text-center">
          <p class="m-t-0 m-b-0">Amount</p>
          <h2 class="m-t-0 m-b-0"><?= $bill->code.' '.number_format($bill->total, 2) ?></h2>
      </div>
    </div>
    <hr>
    
    <div class="row" id="followup-notice">
        <div class="col p-b-10">
            <div class="signature p-t-0 p-b-0 m-b-0">
                <p class="m-b-10"><b>Disclaimer</b></p>
            </div>
            <div class="bottomtext">
                This bill listing once generated must be cleared, before service is resumed.
            </div>
        </div>
    </div>
    <div class="btn-holder exit-followup">
        <!-- logout button -->
        <a rel="external" class="btn btn-light notify-item pull-right">
            <i class="fa fa-times"></i> Logout
        </a>
    </div>
</div>
