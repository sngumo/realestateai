<?php
use Jenga\App\Request\Url;
?>
<script type="text/javascript">
$(function () {

    var holder = '#followup-list-pane';
    var preload = '<div class="p-20"> \n\
                        <div class="text-center"> \n\
                            <img src="<?= RELATIVE_APP_PATH ?>/views/loading/fups-loader.gif"> \n\
                        </div> \n\
                    </div>';

    //initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    //set the window width and height
    var width = $(window).width(), height = $(window).height();

    //on window resize refresh table
    $(document).on('window.resize', function(){

        if($(window).width() !== width || $(window).height() !== height){

            //show loading graphic
            $(".loading").show();

            //reload page
            location.reload();
        }
    });

    function runSearch(){

        var searchstr = $('#searchbox').val();

        //set preloader
        $(holder).html(preload);

        if(searchstr !== ''){

            $('.searchbutton').hide();
            $('.cancelsearch').show();

            $.ajax({
                method: "POST",
                url: "<?= Url::link('/business/invoices/search') ?>",
                data: {
                    search: searchstr
                }
            }).done(function(response){
                $(holder).html(response);
            });
        }
    }

    function loadFollowUpForm(id){

        localStorage.setItem('followup-type', id);

        var simpleurl= "<?= Url::link('/business/invoices/simple/create') ?>";
        var advancedurl= "<?= Url::link('/business/invoices/quick/create') ?>";

        if(id === 'simple'){
            var url = simpleurl;
        }
        else{
            var url = advancedurl;
        }

        //add preloader
        $('#addeditmodal .modal-content').html(preload);

        //add the new content
        $.ajax({
            url: url
        }).done(function(response){
            $('#addeditmodal .modal-content').html(response);
        });
    }

    //search function
    $('.searchbutton').on('click', runSearch);

    //on enter key
    $('#searchbox').keypress(function(event){

        var keycode = (event.keyCode ? event.keyCode : event.which);
        if(keycode === 13){

            event.stopPropagation();
            runSearch();	
        }
    });

    //reload to cancel search
    $('.cancelsearch').on('click', function(){
        location.reload();
    });

    //the simple/advanced toggle switch
    $('a.btn-followup').click(function(){

        $('#addeditmodal').modal('show');

        var followup = localStorage.getItem('followup-type');
        if(followup === null){ 
            loadFollowUpForm('simple');
        }
        else{
            loadFollowUpForm(followup);
        }
    });

    //open on top toggle a click
    $(document).on('click', 'div.top-toggle a', function(){

        var id = $(this).attr('id');

        //set the local storage
        loadFollowUpForm(id);
    });
});
</script>