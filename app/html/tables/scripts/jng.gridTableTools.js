(function($){

    $.fn.gridTable = function (settings){

        var gridTable = $(this);
        var tablename = gridTable.attr('id');
        
        if(settings.hasOwnProperty('shortcuts')){
            settings.shortcuts.tablename = gridTable.attr('id');
            settings.opencount = 0;
        }

        //set up grid table listeners
        var setUpListeners = function (settings){

            //add sibling functionality
            if(settings.hasOwnProperty('siblings')){
                    
                //show childrem
                if(settings.siblings.show === true){
                    $('#'+gridTable.attr('id') + ' div.contains-children').show();
                }
                else{
                    $('#'+gridTable.attr('id') + ' div.contains-children').hide();
                }
                
                //show children action
                $('#'+gridTable.attr('id') + ' div.sibling-pointer').on('click', function(){
                    
                    //get row id
                    var id = $(this).attr('data-refers-to');
                    
                    //replace icon and toggle class
                    $(this).toggleClass('has-children-off has-children-on');
                    
                    //switch icons
                    if($(this).hasClass('has-children-off')){
                        $(this).html('<i class="fa fa-caret-right"></i>');
                    }
                    else if($(this).hasClass('has-children-on')){
                        $(this).html('<i class="fa fa-caret-down"></i>');
                    }
                    
                    //toggle show and hide
                    $('#'+id+' div.contains-children').slideToggle();
                });
            }

            //add orderBy functionality
            if(settings.hasOwnProperty('orderby')){
                
                //ordering on click
                $('#'+gridTable.attr('id') + ' > div.rowsholder > div.columnrow').on('click', orderColumns);
            }

            //add shortcuts functionality
            if(settings.hasOwnProperty('shortcuts')){
                
                //trigger child shortcut menu
                $('#'+ gridTable.attr('id') +' '+ settings.shortcuts.childmenuhandle).on(settings.shortcuts.trigger, shortCutMenu);
                
                //trigger main shortcut menu
                $('#'+ gridTable.attr('id') +' '+ settings.shortcuts.menuhandle).on(settings.shortcuts.trigger, shortCutMenu);
                
                //trigger main shortcut when column is clicked
                $('#'+ gridTable.attr('id') +' div.open-shortcut-menu').on(settings.shortcuts.trigger, function(){
                    
                    if($('body').attr('environment') === 'mobile'){
                        
                        //add class
                        $(this).find('div.indicator').addClass('isopen');

                        //open menu
                        var icon = $(this).find('i.shortcut-menu');
                        shortCutMenu(icon);
                    }
                });
                
                //hack to allow modals to open in table dropdown menus
                $('#'+ gridTable.attr('id') + ' a.shortcut-modal').on('click', function(event){
                    
                    event.preventDefault();
                    event.stopPropagation();
                    
                    //create modal settings
                    var classList = $(this).attr('class').split(/\s+/);
                    
                    var options = {show: true};
                    $.each(classList, function(index, item){
                        
                        if(index > 0){
                            var settings = item.split('-');
                            
                            //check keyboard
                            if(item.indexOf("modal-keyboard") >= 0){
                                
                                if(settings[2] === "false")
                                    options.keyboard = false;
                                else if(settings[2] === "true")
                                    options.keyboard = true;
                            }
                            
                            //check backdrop
                            if(item.indexOf("modal-backdrop") >= 0){
                                options.backdrop = settings[2];
                            }
                        }
                    });
                    
                    $('#addeditmodal').modal(options)
                    .find('.modal-content')
                    .load($(this).attr('href'));
                });
            }

            //add selectable functionality
            if(settings.hasOwnProperty('selectable')){

                //add selectable row count
                settings.selectable.count = 0;

                //add selectable class name
                $('#'+gridTable.attr('id')).addClass('selectable');
                $('#'+gridTable.attr('id') +' '+ settings.selectable.attachto).on('click', function(ev){

                    //select clicked row
                    $(this).selectableRows(ev);

                    //shift multiple select
                    if(ev.shiftKey){
                        shiftSelect();
                    }
                });
                
                //setup tool events
                setUpToolEvents(settings.selectable.tools);
            }
            
            //add ajax tabs function
            if(settings.hasOwnProperty('tabs')){
                
                if(settings.tabs.ajax === true){
                    $(document).find('ul.nav-ajax-tabs a').on('click', function(event){
                        
                        event.preventDefault();
                        
                        //check disable-tab
                        if($(this).hasClass('disable-tab') || $(this).hasClass('disable-main-tab')){
                            return;
                        }
                        
                        //disable all other links while loading
                        $(document).find('ul.nav-ajax-tabs a').not(this).addClass('disable-tab');
                        $(document).find('ul.nav-ajax-tabs a').not(this).addClass('disabled');
                        
                        var href = $(this).attr('href');
                        
                        //start ajax request
                        $.ajax({
                            method: "GET",
                            url: href,
                            success: function(response){
                                
                                $('#' + gridTable.attr('id')).html('');
                                $('#' + gridTable.attr('id')).html(response);
                                
                                //restore after loading
                                $(document).find('ul.nav-ajax-tabs a').not(this).removeClass('disable-tab');
                                $(document).find('ul.nav-ajax-tabs a').not(this).removeClass('disabled');
                            }
                        });
                    });
                }
            }
        };
        
        /**
         * Split the sent tools and create the respective events
         * @param {type} tools
         * @returns {undefined}
         */
        function setUpToolEvents(tools){
            
            $.each(tools, function(tool, attrs){
                
                //attach events to each tool
                $(attrs.id).on(attrs.trigger, {action: attrs.action}, function(event){
                    
                    //stop default action
                    event.preventDefault();
                    var link = $(this).find('a').attr('href');
                    
                    //compare actions
                    if(event.data.action === 'compile-and-confirm'){
                        
                        //start building table
                        var confirmrows = buildTable(tool, link);
                        
                        //launch modal
                        if(settings.selectable.count > 0){
                            
                            //create modal title
                            var modalsettings = {
                                id: tool + 'modal',
                                title: 'Confirm ' + jng.ucfirst(tool)
                            };
                            
                            var toolname = jng.ucfirst(tool);
                            
                            //create buttons list
                            var buttonslist = {
                                'Cancel': {
                                    'class': 'btn',
                                    'data-dismiss': 'modal'
                                },
                                [toolname]: {
                                    'type': 'submit',
                                    'class': 'btn btn-primary',
                                    'id': 'save_button',
                                    'onclick': "jng.saveFromOverlay('#"+tool+"-confirm', 'Operation in Progress...', false)"
                                }
                            };
                            
                            //create the overlay
                            var dialog = jng.dialogModal(modalsettings,confirmrows, buttonslist);
                            
                            //clear previous content and then add to dialog modal
                            $(jng.modal).modal().find('.modal-content').html('');
                            $(jng.modal).modal().find('.modal-content').html(dialog);
                        }
                    }
                });
            });
        };
        
        /**
         * Builds the confirm table
         * @returns {String}
         */
        function buildTable(tool, action){
            
            var table = '<p style="font-size:small">Uncheck to remove from '+jng.ucfirst(tool)+' list.</p>';
                       
            //get highlight value
            if($('#' + tablename).find('div.highlight').length > 0 ){

                //start table
                table += '<form id="'+tool+'-confirm" action="'+action+'" method="POST">\
                            <table class="table table-striped"><tbody>';

                $('#' + tablename).find('div.highlight').each(function(){

                   var rowvals = $(this).find('input[name="rows[]"]').val();
                   var data = JSON.parse(rowvals);
                   
                   //start row
                   table += '<tr>';
                   
                   var i;
                   for(i = 0; i<= (data.length - 1); i++){

                       if(i === 0){
                           table += '<td width="10%"><input type="checkbox" name="ids[]" value="'+data[i]+'" checked=""></td>';
                       }
                       else{
                           table += '<td>'+data[i]+'</td>';
                       }
                   }
                   
                   //end row
                   table += '</tr>';
                });

                table += '</tbody>';
                table += '</table> \
                        </form>';
            }
            else{
                table += '<div class="kode-alert kode-alert-icon alert4">\n'+
                            '<i class="fa fa-info"></i>\n'+
                            'No table rows selected\n'+
                          '</div>';
            }        
            
            return table;
        };

        //default order function
        //FIX-BUG The rows cannot be selected/highlighted after a sorting action
        var defaultOrdering = function(){
            
            var ordercol = settings.orderby.column;
            var direction = settings.orderby.direction;
            var rows = $('div.field-row');

            //sort rows
            var rowsort = rows.sort(function(a, b){

                var atext = $(a).find("[data-column-name='"+ordercol+"']").clone().children().remove().end().text();
                var btext = $(b).find("[data-column-name='"+ordercol+"']").clone().children().remove().end().text();

                if(direction === 'asc'){
                    if(atext > btext){
                        return $(a).find("[data-column-name='"+ordercol+"']").text() > $(b).find("[data-column-name='"+ordercol+"']").text();
                    }
                }
                else if(direction === 'desc'){
                    if(atext < btext){
                        return $(a).find("[data-column-name='"+ordercol+"']").text() < $(b).find("[data-column-name='"+ordercol+"']").text();
                    }
                }
            });

            //replace rows holder
            $('div.field-row-holder').html(rowsort);
        };

        //the selectable function
        $.fn.selectableRows = function(ev){

            //hide any open shortcut menus before highlighting rows
            if($('#'+ gridTable.attr('id')+' ul.dropdown-menu').is(':visible')){

                //hide all open shortcut menus
                $('#'+ gridTable.attr('id')+' ul.dropdown-menu').hide();
                ev.stopPropagation();
            }
            else{

                //check header and footer rows
                if(!$(this).hasClass('columnrow') && !$(this).hasClass('footerrow')){

                    //add highlight class
                    $(this).toggleClass('highlight');
                    var id = $(this).attr('id').split('-')[1];

                    //toggle checking and unchecking
                    if($("#checkbox-"+id).attr('checked')){
                        $("#checkbox-"+id).removeAttr('checked');
                        settings.selectable.count--;
                    }
                    else{
                        $("#checkbox-"+id).attr('checked', 'checked');
                        settings.selectable.count++;
                    }

                    //loop through the table tools
                    $.each(settings.selectable.tools, function(tool, attrs){
                        
                        //count the highlited rows
                        var toolid = attrs.id.substr(1);
                        var counthtml = '<span style="position: absolute" id="'+toolid+'-label" class="label label-danger pull-right">'
                                            + settings.selectable.count
                                        + '</span>';

                        //check for previous instances
                        if($('#' + toolid + '-label').parent().length !== 0){
                           $('li#' + toolid).find('span.label').remove();
                        }

                        //get to reattach
                        var toolhtml = $('#' + toolid ).html();                    

                        //add count to tools
                        $('li#' + toolid).prepend(counthtml);
                    });
                }
            }
        };

        //shift select function
        function shiftSelect(){

            //find all highlighted rows
            var shiftRows = $('#'+gridTable.attr('id')).find('.row.highlight');

            //get first & last row ids
            var first = shiftRows.first().attr('id').split('-')[1];
            var last = shiftRows.last().attr('id').split('-')[1];

            //if descending
            if(first < last){
                var start = parseInt(first)+1;
                var finish = parseInt(last)-1;
            }
            //if ascending
            else if(last < first){
                var start = parseInt(last)+1;
                var finish = parseInt(first)-1;
            }

            //highlight the middle rows
            for(i=start; i <= finish; i++){
                $("#row-"+i).selectableRows();
            }
        };

        //display the shortcut menus
        var shortCutMenu = function (element = null){

            var shorts = settings.shortcuts;

            //check element if null
            if(element.target){
                element = $(this);
            }

            //open the current one
            element.addClass('open');
            var rowid =  element.parent().closest('.row').attr('id');

            //hide all previous shorcuts
            $('#'+shorts.tablename+' ul[id!=shortcutMenu-'+shorts.tablename+'-'+rowid+'].dropdown-menu').hide();

            //set the new menu selector  
            shorts.menuSelector = element.data('linked-to');
            
            if($('body').attr('environment') === 'mobile'){
                
                if($(shorts.menuSelector +':visible').length && settings.opencount === 1){

                    settings.opencount = 0;
                    $(shorts.menuSelector).hide();

                    //remove backdrop
                    $(document).find('div.dropdown-backdrop').remove();

                    //remove isopen if anywhere
                    $(document).find('div.indicator').removeClass('isopen');
                }
                else{
                    $(shorts.menuSelector).show('fast',function(){
                        settings.opencount = 1;
                    });

                    //show back drop
                    var backdrop = '<div class="dropdown-backdrop"></div>';
                    $(shorts.menuSelector).after(backdrop);
                }
            }
            else{
                
                $(shorts.menuSelector).show('fast',function(){
                    settings.opencount = 1;
                });
            }

            //make sure menu closes on any click
            $('body > [id!=shortcutMenu-'+rowid+']').click(function (e) {

                //hide all shorcuts
                var target = e.target;

                if (!$(target).is('ul.dropdown-menu') && !$(target).parents().is('ul.dropdown-menu') && settings.opencount === 1) {
                    
                    $('[id^=shortcutMenu-]').hide(); 
                    settings.opencount = 0;
                    
                    //remove backdrop
                    $(document).find('div.dropdown-backdrop').remove();
                    
                    //remove isopen if anywhere
                    $(document).find('div.indicator').removeClass('isopen');
                }
                else{

                    //workaround for direct or modal links
                    if(!$(target).attr('data-target') && $(target).parents().is('div.gridtable')){
                        
                        //allow propagation if content is in row-holder
                        if($(target).parents().is('div.row-holder') === false){
                            e.stopPropagation();
                        }
                    }
                }
            });
        };

        return setUpListeners(settings);
    };

})(jQuery,window);
