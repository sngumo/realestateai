<?php
use Jenga\App\Request\Url;
use Jenga\App\Request\Session;
use Jenga\App\Project\Core\Project;

    $cfg = Project::getConfigs();
    $timeout = $cfg->session_lifetime;
?>
<script type="text/javascript">    
    function fetchAndSetCookie(idleTimeout){
        
        //get user cookie data
        $.ajax({
            url: "<?= Url::link('/login/cookie') ?>",
            method: "GET",
            beforeSend: function(){
                window.clearTimeout(idleTimeout);
            }
        })
        .done(function(response){

            //check timelog cookie and create it if not present
            if($.cookie('user-timestamp') === undefined){
                
                //set coockie
                $.cookie('user-timestamp', response, {
                    domain: '<?= $cfg->cookie_domain ?>'
                });            
            }
            else{
                
                var server = JSON.parse(response);
                var cookie = JSON.parse($.cookie('user-timestamp'));
                
                //compare cookie login and server's last login
                var lastlogin = server.lastlogin;
                var cookielogin = cookie.login;
                
                //check if cookie is old
                if(cookielogin < lastlogin){
                    
                    //cooke has expired so reset                    
                    $.cookie('user-timestamp', response, {
                        domain: '<?= $cfg->cookie_domain ?>'
                    }); 
                }
            }
        });
    }
    
    $(function() {

        var idleDurationSecs = <?= $cfg->session_lifetime ?>;    // X number of seconds
        const redirectUrl = '<?= Url::route('/user/logout/'.Session::id())?>';  // Redirect idle users to this URL
        let idleTimeout; // variable to hold the timeout, do not modify

        const resetIdleTimeout = function() {
            if($.cookie('user-timestamp') !== undefined){
                
                var cookie = JSON.parse($.cookie('user-timestamp'));

                var cookiedate = new Date(cookie.login * 1000);
                var currentdate = new Date();
                var idleDurationMilliSecs = idleDurationSecs * 1000;

                var diff = Math.abs(currentdate - cookiedate);
                var secondsDiff = Math.floor(diff/1000);

                // Clears the existing timeout
                if(secondsDiff < idleDurationSecs){ 
                    window.clearTimeout(idleTimeout);

                    // Set a new idle timeout to load the redirectUrl after idleDurationSecs
                    if($.cookie('user-remember') !== undefined){
                        idleTimeout = setTimeout(function(){
                            $(document).trigger('session:expired');
                        }, idleDurationMilliSecs);
                    }
                }
                else{
                    //check user remember cookie
                    if($.cookie('user-remember') !== undefined){
                        idleTimeout = setTimeout(function(){
                            $(document).trigger('session:expired');
                        }, 20000);
                    }
                }
            }
        };

        // Init on page load
        resetIdleTimeout();

        //fetch cookie and set timestamps
        fetchAndSetCookie(idleTimeout);

        // Reset the idle timeout on any of the events listed below
        $('body').on('click touchstart mousemove mouseover', function(evt){
            resetIdleTimeout();            
        });
        
        //process session expired
        $(document).on('session:expired', function(){
            $('#close-session').show();
        });
        
        //process connection error
        $(document).on('connection:error', function(){
            $('#connection-error').show();
        });
    });
  </script>  