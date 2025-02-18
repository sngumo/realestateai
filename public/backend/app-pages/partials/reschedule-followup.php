<?php
    require ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'scripts' .DS. 'reschedule-followup-js.php';
?>
<div id="hidden-reminder-item-holder" style="display: none;">
    <input class="reminder-value" type="hidden" name="reminderval" value="" />
    <div class="row reminder-item m-t-10 m-b-20">
        <div class="col-2 p-l-5">
            <i class="zmdi fa-2x zmdi-alarm-check"></i>
        </div>
        <div class="col-2">
            <a class="delete-reminder-item pointer">
                <i class="ion-ios7-trash fa-2x"></i>
            </a>
        </div>
    </div>
</div>
<div class="section-body" id="">
    <input id="remindersummary" type="hidden" name="remindersummary" value="" />
    
    <!-- Due date -->
    <div class="form-group">
        <div class="row">
            <div class="col">
                <h4 class="m-t-0">Due date of payment</h4>
            </div>
        </div>
        <div class="row">
            <div id="due-date-wrapper" class="col date-wrapper" data-validate="required">
                <i class="far fa-calendar-times"></i>
                <?= $due_date ?>
            </div>
            <div id="due-date-display" style="display: none;">
                <i class="far fa-calendar-times"></i>
                <span></span>
            </div>
        </div>
    </div>
    
    <!--Reminder Schedule-->
    <div class="reminder-container open-add-reminder pointer container-fluid">
        <div class="row">
            <div class="col"></div>
        </div>
    </div>
    
    <!-- Add Reminder Panel-->
    <div id="add-reschedule-reminder-panel" class="panel panel-transparent no-shadow p-0 p-t-10">
        <div class="row">
            <div class="col big-toggle m-b-10">
                <div class="switch-toggle switch-candy large-9 columns">
                    <?= $reminders_setup_onetime ?>
                  <label for="reminders_setup_onetime" id="label_reminders_setup_onetime" class="reminder-type">One Time</label>
                    <?= $reminders_setup_repeat ?>
                  <label for="reminders_setup_repeat" id="label_reminders_setup_repeat" class="reminder-type">Repeat</label>
                  <a></a>
                </div>
            </div>
        </div>
        <div class="reminder-notice"  style="display: none">
            <div class="row">
                <div class="col">
                    <div class="kode-alert kode-alert-icon alert6-light btn-rounded">
                        <i class="fa fa-lock"></i>
                        <a href="#" class="closed p-t-10 p-r-10">×</a>
                        Please select a reminder time interval
                    </div>
                </div>
            </div>
        </div>
        <!-- One Time -->
        <div id="reminder-onetime" class="schedule-panel active">
            <input type="hidden" name="onetime-value" id="onetime-value" value="" />
            <div class="row">
                <div class="col">
                    <div class="row">
                        <div class="col text-center">
                            <p class="m-b-0">Send reminder once on</p>
                        </div>
                    </div>
                    <div id="date-display" class="row">
                        <div class="col text-center">
                            <h3 class="m-t-0"> ---- ---- ----</h3>
                        </div>    
                    </div>
                    <div id="pick-date-display" class="row" style="display: none;">
                        <div class="col text-center">
                            <input type="text" placeholder="Select Date.." id="onetime-date-select">
                        </div>    
                    </div>
                    <div class="row m-t-10">
                        <div class="col">
                            <div class="date-select">
                                <ul class="list-inline">
                                    <li>
                                        <a id="general-onetime-date-picker" href="#" data-toggle 
                                           data-remind-val="custom-date" 
                                           class="btn btn-block btn-rounded btn-light btn-onetime-picker">
                                            <div class="date-select-top">
                                                <i class="far fa-2x fa-calendar-check"></i>
                                            </div>
                                            <div class="date-txt">
                                                Pick Date
                                            </div>
                                        </a>   
                                    </li>
                                    <li>
                                        <a href="#" data-remind-val="on-due-date" class="btn date-preset btn-onetime-picker btn-rounded btn-basic">
                                            <div class="date-select-top">
                                                <span>Mar</span>
                                                <h3>13</h3>
                                            </div>
                                            <div class="date-txt">
                                                Due Date
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" data-remind-val="tomorrow" class="btn date-preset btn-onetime-picker btn-rounded btn-basic">
                                            <div class="date-select-top">
                                                <span>Mar</span>
                                                <h3>13</h3>
                                            </div>
                                            <div class="date-txt">
                                                Tomorrow 
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" data-remind-val="next-week" class="btn date-preset btn-onetime-picker btn-rounded btn-basic">
                                            <div class="date-select-top">
                                                <span>Mar</span>
                                                <h3>13</h3>
                                            </div>
                                            <div class="date-txt">
                                                Next week
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" data-remind-val="next-month" class="btn date-preset btn-onetime-picker btn-rounded btn-basic">
                                            <div class="date-select-top">
                                                <span>Mar</span>
                                                <h3>13</h3>
                                            </div>
                                            <div class="date-txt">
                                                Next Month
                                            </div>
                                        </a>    
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!--Repeat-->
        <div id="reminder-repeat" class="schedule-panel">
            <input id="repeat-value" type="hidden" name="repeat-value" value="">
            <div class="row">
                <div class="col">
                    <p>Send follow-up reminders every</p>
                    <div class="date-select m-b-10">
                        <ul class="list-inline">
                            <li>
                                <a href="#" data-remind-val="daily" class="btn btn-repeat-picker btn-rounded btn-basic">
                                    Daily
                                </a>
                            </li>
                            <li>
                                <a href="#" data-remind-val="weekly" class="btn btn-repeat-picker btn-rounded btn-basic">
                                    Weekly
                                </a>
                            </li>
                            <li>
                                <a href="#" data-remind-val="monthly" class="btn btn-repeat-picker btn-rounded btn-basic">
                                    Monthly
                                </a>
                            </li>
                            <li>
                                <a href="#" data-remind-val="yearly" class="btn btn-repeat-picker btn-rounded btn-basic">
                                    Yearly
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="row start-repeat-div">
                <div class="col">
                    <div class="row m-b-0">
                        <div class="col">
                            <div class="p-t-0 m-t-0  m-b-10">
                                <div id="start-date-input" style="display:none">
                                    <input type="text" placeholder="Select Date.." id="repeat-date-select">
                                </div>
                                <a id="repeat-date-flatpickr" class="btn btn-rounded btn-light btn-start-repeat repeat-interval pointer disabled" href="#">
                                    <i class="ion-calendar"></i>
                                    <input class="startdate-value" type="hidden" name="startdate-value" value="">
                                    <span class="startdate">Start reminders on -- today --</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="row m-b-10">
                        <div class="col">
                            <a class="btn btn-rounded btn-light btn-start-repeat repeat-interval pointer disabled" 
                               style="width: 100%" href="#">
                                <table>
                                    <tr>
                                        <td>
                                            <i class="ion-clock"></i>
                                        </td>
                                        <td>
                                            <span class="repeat">Repeat every </span>
                                        </td>
                                        <td>
                                            <select name="repeatunits" id="repeatunits" class="m-t-0" data-role="none">
                                            <?php
                                                for($i=1; $i<= 30; $i++){
                                                    echo '<option value="'.$i.'">'.$i.'</option>';
                                                }
                                            ?>
                                            </select>
                                        </td>
                                        <td>
                                            <span class="time-units"> Days</span>                                            
                                        </td>
                                    </tr>
                                </table>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group bottom-btn-row">
            <div class="row">
                <div class="col">
                    <button id="save-reminder" type="button" class="btn btn-success btn-block btn-rounded disabled pull-right">
                        <i class="fa fa-2x falist fa-calendar-times-o "></i>
                        Reschedule
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
