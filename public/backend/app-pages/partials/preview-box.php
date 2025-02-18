<script type="text/javascript">
$(function(){
    
    // element to detect scroll direction of
    const $el = $('#dashboard #preview-body');
    var title = '#preview-header #title-due-date';
    var body = '#preview-body';

    // initialize last scroll position
    let lastY = $el.scrollTop();

    $el.on('scroll',function(){

        // get current scroll position
        const currY = $el.scrollTop();

        // determine current scroll direction
        const y = (currY > lastY) ? 'down' : ((currY === lastY) ? 'none' : 'up');

        // do something here...
        if(y == 'up'){

            //check visibility
            if(!$(title).is(':visible')){
                $(title).show('fast');
                $(body).css('height', '35vh');
            }
        }
        else{
            
            //check visibility
            if($(title).is(':visible')){
                $(title).hide('fast');
                $(body).css('height', '57vh');
            }
        }

        // update last scroll position to current position
        lastY = currY;


    });
});
</script>
<div id="preview-box" style="display: none;">
    <!--Customer Name-->
    <div id="preview-header" class="enlarged">
        <div class="row" id="customer-row">
            <div class="col text-left m-t-10">
                <div class="title p-t-5" data-map-to="customer"></div>
            </div>
            <div class="col-1 text-right m-t-10 m-r-20 p-t-5">
                <!--<i class="fas fa-2x fa-times close-preview" style="opacity: 0.7"></i>-->
                <i class="ti-close close-preview" style="font-weight: bold;"></i>
            </div>
        </div>
        <div id="title-due-date">
            <hr>
            <!--Title Full Amount-->
            <div class="row">
                <div class="col text-center p-0 m-0">
                    <div class="panel-heading p-b-0 p-t-0" data-map-to="title"></div>
                </div>
            </div>
            <div class="row">
                <div class="col text-center">                
                    <div class="cost m-t-15" data-map-to="full_amount"></div>
                </div>
            </div>

            <!--Due Date-->
            <div class="row">
                <div class="col text-center m-t-0">
                    <div class="duedate m-t-5" style="font-weight: normal" data-map-to="due_date"></div>
                </div>
            </div>
        </div>
    </div>
    <hr class="m-t-0 m-b-5">

    <div id="preview-body">
        
        <!--SMS Email-->
        <div class="row hide-on-minimize" style="">
            <div class="col">
                <span class="app-label">
                    <i class="zmdi zmdi-comments m-r-5"></i> 
                    SMS <br>
                </span>
                <span data-map-to="sms"></span>
            </div>
            <div class="col text-right">
                <span class="app-label">
                    <i class="zmdi zmdi-email-open m-r-5"></i> 
                    Email <br>
                </span>
                <span data-map-to="email"></span>
            </div>
        </div>

        <!-- Delivery Report -->
        <hr class="hide-on-minimize" style="">
        <div class="row hide-on-minimize" style="">
            <div class="col">
                <span data-map-to="deliveryreport"></span>
            </div>
        </div>

        <!--Reminder Type-->
        <hr class="hide-on-minimize" style="">
        <div class="row hide-on-minimize" style="">
            <div class="col">
                <span data-map-to="remindertype"></span>
            </div>
        </div>
        <hr>

        <!--Next Last FollowUp-->
        <div class="row">
            <div class="col text-left">
                <span class="app-label" style="text-transform: capitalize; font-size: 14px">
                    <i class="zmdi zmdi-alarm-check m-r-5"></i> 
                    Next Follow-up: 
                </span>
                <span data-map-to="next"></span>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col text-left">
                <span class="app-label" style="text-transform: capitalize; font-size: 14px">
                    <i class="far fa-calendar-times m-r-5"></i>
                    Last Sent: 
                </span>
                <span data-map-to="last"></span>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col text-left">
                <span class="app-label" style="text-transform: capitalize; font-size: 14px">
                    <i class="fa fa-bullhorn m-r-5"></i>
                </span>
                <span data-map-to="start_on"></span>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col">
                <span class="app-label">
                    Created At<br>
                </span>
                <span data-map-to="created_at"></span>
            </div>
            <div class="col p-r-30 text-right">
                <span class="app-label">
                    Last Modified <br>
                </span>
                <span data-map-to="modified_at"></span>
            </div>
        </div>
        <hr>
    </div>
    <!--Icons-->
    <div class="row">
        <div class="col">
            <div class="tools" data-map-to="menu"></div>
        </div>
    </div>
    <div class="notice" data-map-to="test-notice"></div>    

    <!--Open FollowUp-->
    <div id="more-details-btn" class="btn-holder open">
        <a class="btn btn-block btn-primary ui-link open-followup" data-followup-id="">
            View Messages
            <i class="fas fa-external-link-alt m-l-10"></i> 
        </a>
    </div>
</div>

