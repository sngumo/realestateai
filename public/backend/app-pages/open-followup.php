<?php
require_once ABSOLUTE_PUBLIC_PATH .DS. 'backend' .DS. 'app-pages' .DS. 'scripts' .DS. 'open-followup-js.php';
?>
<div role="main">
    <div id="open-followup-container" class="preview">
        
        <div class="preview-header">
            <div class="container">
                
                <!--Customer and status-->
                <div class="p-0 m-0 text-right">
                    <a href="#dashboard" data-transition="slidefade" data-direction="reverse" class="back-btn shadow">
                        <i class="fas fa-times close-preview" style="font-weight: bold;"></i>
                    </a>
                </div>
                <div id="customer-and-status" class="row">
                    <div class="col customer-name p-0 m-0">
                        <div class="title inline-edit" data-map-to="customer"></div>
                    </div>
                    <div class="col-4">
                        <a class="edit-mode">
                            <i class="fa fa-edit" style="font-weight: bold;"></i>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="floating-box">
                        
                        <!--Title Due Date-->
                        <div class="row">
                            <div class="col text-center p-0 m-0">
                                <div class="panel-heading m-b-5 p-b-5 p-l-0 inline-edit" 
                                     data-map-to="title">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <div class="cost text-center p-0 m-0 inline-edit" 
                                     data-map-to="full_amount">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col text-center">
                                <div class="duedate m-t-5 hide-on-minimize inline-edit" 
                                     style="font-weight: normal; display: none;" 
                                     data-map-to="due_date">
                                </div>
                           </div>
                        </div>                          
                        <div class="row hide-on-minimize" style="display: none;">
                            <div class="col text-center">
                                <a class="btn btn-sm btn-transparent-outline"
                                   data-map-to="status">
                                </a>
                            </div>
                        </div>
                        
                        <!--Icons-->
                        <div class="notice" data-map-to="test-notice"></div>
                        <div id="icon-list-holder" class="row m-t-20">
                            <div class="col">
                                <div class="tools" data-map-to="menu"></div>
                            </div>
                        </div>
                        
                        <!--Next Last FollowUp-->
                        <hr class="hide-on-minimize" style="display: none">
                        <div class="row hide-on-minimize" style="display: none">
                            <div class="col">
                                <span class="app-label">
                                    <i class="zmdi zmdi-alarm-check m-r-5"></i> 
                                    Next Follow-up: <br/>
                                </span>
                                <span data-map-to="next"></span>
                            </div>
                            <div class="col text-right">
                                <span class="app-label">
                                    <i class="far fa-calendar-times m-r-5"></i>
                                    Last Sent: <br/>
                                </span>
                                <span data-map-to="last"></span>
                            </div>
                        </div>
                        <!--SMS Email-->
                        <hr class="hide-on-minimize" style="display: none">
                        <div class="row hide-on-minimize" style="display: none">
                            <div class="col">
                                <span class="app-label">
                                    <i class="zmdi zmdi-comments m-r-5"></i> 
                                    SMS <br/>
                                </span>
                                <span class="inline-edit" data-map-to="sms"></span>
                            </div>
                        </div>
                        <hr class="hide-on-minimize" style="display: none">
                        <div class="row hide-on-minimize" style="display: none">
                            <div class="col text-left">
                                <span class="app-label">
                                    <i class="zmdi zmdi-email-open m-r-5"></i> 
                                    Email <br/>
                                </span>
                                <span class="inline-edit" data-map-to="email"></span>
                            </div>
                        </div>
                        
                        <!--Reminder Type-->
                        <hr class="hide-on-minimize" style="display: none">
                        <div class="row hide-on-minimize" style="display: none">
                            <div class="col">
                                <span class="inline-edit" data-map-to="remindertype"></span>
                            </div>
                        </div>
                        
                        <!--start date-->
                        <hr class="hide-on-minimize" style="display: none">
                        <div class="row hide-on-minimize" style="display: none">
                            <div class="col text-left">
                                <span class="app-label" style="text-transform: capitalize; font-size: 14px">
                                    <i class="fa fa-bullhorn m-r-5"></i>
                                </span>
                                <span data-map-to="start_on"></span>
                            </div>
                        </div>
                        
                        <!--Timestamps-->
                        <hr class="hide-on-minimize" style="display: none">
                        <div class="row hide-on-minimize" style="display: none">
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
                        
                        <!--Scroll Down-->
                        <div class="scroll-down text-center">
                            <a class="m-t-10">
                                <i class="fas fa-2x fa-chevron-down"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--Tabs-->
        <div class="preview-body">
            
        </div>
        
        <!--Footer-->
        <div class="preview-footer" style="display: none;">
            <div class="row">
                <div id="tab-btn-holder" class="col"></div>
            </div>
            <div class="row">
                <div class="col p-0" data-map-to="main_btn"></div>
            </div>
        </div>
    </div>
</div>

<!--Inline Editor-->
<div id="inline-overlay" class="inline-editor-div" style="display: none"></div>
<div id="inline-edit-section" class="container-fluid inline-editor-div m-t-20" style="display: none">
    <div class="inline-content">
        <form rel="external" id="inline-edit-form">
            <input id="active-element" name="active-element" type="hidden" value="inline-edit-element-1">
            <input id="original-value" name="original-value" type="hidden" value="">
            <div class="row p-t-10">
                <div class="col p-10">
                    <input type="text" name="new-value" id="new-value" data-role="none" value="" class="form-control heading-edit text m-b-10">
                </div>
            </div>
            <div class="row">
                <div class="col p-0">
                    <a class="btn btn-secondary actn-cancel">
                        <i class="fa fa-remove"></i> Cancel
                    </a>
                </div>
                <div class="col p-0">
                    <a rel="external" class="btn btn-primary actn-save">
                        <i class="fa fa-check"></i> Save
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

