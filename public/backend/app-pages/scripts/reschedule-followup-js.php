<script type="text/javascript">
    var pane = '#followup-tools-panel';
    
    function trimByChar(string, character) {
        const first = [...string].findIndex(char => char !== character);
        const last = [...string].reverse().findIndex(char => char !== character);
        return string.substring(first, string.length - last);
    }
    
    function populateOneTimeDates(){
        
        //loop through each date setting
        $(pane + ' #reminder-onetime a.date-preset').each(function(){
            
            var attr = 'data-remind-val';
            var onetime = calcOneTimeDate($(this).attr(attr), true);
            
            $(pane + ' a[data-remind-val="' + $(this).attr(attr) + '"] > div.date-select-top > h3').html(onetime[1]);
            $(pane + ' a[data-remind-val="' + $(this).attr(attr) + '"] > div.date-select-top > span').html(onetime[2]);
        });
    }
    
    function calcOneTimeDate(interval, setToArray = false){
        
        var today = new Date("<?= date('F j, Y, g:i a', time())  ?>");
        
        if(setToArray){
            var formatted_date = [];
        }
        else{
            var formatted_date = null;
        }
        
        const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        const months = ["January", "February", "March","April", "May", "June", "July", "August", "September", "October", "November", "December"];
        
        switch(interval){
            
            case "on-due-date":
                
                var duedate = $(pane + ' #due_date').val();
                
                if(setToArray === true){
                    var dates = duedate.split(' ');
                    
                    formatted_date[0] = '';
                    formatted_date[1] = trimByChar(dates[1], ',');
                    formatted_date[2] = dates[0];
                }
                else{
                    formatted_date = duedate;
                }
                break;
            
            case "tomorrow":
                
                var tomorrow = new Date(today);
                tomorrow.setDate(today.getDate() + 1);
                
                if(setToArray === true){
                    
                    formatted_date[0] = days[tomorrow.getDay()];
                    formatted_date[1] = tomorrow.getDate();
                    formatted_date[2] = months[tomorrow.getMonth()];
                }
                else{
                    formatted_date = days[tomorrow.getDay()] + ", "+ tomorrow.getDate() +" "+ months[tomorrow.getMonth()] + " " + tomorrow.getFullYear();
                }
                break;
                
            case "next-week":
                
                var nextweek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
                
                if(setToArray){
                    formatted_date[0] = days[nextweek.getDay()];
                    formatted_date[1] = nextweek.getDate();
                    formatted_date[2] = months[nextweek.getMonth()];
                }
                else{
                    formatted_date = days[nextweek.getDay()] + ", "+ nextweek.getDate() +" "+ months[nextweek.getMonth()] + " " + nextweek.getFullYear();
                }
                break;
                
            case "next-month":
                
                if (today.getMonth() === 11) {
                    var nextmonth = new Date(today.getFullYear() + 1, 0, 1);
                } else {
                    var nextmonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
                }
                
                if(setToArray){
                    formatted_date[0] = days[nextmonth.getDay()];
                    formatted_date[1] = nextmonth.getDate();
                    formatted_date[2] = months[nextmonth.getMonth()];
                }
                else{
                    formatted_date = days[nextmonth.getDay()] + ", "+ nextmonth.getDate() +" "+ months[nextmonth.getMonth()] + " " + nextmonth.getFullYear();
                }
                break;
        }
        
        if(setToArray){
            return formatted_date;
        }
        
        //set the formatted date
        $(pane + ' #date-display').show();
        $(pane + ' #date-display h3').html(formatted_date);
        
         //scroll to center
        //$("div.date-select").scrollCenter(".btn-success", 300);
        
        $(pane + ' input[name=onetime-value]').val(formatted_date);
    }
    
    function setRescheduleReminder(remindtype, duedate, medium){
        
        switch(remindtype){

            case 'onetime':

                var newreminderval = $(pane + ' input[name=onetime-value]').val();
                if(newreminderval === ''){
                    newreminderval = duedate;
                }

                //create the reminder object
                var reminder = {
                    type: 'onetime',
                    startdate: newreminderval,
                    duedate: duedate,
                    medium: medium
                };
                
                //get the current reminder value
                var remindval = $(pane + ' #hidden-reminder-item-holder').find('input.reminder-value').val();

                //avoid duplicate values
                if(remindval !== JSON.stringify(reminder)){

                    //encode the object
                    $(pane + ' #hidden-reminder-item-holder').find('input.reminder-value').val(JSON.stringify(reminder));
                }
            break;

            case "repeat":

                var freq = $(pane + ' a.btn-repeat-picker.picked').attr('data-remind-val');
                var reminderval = $(pane + ' input[name=startdate-value]').val();

                //if empty set start to current day
                if(reminderval === ''){
                    reminderval = "<?= date('F j, Y, g:i a', time())  ?>";
                }

                //create the reminder object
                var reminder = {
                    type: 'repeat',
                    startdate: reminderval,
                    duedate: duedate,
                    medium: medium,
                    frequency: freq,
                    units: $(pane + ' #repeatunits').val()
                };

                //avoid duplicate values
                if(remindval !== JSON.stringify(reminder)){

                    //encode the object
                    $(pane + ' #hidden-reminder-item-holder').find('input.reminder-value').val(JSON.stringify(reminder));
                }
                break
        }
    }
    
    function redrawSchedule(response, page){
        
        //get the hidden followup div
        var hidden = $('#dashboard #contacts-' + response.id ).val();
        var data = JSON.parse(hidden);
        
        //replace the due_date
        data.due_date = response.data.due_date;
        
        //replace the remindertype
        data.remindertype = response.data.remindertype;
        
        //replace the hidden value
        $('#dashboard #contacts-' + response.id ).val(JSON.stringify(data));
        
        //if dashboard change preview
        if(page == 'dashboard'){
            
            $('#preview-body div[data-map-to="due_date"]').html(response.data.due_date);
            $('#preview-body span[data-map-to="remindertype"]').html(response.data.remindertype);
        }
        else if(page == 'follow-up-opened'){
            
            //open divs
            $('#follow-up-opened .hide-on-minimize').show('fast');
            $('#follow-up-opened .scroll-down a').html('<i class="fas fa-2x fa-chevron-up"></i>');

            //map values
            $('#open-followup-container div[data-map-to="due_date"]').html(response.data.due_date);
            $('#open-followup-container span[data-map-to="remindertype"]').html(response.data.remindertype);
        }
    }
    
    function hydrateRescheduleForm(){
    
        var due = JSON.parse('<?= $due ?>');
        
        //set active panel
        $(pane  + ' div.schedule-panel').removeClass('active').hide();
        $(pane + ' #reminder-'+ due.type).addClass('active').show();
        
        if(due.type == 'repeat'){
            
            var btn = pane + ' a[data-remind-val="'+ due.frequency +'"]';
            
            //remove the success
            $(pane + ' a.btn-repeat-picker').each(function(){                
                if($(btn).hasClass('btn-success')){
                    $(btn).removeClass('btn-success picked').addClass('btn-basic');
                }
            });
            
            //add the success
            $(pane + ' a.btn-start-repeat').removeClass('disabled');
            $(btn).removeClass('btn-basic').addClass('btn-success picked');
            
            //show the start-repeat div
            $(pane + ' div.start-repeat-div').show();
            
            //change the time unit
            switch($(btn).attr('data-remind-val')){
                
                case "daily":
                    $(pane + ' #repeat-value').val('days');
                    $(pane + ' span.time-units').html('Days');
                    break;
                    
                case "weekly":
                    $(pane + ' #repeat-value').val('weeks');
                    $(pane + ' span.time-units').html('Weeks');
                    break;
                    
                case "monthly":
                    $(pane + ' #repeat-value').val('months');
                    $(pane + ' span.time-units').html('Months');
                    break;
                    
                case "yearly":
                    $(pane + ' #repeat-value').val('years');
                    $(pane + ' span.time-units').html('Years');
                    break;
            }
            
            //add startdate
            $(pane + ' #repeat-date-flatpickr span.startdate').html('Start reminders on ' + due.startdate);
            $(pane + ' #repeat-date-flatpickr input.startdate-value').val(due.startdate);
            
            //set unit
            $(pane + ' #repeatunits').val(due.units);
        }
        else if(due.type == 'onetime'){
            
            var btn = pane + ' #reminder-onetime';
            
            $(btn + ' input[name="onetime-value"]').val(due.startdate);
            $(btn + ' #date-display h3').html(due.startdate);
        }
            
        //enable save button
        //$(pane + ' #save-reminder').removeClass('disabled');
    }
    
    $(function(){
        
        //on repeat days select
        $(pane + ' #repeatunits').on('change', function(){
            $(pane + ' #save-reminder').removeClass('disabled');
        });
        
        //open on click
        $(pane + ' #due_date').on('click', function(){
            
            //initialize due date
            var duedate = $(pane + ' #due_date').flatpickr({
                minDate: "today",
                dateFormat: "F j Y",
                onChange: function(selectedDates, dateStr, instance){
                    
                    $(pane + ' #due-date-wrapper').hide('fast');
                    $(pane + ' #due-date-display').show('fast');
                    
                    if(selectedDates.length == 0){
                        
                        var data = JSON.parse($('#reminderdata').val());
                        $(pane + ' #due-date-display span').html('<h5 class="m-t-0 m-b-0 p-t-0 p-b-0">' 
                                + data.duedate + '</h5>');
                        
                        $(pane + ' #save-reminder').removeClass('disabled');
                    }
                },
                onClose: function(selectedDates, dateStr, instance){

                    $(pane + ' #due-date-wrapper').hide('fast');
                    $(pane + ' #due-date-display').show('fast');

                    if(selectedDates.length !== 0){
                        
                        $(pane + ' #due-date-display span').html('<h5 class="m-t-0 m-b-0 p-t-0 p-b-0">' 
                                + dateStr + '</h5>');

                        //repopulate one-time dates
                        populateOneTimeDates();
                        $(pane + ' #save-reminder').removeClass('disabled');
                    }
                },
                onDestroy: function(selectedDates, dateStr, instance){
                    $(pane + ' #due-date-wrapper').hide('fast');
                    $(pane + ' #due-date-display').show('fast');
                    
                    if(selectedDates.length == 0){
                        
                        var data = JSON.parse($('#reminderdata').val());
                        $(pane + ' #due-date-display span').html('<h5 class="m-t-0 m-b-0 p-t-0 p-b-0">' 
                                + data.duedate + '</h5>');
                        $(pane + ' #save-reminder').removeClass('disabled');
                    }
                }
            });
            
            //open date picker
            duedate.open();
        });
        
        //return on click
        $(pane + ' #due-date-display').on('click', function(){

            $(pane + ' #due-date-wrapper').show('fast');
            $(pane + ' #due-date-display').hide('fast');

            //reopen date picker
            var duedate = $(pane + ' #due_date').flatpickr({
                minDate: "today",
                dateFormat: "F j Y",
                onChange: function(selectedDates, dateStr, instance){
                    //console.log('change', dateStr);
                },
                onClose: function(selectedDates, dateStr, instance){

                    $(pane + ' #due-date-wrapper').hide('fast');
                    $(pane + ' #due-date-display').show('fast');

                    $(pane + ' #due-date-display span').html('<h5 class="m-t-0 m-b-0 p-t-0 p-b-0">' 
                            + dateStr + '</h5>');
                    
                    //repopulate one-time dates
                    populateOneTimeDates();
                },
                onDestroy: function(){
                    //console.log('destroy');
                }
            });
            
            duedate.open();
        });
        
        //populate one-time dates
        populateOneTimeDates();
        
        //hide all panels
        $(pane  + ' div.schedule-panel').each(function(){
            
            if($(this).hasClass('active') === false){
                $(this).hide();
            }
        });
        
        //hydrate reschedule form
        hydrateRescheduleForm();
        
        //open reminder panel
        $(pane + ' .open-add-reminder').on('click', function(){
            
            //hide the reminder container
            $(pane + ' div.reminder-container div.col').hide();
            
            //show panel
            $(pane + ' #add-reminder-panel').slideDown('fast');
        });
        
        //close reminder panel
        $(pane + ' #close-reminder-panel').on('click', function(){
            
            if($(pane + ' div.open-add-reminder div.col').html() !== ''){
                
                //hide reminder panel
                $(pane + ' #add-reminder-panel').slideUp('fast');
                
                //check if reminder is hidden
                if($(pane + ' div.open-add-reminder div.col').is(':hidden')){
                    $(pane + ' div.open-add-reminder div.col').show();
                }
            }
        });
        
        //switch between reminder types
        $(pane + ' .reminder-type').on('click', function(){
            
            //clear checked
            $(pane + ' input[name="reminders_setup"]').removeAttr('checked');
            
            var setup = $(this).attr('for');
            var type = $(pane + ' input#' + setup).val();
            $(pane + ' input#' + setup).prop('checked', true);
            
            //manually push the a link
            if(setup == 'reminders_setup_repeat'){
                $(pane + ' .switch-toggle a').css('left', '50%');
            }
            else{
                $(pane + ' .switch-toggle a').removeAttr('style');
            }
            
            //hide all panels
            $(pane + ' div.schedule-panel').hide();
            
            //show other panel
            $(pane + ' #reminder-' + type).show();
        });
        
        //onetime datepicker
        $(pane + ' a.btn-onetime-picker').on('click', function(){
            
            if($(this).attr('id') !== 'general-onetime-date-picker'){
                
                //remove the success
                $(pane + ' #reminder-onetime .date-select a').each(function(){                
                    if($(this).hasClass('btn-success')){
                        $(this).removeClass('btn-success picked').addClass('btn-basic');
                    }
                });

                //add the success
                $(this).removeClass('btn-basic').addClass('btn-success picked');

                //get the data-remind-val attribute
                var remindval = $(this).attr('data-remind-val');
                calcOneTimeDate(remindval);

                //enable save button
                $(pane + ' #save-reminder').removeClass('disabled');
            }
        });
        
        //general date picker
        var dateselect = $(pane + ' #onetime-date-select').flatpickr({
            dateFormat: 'l, j F Y',
            minDate: "today",
            onOpen:  function(selectedDates, dateStr, instance){
                instance.clear();
            },
            onClose: function(selectedDates, dateStr, instance) {
                
                $(pane + ' #pick-date-display').hide('fast');
                
                //set the date
                $(pane + ' #date-display').show();
                $(pane + ' #date-display h3').html(dateStr);   
                
                $(pane + ' input[name=onetime-value]').val(dateStr);
                
                //remove the success
                $(pane + ' .date-select a').each(function(){                
                    if($(this).hasClass('btn-success')){
                        $(this).removeClass('btn-success picked').addClass('btn-basic');
                    }
                });
                
                //add the success
                $(pane + ' #general-onetime-date-picker').removeClass('btn-basic').addClass('btn-success picked');
                
                //enable save button
                $(pane + ' #save-reminder').removeClass('disabled');
            }
        });
        
        //one time date flatpickr
        $(pane + ' #general-onetime-date-picker').on('click', function(){
            $(pane + ' #pick-date-display').show('fast');
                
            //add the success
            $(this).addClass('btn-success picked');
            dateselect.open();
        });
        
        //general date picker
        var startdateselect = $(pane + ' #repeat-date-select').flatpickr({
            dateFormat: 'l, j F Y',
            minDate: "today",
            onOpen:  function(selectedDates, dateStr, instance){
                instance.clear();
            },
            onClose: function(selectedDates, dateStr, instance) {
                $(pane + ' #start-date-input').hide();
                
                //set the date
                $(pane + ' #repeat-date-flatpickr span.startdate').html('Start reminders on ' + dateStr);  
                $(pane + ' input[name=startdate-value]').val(dateStr);
                
                //activate button
                $(pane + ' #save-reminder').removeClass('disabled');
            }
        });
        
        //repeat date flatpickr
        $(pane + ' #repeat-date-flatpickr').on('click', function(){
            
            //show div
            $(pane + ' #start-date-input').show();
            
            //add the success
            startdateselect.open();
        });
        
        //repeat datepicker
        $(pane + ' a.btn-repeat-picker').on('click', function(){
            
            //remove the success
            $(pane + ' a.btn-repeat-picker').each(function(){                
                if($(this).hasClass('btn-success')){
                    $(this).removeClass('btn-success picked').addClass('btn-basic');
                }
            });
            
            //add the success
            $(pane + ' a.btn-start-repeat').removeClass('disabled');
            $(this).removeClass('btn-basic').addClass('btn-success picked');
            
            //show the start-repeat div
            $(pane + ' div.start-repeat-div').show();
            
            //change the time unit
            switch($(this).attr('data-remind-val')){
                
                case "daily":
                    $(pane + ' #repeat-value').val('days');
                    $(pane + ' span.time-units').html('Days');
                    break;
                    
                case "weekly":
                    $(pane + ' #repeat-value').val('weeks');
                    $(pane + ' span.time-units').html('Weeks');
                    break;
                    
                case "monthly":
                    $(pane + ' #repeat-value').val('months');
                    $(pane + ' span.time-units').html('Months');
                    break;
                    
                case "yearly":
                    $(pane + ' #repeat-value').val('years');
                    $(pane + ' span.time-units').html('Years');
                    break;
            }
            
            //enable save button
            $(pane + ' #save-reminder').removeClass('disabled');
        });
        
        //save reminder
        $(pane + ' #save-reminder').on('click', function(){
            
            if($(this).hasClass('disabled')){
                return false;
            }
            
            //get reminder type
            var remindtype = $(pane + ' input[name=reminders_setup]:checked').val();
            var duedate = $(pane + ' #due_date').val();
            
            //get the picked time interval
            if($(pane + ' #reminder-'+remindtype+' a.btn-'+remindtype+'-picker').hasClass('picked')){
                
                //switch reminder type
                setRescheduleReminder(remindtype, duedate);
                
                //throw event
                $.bootstrapGrowl('Saving Schedule', {
                    type: "info",
                    align: "center",
                    offset: {from: 'top', amount: 50},
                    delay: 15000,
                    allow_dismiss: true
                });
            
                var action = $(pane + ' form#reminderform').attr('action');

                //reminder data
                var data = $(pane + ' input[name=reminderval]').val();

                //save reminder
                $.ajax({
                    method: "POST",
                    url: action,
                    data: {
                        reminders: data,
                        duedate: $(pane + ' #due_date').val()
                    }
                })
                .done(function(feedback){

                    var response = JSON.parse(feedback);
                    if(response.status === 1){

                        //redraw schedule
                        redrawSchedule(response, $.mobile.activePage.attr('id'));

                        //activate refresh
                        activateRefresh();
                        
                        //show tooltip
                        $('div.bootstrap-growl').remove();
                        $.bootstrapGrowl('Schedule Updated', {
                            type: "success",
                            align: "center",
                            offset: {from: 'top', amount: 50},
                            delay: 10000,
                            allow_dismiss: true
                        });
                        
                        //disable button
                        $(pane + ' #save-reminder').addClass('disabled');
                        closeToolPanel();
                    }
                    else if(response.status === 0){

                        //error swal
                        swal({
                            title: response.title,
                            text: response.text,
                            type: "error",
                            width: "530px"
                        });
                    }
                });
            }
            else {
                
                //show reminder interval
                $(pane + ' div.reminder-notice').show();
            }
        });
        
        //close reminder notice
        $(pane + ' div.reminder-notice a.closed').on('click', function(){
            $(pane + ' div.reminder-notice').hide();
        });
        
        //set the one time date
        var onetime = $('input#onetime-value').val();
        $(pane + ' #general-onetime-date-picker').addClass('btn-success picked');
        
    });
</script>
