<script type="text/javascript">
    function trimByChar(string, character) {
      const first = [...string].findIndex(char => char !== character);
      const last = [...string].reverse().findIndex(char => char !== character);
      return string.substring(first, string.length - last);
    }
    
    function populateOneTimeDates(pane){
        
        //loop through each date setting
        $('#reminder-onetime a.date-preset').each(function(){
            
            var attr = 'data-remind-val';
            var onetime = calcOneTimeDate($(this).attr(attr), true);
            
            $('a[data-remind-val="' + $(this).attr(attr) + '"] > div.date-select-top > h3').html(onetime[1]);
            $('a[data-remind-val="' + $(this).attr(attr) + '"] > div.date-select-top > span').html(onetime[2]);
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
                
                var duedate = $('#due_date').val();
                
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
        $('#date-display').show();
        $('#date-display h3').html(formatted_date);
        
         //scroll to center
        $("div.date-select").scrollCenter(".btn-success", 300);
        
        $('input[name=onetime-value]').val(formatted_date);
    }
    
    function setReminder(remindtype, customername, duedate, medium){
        
        switch(remindtype){

            case 'onetime':

                var newreminderval = $('input[name=onetime-value]').val();
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
                var remindval = $('#hidden-reminder-item-holder').find('input.reminder-value').val();

                //avoid duplicate values
                if(remindval !== JSON.stringify(reminder)){

                    //encode the object
                    $('#hidden-reminder-item-holder').find('input.reminder-value').val(JSON.stringify(reminder));

                    //add the name
                    $('#hidden-reminder-item-holder').find('span.reminder-customer-names').html(customername);

                    //change the date
                    $('#hidden-reminder-item-holder').find('span.reminder-due-date').html(newreminderval);

                    //change the frequency pronoun
                    $('#hidden-reminder-item-holder').find('span.frequency-pronoun').html(' once on ');

                    //clear repeat units
                    $('#hidden-reminder-item-holder').find('span.repeatunits').html('');

                    var str = '';
                    
                    if(medium.search(':') >= 0){
                        str = 'sms & email';
                    }
                    else{
                        str = 'sms';
                    }
                    
                    $('#hidden-reminder-item-holder').find('span.medium')
                            .html(' by <span style="font-weight: bold">'+ str +'</span>');
                }
            break;

            case "repeat":

                var freq = $('a.btn-repeat-picker.picked').attr('data-remind-val');
                var reminderval = $('input[name=startdate-value]').val();

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
                    units: $('#repeatunits').val()
                };

                //avoid duplicate values
                if(remindval !== JSON.stringify(reminder)){

                    //encode the object
                    $('#hidden-reminder-item-holder').find('input.reminder-value').val(JSON.stringify(reminder));

                    //add the name
                    $('#hidden-reminder-item-holder').find('span.reminder-customer-names').html(customername);

                    //change the date
                    $('#hidden-reminder-item-holder').find('span.reminder-due-date').html(newreminderval);

                    //change the frequency pronoun
                    $('#hidden-reminder-item-holder').find('span.frequency-pronoun')
                                        .html(' '+ freq +'<br/> Starting <span style="font-weight: bold;">'+ reminderval +'</span> to ');

                    $('#hidden-reminder-item-holder').find('span.repeatunits')
                            .html('<br/>Repeat <span style="font-weight: bold">once every ' + reminder.units + ' ' + $('input[name=repeat-value]').val() + '</span>');

                    var str = '';
                    
                    if(medium.search(':') >= 0){
                        str = 'sms & email';
                    }
                    else{
                        str = 'sms';
                    }
                    
                    $('#hidden-reminder-item-holder').find('span.medium')
                            .html(' by <span style="font-weight: bold">'+ str +'</span>');
                }
                break
        }
        
        //get the html
        var html = $('#hidden-reminder-item-holder').html();
        var actual = $('#hidden-reminder-item-holder').find('#actualtext').html();

        //add to reminder container
        $('div.reminder-container > div.row > div.col').html(html);
        $('#remindersummary').val(actual);
    }
    
    $(function(){
        
        //PANEL-3 Reminders 
        $('div.schedule-panel').each(function(){
            
            if($(this).hasClass('active') === false){
                $(this).hide();
            }
        });
        
        //open reminder panel
        $('.open-add-reminder').on('click', function(){
            
            //hide the reminder container
            $('div.reminder-container div.col').hide();
            
            //show panel
            $('#add-reminder-panel').slideDown('fast');
        });
        
        //close reminder panel
        $('#close-reminder-panel').on('click', function(){
            
            if($('div.open-add-reminder div.col').html() !== ''){
                
                //hide reminder panel
                $('#add-reminder-panel').slideUp('fast');
                
                //check if reminder is hidden
                if($('div.open-add-reminder div.col').is(':hidden')){
                    $('div.open-add-reminder div.col').show();
                }
            }
        });
        
        //switch between reminder types
        $('.reminder-type').on('click', function(){
            var setup = $(this).attr('for');
            var type = $('#' + setup).val();
            
            //hide all panels
            $('div.schedule-panel').hide();
            
            //show other panel
            $('#reminder-' + type).show();
        });
        
        //onetime datepicker
        $('a.btn-onetime-picker').on('click', function(){
            
            if($(this).attr('id') !== 'general-onetime-date-picker'){
                
                //remove the success
                $('#reminder-onetime .date-select a').each(function(){                
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
                $('#save-reminder').removeClass('disabled');
            }
        });
        
        //general date picker
        var dateselect = $('#onetime-date-select').flatpickr({
            dateFormat: 'l, j F Y',
            minDate: "today",
            onOpen:  function(selectedDates, dateStr, instance){
                instance.clear();
            },
            onClose: function(selectedDates, dateStr, instance) {
                
                $('#pick-date-display').hide('fast');
                
                //set the date
                $('#date-display').show();
                $('#date-display h3').html(dateStr);   
                
                $('input[name=onetime-value]').val(dateStr);
                
                //remove the success
                $('.date-select a').each(function(){                
                    if($(this).hasClass('btn-success')){
                        $(this).removeClass('btn-success picked').addClass('btn-basic');
                    }
                });
                
                //add the success
                $('#general-onetime-date-picker').removeClass('btn-basic').addClass('btn-success picked');
                
                //enable save button
                $('#save-reminder').removeClass('disabled');
            }
        });
        
        //one time date flatpickr
        $('#general-onetime-date-picker').on('click', function(){
            $('#pick-date-display').show('fast');
                
            //add the success
            $(this).addClass('btn-success picked');
            dateselect.open();
        });
        
        //general date picker
        var startdateselect = $('#repeat-date-select').flatpickr({
            dateFormat: 'l, j F Y',
            minDate: "today",
            onOpen:  function(selectedDates, dateStr, instance){
                instance.clear();
            },
            onClose: function(selectedDates, dateStr, instance) {
                $('#start-date-input').hide();
                
                //set the date
                $('#repeat-date-flatpickr span.startdate').html('Start reminders on ' + dateStr);  
                $('input[name=startdate-value]').val(dateStr);
            }
        });
        
        //repeat date flatpickr
        $('#repeat-date-flatpickr').on('click', function(){
            
            //show div
            $('#start-date-input').show();
            
            //add the success
            startdateselect.open();
        });
        
        //repeat datepicker
        $('a.btn-repeat-picker').on('click', function(){
            
            //remove the success
            $('a.btn-repeat-picker').each(function(){                
                if($(this).hasClass('btn-success')){
                    $(this).removeClass('btn-success picked').addClass('btn-basic');
                }
            });
            
            //add the success
            $('a.btn-start-repeat').removeClass('disabled');
            $(this).removeClass('btn-basic').addClass('btn-success picked');
            
            //show the start-repeat div
            $('div.start-repeat-div').show();
            
            //change the time unit
            switch($(this).attr('data-remind-val')){
                
                case "daily":
                    $('#repeat-value').val('days');
                    $('span.time-units').html('Days');
                    break;
                    
                case "weekly":
                    $('#repeat-value').val('weeks');
                    $('span.time-units').html('Weeks');
                    break;
                    
                case "monthly":
                    $('#repeat-value').val('months');
                    $('span.time-units').html('Months');
                    break;
                    
                case "yearly":
                    $('#repeat-value').val('years');
                    $('span.time-units').html('Years');
                    break;
            }
            
            //enable save button
            $('#save-reminder').removeClass('disabled');
        });
        
        //save reminder
        $('#save-reminder').on('click', function(){
            
            //get reminder type
            var remindtype = $('input[name=reminders_setup]:checked').val();
            
            //get the picked time interval
            if($('#reminder-'+remindtype+' a.btn-'+remindtype+'-picker').hasClass('picked')){
                
                //get the customer name
                var customername = $('#nf_customername').val();
                var duedate = $('#due_date').val();
                var medium = $('#medium').val();
                
                //switch reminder type
                setReminder(remindtype, customername, duedate, medium);
                
                //show next button
                if(localStorage.getItem('create:followup:mode') !== 'complete'){
                    $('#pane-buttons .next').show();
                }
            
                //hide reminder panel
                $('#add-reminder-panel').slideUp('fast');
                
                //check if reminder is hidden
                if($('div.open-add-reminder div.col').is(':hidden')){
                    $('div.open-add-reminder div.col').show();
                }
            }
            else {
                
                //show reminder interval
                $('div.reminder-notice').show();
            }
        });
        
        //close reminder notice
        $('div.reminder-notice a.closed').on('click', function(){
            $('div.reminder-notice').hide();
        });
        
        //delete reminder item
        $(document).on('click', 'a.delete-reminder-item', function(){
            
            //clear the reminder container
            $('div.reminder-container div.col').html('');
            
            //show reminder panel
            $('#add-reminder-panel').slideDown('fast');
            
            //hide again
            $('#pane-buttons a.nextslide').hide();
        });
    });
</script>