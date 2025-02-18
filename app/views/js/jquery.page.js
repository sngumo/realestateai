/**
 * This is the global page handler for Jenga
 * and will expand to handle the client-side operations for any page
 */

/**
 * Calculates and hides elements based on screen size
 * @returns {undefined}
 */
function hideScreenElements(){

    //check the window width
    var width = $(window).width();

    //set desktop viewport
    if (width > 1024) {
        $('body').attr('environment', 'desktop');

        //hide the tablet and mobile elements
        $('[env="mobile"]').each(function () {
            $(this).hide();
        });
        $('[env="tablet"]').each(function () {
            $(this).hide();
        });
    } 
    else {

        //set the mobile viewport
        $('body').attr('environment', 'mobile');

        //hide the desktop and mobile elements
        $('[env="desktop"]').each(function () {
            $(this).hide();
        });
        $('[env="tablet"]').each(function () {
            $(this).hide();
        });
    }
}

/**
 * Reload page content
 * @param {type} response
 * @returns {undefined}
 */
function repopulatePage(response) {

    var page = $('div.jng-main-page');

    //clear page
    $('#jng-progressbar').remove();
    page.html('');

    //add new content
    page.html(response);
}

function JengaPage(settings) {

    this.settings = settings;
    this.modal = '#addeditmodal';
    this.progressbar = '<div id="jng-progressbar" class="progress">\n\
                            <div class="indeterminate"></div>\n\
                        </div>';
    this.reloadpage = false;

    /**
     * Block any user input during operation
     * @param {type} message
     * @returns {unresolved}
     */
    this.blockIO = function (message = 'loading...') {

        return swal({
            title: '',
            text: message,
            imageUrl: this.settings.viewpath + '/loading/fups-loader.gif',
            imageSize: '150x150',
            showCancelButton: false,
            showConfirmButton: false,
            width: '200px',
            allowOutsideClick: false,
            customClass: 'sweetalert-sm'
        });
    };

    /**
     * Show progress bar
     * @returns {undefined}
     */
    this.showProgress = function () {
        $('body').prepend(this.progressbar);
    };

    /**
     * Cancel progress bar
     * @returns {undefined}
     */
    this.cancelProgress = function () {
        $('#jng-progressbar').remove();
    };

    this.reloadCurrentPage = function(){

        //block
        this.blockIO('Reloading Page');

        //get the url in use
        var currentUrl = settings.base + '/ajax' + settings.current;

        //start ajax request
        $.ajax({
            method: "GET",
            url: currentUrl,
            success: function (response) {

                swal.close();
                repopulatePage(response);
                hideScreenElements();
            }
        });
    };

    /**
     * Reload the main section of the page via ajax
     * @returns {undefined}
     */
    function reloadMainPage() {

        //get the url in use
        var currentUrl = settings.base + '/ajax' + settings.current;

        //start ajax request
        $.ajax({
            method: "GET",
            url: currentUrl,
            beforeSend: function () {
                $('body').prepend(this.progressbar);
            },
            success: function (response) {
                repopulatePage(response);
                hideScreenElements();
            }
        });
    }
    
    /**
     * Save from localStorage
     * @param {type} url
     * @param {type} key
     * @param {type} message
     * @param {type} event
     * @param {type} method
     * @returns {Boolean}
     */
    this.saveFromLocalStorage = function (url, key, message = 'Saving Form...', event = null, method = 'POST') {

        //check if click event is set
        if (event !== null) {
            event.preventDefault();
        }

        //block IO
        this.blockIO(message);

        //get data
        var data = localStorage.getItem(key);

        //save data
        var save = '';
        $.ajax({
            url: url,
            method: method,
            cache: false,
            async: false,
            data: {
                invoice: data
            },
            success: function (response) {
                save = response;
            },
            error: function () {
                save = false;
            }
        });

        return save;
    };

    /**
     * Handles overlay form save
     * @param {type} id
     * @param {type} message
     * @param {type} validate
     * @param {type} event
     * @param {type} reload
     * @param {type} trigger
     * @returns {undefined}
     */
    this.saveFromOverlay = function (id, message = 'Saving Form...',
            validate = true, event = null, reload = true, trigger = null) {

        //check if click event is set
        if (event !== null) {
            event.preventDefault();
        }

        //get form by id
        var form = $(id);

        //validate form
        if (validate) {
            form.parsley().validate();
            if (form.parsley().isValid() === false) {
                return;
            }
        }

        //prevent submit
        form.submit(function (event) {
            event.preventDefault();
        });

        //block IO
        this.blockIO(message);

        //get data
        var data = form.serializeArray();
        var action = form.attr('action');
        var method = form.attr('method');

        //save data
        if (reload === true) {
            this.executePageAction(action, method, data, reload);
        } else {
            this.executePageAction(action, method, data, false, trigger);
        }
    };

    this.saveFromOverlayAndAlert = function (id, message = 'Saving Form...', validate = true) {

        //check if click event is set
        if (event !== null) {
            event.preventDefault();
        }

        //get form by id
        var form = $(id);

        //validate form
        if (validate) {
            form.parsley().validate();
            if (form.parsley().isValid() === false) {
                return;
            }
        }

        //prevent submit
        form.submit(function (event) {
            event.preventDefault();
        });

        //block IO
        this.blockIO(message);

        //get data
        var data = form.serializeArray();
        var action = form.attr('action');
        var method = form.attr('method');

        $.ajax({
            url: action,
            method: method,
            cache: false,
            data: data,
            success: function (data) {

                var response = JSON.parse(data);
                if (response.status === 1) {

                    //success swal
                    swal({
                        title: response.title,
                        text: response.text,
                        type: "success",
                        button: "Ok",
                        width: "530px"
                    });

                    //close modals
                    $('.modal').modal('hide');
                } else if (response.status === 0) {

                    //error swal
                    swal({
                        title: response.title,
                        text: response.text,
                        type: "error",
                        width: "530px"
                    });
                } else if (response.status === 2) {

                    //warning swal
                    swal({
                        title: response.title,
                        text: response.text,
                        type: "warning",
                        width: "530px"
                    });
                } else if (response.status === 3) {

                    //warning swal
                    swal({
                        title: response.title,
                        text: response.text,
                        type: "info",
                        width: "530px"
                    });
                }
            }
        });
    }

    /**
     * Save form and trigger event
     * @param {type} trigger
     * @param {type} id
     * @param {type} message
     * @param {type} event
     * @returns {undefined}
     */
    this.saveFormAndTrigger = function (trigger, id, message = 'Saving Form...', event = null) {
        this.saveFromOverlay(id, message, true, event, false, trigger);
    };

    /**
     * Handles saving code
     * @param {type} id
     * @param {type} message
     * @param {type} validate
     * @param {type} event
     * @returns {undefined}
     */
    this.saveFromCodeMirror = function (id, message = 'Saving Form...', validate = true, editor) {

        //check if click event is set
        if (event !== null) {
            event.preventDefault();
        }

        var form = $(id);

        //validate form
        if (validate) {
            form.parsley().validate();
            if (form.parsley().isValid() === false) {
                return;
            }
        }

        //prevent submit
        form.submit(function (event) {
            event.preventDefault();
        });

        //block IO
        this.blockIO(message);

        //get data
        var data = form.serializeArray();

        //add the edited code
        data.push({"name": "editor", "value": editor.getValue()});

        var action = form.attr('action');
        var method = form.attr('method');

        //save data
        this.executePageAction(action, method, data);
    };

    /**
     * Executes the clicked link
     * @param {type} event
     * @param {type} reload
     * @param {type} trigger
     * @returns {undefined}
     */
    this.executeLinkAction = function (event, reload = true, trigger = 'jng.link.complete', element = this) {

        event.preventDefault();
        if(event.hasOwnProperty('path')){
            
            var href = event.path[0].href;

            //if undefined get parent
            if (typeof href === 'undefined') {
                href = $(element).attr('href');
            }
        }
        else{
            var href = $(event.target).attr('href');
        }

        this.blockIO('Operation in Progress ...');
        this.executePageAction(href, "GET", null, reload, trigger);
    };

    /**
     * Execute page action
     * @param {type} action
     * @param {type} method
     * @param {type} data
     * @param {type} reload
     * @param {type} trigger
     * @returns {undefined}
     */
    this.executePageAction = function (action, method, data = null, reload = true, trigger = 'jng.page.complete') {

        if (reload === true) {
            $.ajax({
                url: action,
                method: method,
                cache: false,
                data: data,
                success: this.handleSave
            });
        } else {
                $.ajax({
                    url: action,
                    method: method,
                    cache: false,
                    data: data,
                    success: function (response) {
                        $(document).trigger(trigger, [response]);
                    }
                });
        }
    };

    /**
     * Execute link and create toast response
     * @param {type} event
     * @returns {undefined}
     */
    this.passiveExecute = function (event) {

        var href = event.path[1].href;

        //if undefined then get the target
        if (href === undefined) {
            var target = event.target;
            var href = $(target).attr('href');
        }

        //stop event propagation
        event.preventDefault();
        event.stopPropagation();

        //load ajax data
        $.ajax({
            url: href,
            method: "GET",
            beforeSend: function () {
                $('body').prepend('<div id="jng-progressbar" class="progress"> \
                                        <div class="indeterminate"></div> \
                                    </div>');
            }
        })
                .done(function (data) {

                    var response = JSON.parse(data);
                    if (response.status === 1) {
                        toast(response.title, response.text, "success");
                        reloadMainPage();
                    } else if (response.status === 0) {
                        toast(response.title, response.text, "error");
                    }
                });
    };

    /**
     * Creates a page toast
     * @param {type} title
     * @param {type} message
     * @param {type} type
     * @param {type} position
     * @returns {unresolved}
     */
    function toast(title, message, type = "info", position = "toast-top-right") {

        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "positionClass": position,
            "preventDuplicates": false,
            "showDuration": "100300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "10000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        return toastr[type](title, message);
    }
    ;

    this.toast = toast;

    /**
     * Handle save response
     * @param {type} resp
     * @returns {Boolean}
     */
    this.handleSave = function (resp) {

        var response = JSON.parse(resp);
        if (response.status === 1) {

            //success swal
            swal({
                title: response.title,
                text: response.text,
                type: "success",
                button: "Ok",
                width: "530px"
            },reloadMainPage(resp));

            //close modals
            $('.modal').modal('hide');
        } else if (response.status === 0) {

            //error swal
            swal({
                title: response.title,
                text: response.text,
                type: "error",
                width: "530px"
            });
        } else if (response.status === 2) {

            //warning swal
            swal({
                title: response.title,
                text: response.text,
                type: "warning",
                width: "530px"
            });
        } else if (response.status === 3) {

            //warning swal
            swal({
                title: response.title,
                text: response.text,
                type: "info",
                width: "530px"
            });
        }
    };

    /**
     * Create a bootstrap dialog modal
     * @param {type} settings
     * @param {type} content
     * @param {type} buttonslist
     * @returns {String}
     */
    this.dialogModal = function (settings, content, buttonslist) {

        var buttons = this._createModalButtons(buttonslist);
        var modal = '<div class="modal-header"> \
                        <h4 class="modal-title" id="' + settings.id + 'Label">' + settings.title + '</h4> \
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> \
                    </div> \
                    <div class="modal-body"> \
                        <div class="modal-body dialog"> ' + content + '</div> \
                    </div> \
                    <div class="modal-footer">' + buttons + '</div>';

        return modal;
    };

    /**
     * Confirm link action
     * @param {type} event
     * @param {type} question
     * @param {type} reload
     * @param {type} trigger
     * @returns {Boolean}
     */
    this.confirmAction = function (event, question, reload = true, trigger = 'jng.page.complete', element = this) {

        event.preventDefault();
        
        if(event.hasOwnProperty('path')){
            
            var href = event.path[0].href;
            var onclick = '';

            //if undefined get parent
            if (typeof href === 'undefined') {
                href = $(element).attr('href');
            }
        }
        else{
            
            var href = $(event.target).attr('href');
            if(typeof href === 'undefined'){
                href = $(event.target).parent('a').attr('href');
            }
        }
        
        if (trigger !== 'jng.direct.link') {
            var onclick = 'onclick="jng.executeLinkAction(event, ' + reload + ', \'' + trigger + '\' )"';
        }

        if (!$('#dataConfirmModal').length) {

            $('body').append('<div id=\"dataConfirmModal\" class=\"modal confirm-action fade\" role=\"dialog\" aria-labelledby=\"dataConfirmLabel\" aria-hidden=\"true\"> \
                                <div class=\"modal-dialog\"> \
                                    <div class=\"modal-content\"> \
                                        <div class=\"modal-header\"> \
                                            <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-hidden=\"true\">×</button> \
                                            <h3 id=\"dataConfirmLabel\" class="modal-title">Please Confirm Action</h3> \
                                        </div>\n\
                                        <div class=\"modal-body\"></div> \
                                        <div class=\"modal-footer\"> \
                                            <button class=\"btn\" data-dismiss=\"modal\" aria-hidden=\"true\">Cancel</button> \
                                            <a ' + onclick + ' class=\"btn btn-primary\" id=\"dataConfirmOK\">OK</a> \
                                        </div> \
                                    </div> \
                                </div> \
                            </div>');
        }

        $('#dataConfirmModal').find('.modal-body').html('<span class="confirm-txt">' + question + '</span>');
        $('#dataConfirmOK').attr('href', href);
        $('#dataConfirmModal').modal({show: true});

        return false;
    };
    
    /**
     * Confirm link action
     * @param {type} event
     * @param {type} question
     * @param {type} reload
     * @param {type} trigger
     * @returns {Boolean}
     */
    this.confirmActionOnMobile = function (event, question, reload = true, trigger = 'jng.page.complete', element = this) {

        var onclick = '';
        event.preventDefault();
        
        if(event.hasOwnProperty('path')){
            
            var href = event.path[0].href;

            //if undefined get parent
            if (typeof href === 'undefined') {
                href = $(element).attr('href');
            }
        }
        else{
            
            var href = $(event.target).attr('href');
            if(typeof href === 'undefined'){
                href = $(event.target).parent('a').attr('href');
            }
        }
        
        if (trigger !== 'jng.direct.link') {
            onclick = 'onclick="jng.executeLinkAction(event, ' + reload + ', \'' + trigger + '\' )"';
        }

        //remove any existing dialog
        if ($('body').find('#dataConfirmModal').length >= 1) {
            $('#dataConfirmModal').remove();
        }

        //append new modal
        $('body').append('<div id=\"dataConfirmModal\" class=\"modal confirm-action fade\" role=\"dialog\" aria-labelledby=\"dataConfirmLabel\" aria-hidden=\"true\"> \
                            <div class=\"modal-dialog\"> \
                                <div class=\"modal-content\"> \
                                    <div class=\"modal-header\"> \
                                        <h3 id=\"dataConfirmLabel\" class="modal-title">Please Confirm Action</h3> \
                                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-hidden=\"true\">×</button> \
                                    </div>\n\
                                    <div class=\"modal-body\"></div> \
                                    <div class=\"modal-footer\"> \
                                        <button class=\"btn\" data-dismiss=\"modal\" aria-hidden=\"true\">Cancel</button> \
                                        <a ' + onclick + ' class=\"btn btn-primary\" id=\"dataConfirmOK\">OK</a> \
                                    </div> \
                                </div> \
                            </div> \
                        </div>');

        $('#dataConfirmModal').find('.modal-body').html('<span class="confirm-txt">' + question + '</span>');
        $('#dataConfirmOK').attr('href', href);
        $('#dataConfirmModal').modal({show: true});

        return false;
    };

    /**
     * Builds the Overlay buttons from the buttons array
     * @param {type} buttons
     * @returns {undefined}
     */
    this._createModalButtons = function (buttons) {

        var i, type, attr, build;
        var names = Object.keys(buttons);
        var length = names.length;

        //loop through buttons
        build = '';
        for (i = 0; i < length; i++) {

            //check button type
            if (buttons[names[i]].hasOwnProperty('type')) {
                type = 'type="' + buttons[names[i]].type + '"';
                delete buttons[names[i]]['type'];
            } else {
                type = 'type="button"';
            }

            attr = this._alignButtonAttrs(buttons[names[i]]);
            build += '<button ' + type + ' ' + attr + '>' + names[i] + '</button>';
        }

        return build;
    };

    /**
     * Create button attributes
     * @param {type} properties
     * @returns {String}
     */
    this._alignButtonAttrs = function (properties) {

        var properties_string = '';

        //loop through the properties
        $.each(properties, function (attr, value) {
            properties_string += attr + ' = "' + value + '" ';
        });

        return properties_string;
    };

    this.ucfirst = function (str) {
        return str.replace(/\w\S*/g, function (txt) {
            return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        });
    };
}
;

