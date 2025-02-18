<script type="text/javascript">
$(function(){

    var page = '#create-follow-up';
    $('[data-pane-no]').on('click', function(){

        var paneno = parseInt($(this).attr('data-pane-no'));
        var active = page + ' [data-pane="' + paneno + '"]';

        //open panel
        $(page + '-panel').openPanelUp();

        //show pane
        changePane(page, active, '', false);

        if(localStorage.getItem('create:followup:mode') === 'complete'){

            //hide finish button
            var finish = page + ' div.form-group.finish';
            if($(finish).is(':visible')){
                $(finish).hide();
            }

            //hide next button
            var next = page + ' div.form-group.next';
            if($(next).is(':visible')){
                $(next).hide();
            }

            //show the save buttom
            if(paneno != 5){
                var pay = page + ' div.form-group.pay';
                $(pay).hide();
                
                var save = page + ' div.form-group.save';
                if($(save).is(':hidden')){
                    $(save).show();
                }
            }
            else{
                
                var save = page + ' div.form-group.save';
                $(save).hide();
                
                var pay = page + ' div.form-group.pay';
                if($(pay).is(':hidden')){
                    $(pay).show();
                }
            }
        }
    });
});
</script>
<div id="draft" class="summary shadow m-t-0" style="display: none;">
    <div class="container">
        <!--Customer and status-->
        <div id="customer-and-status" class="row">
            <div class="col customer-name p-0 m-0">
                <div class="title" data-pane-no="1" data-map-to="#nf_customername">
                    [ customer-name ]
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col p-l-5 p-r-5">

                <!--Title Due Date-->
                <div class="row">
                    <div class="col text-center p-0 m-0">
                        <div class="panel-heading m-b-5 p-b-5 p-l-0" data-pane-no="2" data-map-to="#title">
                            [ purpose-of-payment ]
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="cost text-center p-0 m-0">
                            <span><?= $invoice['currency'] ?></span> 
                            <span data-pane-no="2" data-map-to="#price">[ amount-due ]</span>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col text-center">
                        <div class="duedate m-t-5 " style="font-weight: normal;">
                            <span>Due </span> 
                            <span data-pane-no="3" data-map-to="#due_date">[ due-date ]</span>
                        </div>
                   </div>
                </div>    
                <div class="row hide-on-minimize" style="">
                    <div class="col text-center">
                        <a class="btn btn-sm btn-transparent-outline m-t-5">
                            draft
                        </a>
                    </div>
                </div>

                <!--SMS Email-->
                <hr class="" style="">
                <div class="row " style="">
                    <div class="col">
                        <span class="app-label">
                            <i class="zmdi zmdi-comments m-r-5"></i> 
                            SMS <br>
                        </span>
                        <span data-pane-no="1" data-map-to="#mobile">
                            [ phone-number ]
                        </span>
                    </div>
                </div>
                <hr class="" style="">
                <div class="row " style="">
                    <div class="col text-left">
                        <span class="app-label">
                            <i class="zmdi zmdi-email-open m-r-5"></i> 
                            Email <br>
                        </span>
                        <span data-pane-no="1" data-map-to="#emailaddress">
                            [ email-address ]
                        </span>
                    </div>
                </div>

                <!--Reminder Type-->
                <hr>
                <div class="row " style="">
                    <div class="col">
                        <i class="zmdi zmdi-alarm-check"></i> 
                        <span data-pane-no="4" data-map-to="#remindersummary">
                            [ reminder-schedule ]
                        </span>
                    </div>
                </div>
                <hr>
                <div class="row pay-means-summary" style="display: none;">
                    <div id="pay-means-details" class="col" data-pane-no="5" data-map-to="#pay-means-details">
                    </div>
                    <hr>
                </div>
                <div class="row ">
                    <div class="col">                  
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>