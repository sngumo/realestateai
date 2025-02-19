
const customerlogin = '#customerlogin';
const preloaderlogin = '#preloader-login';
const uploadform = '#doc-upload';
const uploadbox = uploadform + ' #upload-box';
const fileupload = '#docfile';
const preloaderupload = '#preloader-upload';

let site  = localStorage.getItem('SITE_PATH');
let previewFilePromise = null;

function logOut(){
    localStorage.clear();
    
     //change page
    $.mobile.navigate('#loginpage', {
        transition: "slidefade"
    });
    
    window.location.reload();
}

function moveToUpload(){
    
     //change page
    $.mobile.navigate('#uploadpage', {
        transition: "slidefade"
    });
    
    window.location.reload();
}

function parseAndStructureAnalysis(analysis_obj, targetSelector) {
    
    // Clear the target element to avoid duplicate entries
    $(targetSelector).empty();

    let htmlString = '';
    
    // Iterate through each key in the "analysis" object
    $.each(analysis_obj, function (key, value) {
        
        // Extract values
        const term = key;
        const status = value.status;
        const positionInfo = value.positions.length > 0 ? value.positions[0] : null;

        // Determine the line number from position (if available)
        const lineNo = positionInfo ? Math.ceil(positionInfo.position / 120) : "N/A";
        const termUsed = positionInfo ? positionInfo.term : "None";

        // Create the row class based on the status
        const rowClass = status === "PRESENT" ? "row-success" : "row-failure";
        const rowStyle = status === 'PRESENT' ? 'style="cursor: pointer;"' : '';
        
        //only display the PRESENT terms
        if(status === 'PRESENT'){
            
            // Construct the HTML and append directly to the target element
            htmlString += `<div class="row text-left analysis-row ${rowClass}" data-search-term="${termUsed}"  ${rowStyle} >
                    <div class="col-md-12">
                        <strong>${term}</strong> <br/>
                        <span class="small">Used Terms: ${termUsed}</span>
                    </div>
                </div>`;
        }
        
    });
    
    //if no terms are found
    if(htmlString === ''){
        htmlString = '<h5 style="color: red">No business terms found in the document</h5>';
    }
    
    $(targetSelector).append(htmlString);
}

function embedPDF(docpath){

    // Store the UI options in a constant
    const previewConfig = {
        embedMode: "IN_LINE",
        showDownloadPDF: true,
        showZoomControl: false,
        enableSearchAPIs: true
   };

    let adobeDCView = new AdobeDC.View({clientId: "6b24a9e977904f868d9ea4249ce0f4c1", divId: "adobe-dc-view"});
    
    previewFilePromise = adobeDCView.previewFile({
      content:{location: {url: docpath}},
      metaData:{fileName: "Bodea Brochure.pdf"}
   }, previewConfig);
   
   return previewFilePromise;
}

//show the document text
function showDocumentText(docid){
    
    let site  = localStorage.getItem('SITE_PATH');
    
    //start the ajax operation
    $.ajax({
        url: site + '/gettext/' + docid,
        method: 'post',
        dataType: 'json'
    }).done(function(response){
        
        if(response.status === 1){
            
            let docpath = site + '/project/storage/' + response.filename;
            
            //display the text in the text column
//            $('#docanalysis iframe').attr('src', docpath);

            //invoke the Adobe Embed API
           let pdfdoc  = embedPDF(docpath);
            
           //analyze the text
            $.ajax({
                url: site + '/analyze/' + docid,
                method: 'post',
                dataType: 'json'
            }).done(function(response){
                
                //parse and structure analysis
                parseAndStructureAnalysis(response.analysis, '#docanalysis div#analysis-tabs');

                //get the search terms and go through the PDF
//                searchDocForBaseTerms(rules, pdfdoc, '#docanalysis div#analysis-tabs');
            });
            
        }
        else{
            $('#docanalysis div#doctext').html(response.message);
        }
    });
}


/**
 * Reruns the document analysis
 * @returns {undefined}
 */
function reAnalyze(){
    
    let docid = localStorage.getItem('docid');
    
    showDocumentText(docid);
}

/**
 * Check if element is in viewpoint
 */
$.fn.isInViewport = function() {
    var elementTop = $(this).offset().top;
    var elementBottom = elementTop + $(this).outerHeight();

    var viewportTop = $(window).scrollTop();
    var viewportBottom = viewportTop + $(window).height();

    return elementBottom > viewportTop && elementTop < viewportBottom;
};

//on document ready
$(function(){
    
    //check if user in in local storage
    let user = localStorage.getItem('user');
    if(user === null){
        
            //change page
            $.mobile.navigate('#loginpage', {
                transition: "slidefade"
            });
    }
    
    //on window reload
    $(window).on('load', function(){
        
        var activePage = $.mobile.activePage.attr('id');
        if(activePage === 'doc-analysis-page'){
            reAnalyze();
        }
    });
    
    //the login functionality
    $(customerlogin + ' button').on('click', function(ev){
        
            ev.preventDefault();
            $(customerlogin).parsley().validate();

            let site  = localStorage.getItem('SITE_PATH');
            let username = $(customerlogin + ' input[name=username]').val();
            let password = $(customerlogin + ' input[name=password]').val();

            //start the login process
            $(customerlogin).hide();
            $(preloaderlogin).show();

            //start the ajax function
            $.ajax({
                url: site + '/userlogin',
                method: 'POST',
                dataType: 'json',
                data: {
                    username: username,
                    password: password
                }
            }).done(function(response){

                if(response.status === 1){

                    //set it into local storage
                    localStorage.setItem('user', JSON.stringify(response));

                    //change page
                    window.location = '#uploadpage';
                    window.location.reload();
                }
                else{

                        //show the form
                        $(preloaderlogin).hide();
                        $(customerlogin).show();

                        //set the input to red
                        $(customerlogin + ' input').css('border', '1px solid red');
                }
            });
        });

        //the upload functionality
        $(uploadbox).on('click', function(ev){

            ev.preventDefault(); 
            $(fileupload).trigger('click');
        });

        //upload form submission functionality
        $(fileupload).on('change', function(ev){

            let site  = localStorage.getItem('SITE_PATH');
            let fd = new FormData();
            let files = $(fileupload)[0].files[0];

            //check file extension
            let filename = files.name;
            let extension =  filename.substring(filename.lastIndexOf(".")+1,filename.length);

            //check if extensions is PDF
            if(extension.toLowerCase() !== 'pdf'){

                $(uploadbox).show();

                $(uploadbox).css('border', '1px solid red');
                $(uploadbox).css('color', 'red');            

                return;
            }

            //get the form fields
            let asset_type = $(uploadform + ' #asset_type').val();
            let perspective = $(uploadform + ' #perspective').val();

            //append form values
            fd.append('asset_type', asset_type);
            fd.append('perspective', perspective);

            //append file to the form data
            fd.append('file', files);

            //attach the user profile id
            let user = localStorage.getItem('user');
            let userdata = JSON.parse(user);
            fd.append('profile_id', userdata.profile);

            //create the progress animation
            $(uploadbox).hide();
            $(preloaderupload).show();

            //start the upload
            $.ajax({
                url: site + '/startupload',
                method: 'post',
                data: fd,
                processData: false,
                contentType: false
            }).done(function(response){

                let feedback = JSON.parse(response);

                if(feedback.status){

                    //reset upload box
                    $(uploadbox).show();
                    $(preloaderupload).hide();

                    //show document text
                    showDocumentText(feedback.docid);

                    //set the document id into localStorage
                    localStorage.setItem('docid', feedback.docid);

                    //change page
                    $.mobile.navigate('#doc-analysis-page', {
                        transition: "slidefade"
                    });
                }
            });
        });

        $(document).on('click', '#docanalysis div.analysis-row', function(){

            if (!previewFilePromise) {
                console.log("previewFilePromise not yet initialized.");
                return;
            }

            //get the search term
            let searchterm = $(this).attr('data-search-term');

            //perform the search on the document
            previewFilePromise.then(adobeViewer => {
                    adobeViewer.getAPIs().then(apis => {
                            apis.search(searchterm);
                    });
            });
        });
        
        //fix or scroll business search terms on scroll 
        $(document).on('scroll', function(){
            
                //detect end of header
                var header = '#doc-header';
                var businessterms = $('#business-terms');
                
                if($(header).isInViewport()) {
                
                    //remove fixed class
                    if(businessterms.hasClass('fixed')){
                        businessterms.removeClass('fixed');
                    }
                }
                else{
                    if(!businessterms.hasClass('fixed')){
                        businessterms.addClass('fixed');
                    }
                }
        });
    });
