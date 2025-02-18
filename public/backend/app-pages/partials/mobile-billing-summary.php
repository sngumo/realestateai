<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
    
    $(function(){
        
        //pay now
        $('#pay-now').on('click', function(ev){
            
            //prevent the default action
            ev.preventDefault();
            jng.blockIO('Generating Bill');
            
            //get the link href
            var href = $(this).attr('href');
            
            //compile total
            var totals = 0;
            var ids = '';
            
            //loop through all the checkboxes
            $('input.chkbox').each(function(){

                if($(this).is(':checked')){

                    var chkval = JSON.parse($(this).val());
                    
                    totals += parseInt(chkval['amount']);
                    ids += chkval['id'] + ',';
                }
            });
            
            //load data
            $.ajax({
                method: "POST",
                url: href,
                data: {
                    agencies_id: '<?= $agency->id ?>',
                    total: totals,
                    bills: ids,
                    status: 'unpaid'
                },
                tryCount : 0,
                retryLimit : 3,
                error: function (){

                    this.tryCount++;
                    if (this.tryCount <= this.retryLimit) {

                        //try again
                        $.ajax(this).done(function(response){
            
                            //switch to dashboard
                            window.location.hash = '#dashboard'; 

                            $('footer').hide();
                            $('body #dashboard').prepend(response);
                            
                            swal.close();
                        });
                        return;
                    }   
                    else{
                        $(document).trigger('connection:error');
                    }

                    return;
                }
            }).done(function(response){
            
                //switch to dashboard
                window.location.hash = '#dashboard';
                
                $('footer').hide();
                $('body #dashboard').prepend(response);
                
                swal.close();
            });
        });
        
        $('#mobile-billing-summary #content').on('click', function(){
                $('#mobile-billing-summary').addClass('expanded');
                $('#mobile-billing-summary #content').css('height', '35vh');
        });
        
        $('#balance-pay-now').on('click', function(){
            if($('#mobile-billing-summary').hasClass('expanded')){
                $('#mobile-billing-summary').removeClass('expanded');
                $('#mobile-billing-summary #content').css('height', '15vh');
            }
        });
        
        $('a.close-holder').on('click', function(){
            $('#dashboard').removeClass('search-on');
            $("#footer-items-holder").closePanelDown();
            
            //panel close event
            $(document).trigger('panel:close');
        });
    });
</script>

        <div id="content" style="height: 15vh;">
            <table class="table m-t-5">
              <tbody>
                 <?php

                  if(!is_null($bills) && count($bills) > 0){
                      
                    $bills = array_reverse($bills);
                    foreach($bills as $bill){

                        //billing period
                        $from = date('d M', $bill->billing_start);
                        $to = date('d M, y', $bill->billing_end);
                 ?>
                        <tr class="inv-row">
                            <td class="p-b-0 p-l-20">
                                <div class="checkbox checkbox-info checkbox-circle" style="display: none;">
                                    <input class="chkbox" id="chkbox_<?= $bill->id ?>" type="checkbox"  value="<?= htmlentities(json_encode([
                                            'id' => $bill->id,
                                            'amount' => $bill->amount_due
                                        ])); ?>" checked="checked">
                                    <label for="chkbox_<?= $bill->id ?>"></label>
                                </div>

                                <?= $bill->invoice->title ?> 
                                <p><?= $from ?> to <?= $to ?></p>
                                <!-- $bill->currency->code.' '.number_format($bill->payment->amount, 2)-->
                            </td>
                            <td class="text-right p-r-20">
                              <?= number_format($bill->amount_due, 2) ?>
                            </td>
                        </tr>
                 <?php
                    }
                  }
                  else{
                 ?>
                        <tr>
                            <td colspan="3">No bills listed</td>
                        </tr>
                 <?php
                  }
                 ?>
              </tbody>
            </table>
    </div>
    <hr class="m-t-0">
    <div id="pay-details" class="row">
        <div class="col-12 text-center">
          <span class="small">Pay to</span>
          <h5 class="m-t-0 m-b-10"><?= $default->name ?></h5>
        </div>
        <div class="col text-left p-l-30">
            <span class="small">Account</span>
          <h5 class="m-t-0 m-b-20"><?= $agency->alias ?></h5>
        </div>
        <div class="col text-right p-r-30">
            <span class="small">Paybill</span>
          <h5 class="m-t-0 m-b-20"><?= $c2b->shortcode ?></h5>
        </div>
    </div>
    <div id="bottom-btn" class="row">
      <div id="balance-pay-now" class="col p-t-10">
        <div class="row">
            <div class="col p-l-30 p-t-5">
                
                <?php 
                if(!is_null($discount) && !empty($discount) && $discount != ''){ 
                  ?>
                    <h5 style="margin-top: -15px;">BALANCE<span style="font-size: 11px;"> (Discounted)</span></h5>       
                    <h6 class="m-t-0" style="color: gray">Total</h6>       
                <?php
                }
                else{
                ?>
                    <h5 class="m-t-0">BALANCE</h5>       
                <?php
                }
                ?>
            </div>  
            <div class="col text-right p-r-30">
                <?php 
                if(!is_null($discount) && !empty($discount) && $discount != ''){ 
                  ?>
                    <h5 class="m-t-0"><?= $code.' <span>'. number_format($discount, 2).'</span>' ?></h5> 
                    <p></p>
                    <h6 class="m-t-0" style="text-decoration: line-through;  color: gray;"><?= $code.' <span >'. number_format($total, 2).'</span>' ?></h6> 
                <?php
                }
                else{ 
                ?>
                    <h5 class="m-t-0"><?= $code.' <span class="total">'.number_format($total, 2).'</span>' ?></h5> 
                <?php
                }
                ?>
            </div>
         </div>
      </div>
    </div>

