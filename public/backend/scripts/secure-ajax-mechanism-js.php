<script type="text/javascript">
    var fups_home = "<?= RELATIVE_ROOT ?>";
        
    //check for user data
    var local = window.localStorage;
    var user = local.getItem('user-data');
    var tokenUpdated = false;
    
    /**
     * Fetch and store the guest user data
     * @type Arguments
     */
    function checkAndFetchGuestUser(user){
            
             if(user === null){

                var guest_url = fups_home + '/mobile/guest';

                //get and set the guest user
                $.ajax({
                    method: "POST",
                    url: guest_url,
                    tryCount : 0,
                    retryLimit : 3,
                    error: function (){

                        this.tryCount++;
                        if (this.tryCount <= this.retryLimit) {

                            //try again
                            $.ajax(this);
                            return;
                        }   
                        else{
                            $(document).trigger('connection:error', {
                                priority: 'medium',
                                link: guest_url,
                                message: 'Error in server response'
                            });
                        }

                        return;
                    },
                    success: function(response){

                        //set into local storage
                        local.setItem('user-data', response);
                        
                        //set the token flag
                        tokenUpdated = true;
                    }
                });
            }
    }
    
    /**
     * Configure Ajax Headers
     * @param {type} xhr
     * @returns {undefined}
     */
    function configureAjaxHeaders(xhr,) {

        //get user data
        var user = local.getItem('user-data');
        
        //check if null
        if(user !== null){
                
            //get the user object
            var user_object = JSON.parse(user);

            // set the authentication header
            if (user_object !== null && 'bearer' in user_object) {
                xhr.setRequestHeader('Authorization', 'Bearer  ' + user_object.bearer);
            }

            //set the userkey if it exists
            if (user_object !== null && 'userkey' in user_object) {
                xhr.setRequestHeader('Userkey', user_object.userkey);
            }
        }
      }
    
    //on document ready
    $(function(){

            //check and set the guest user
            checkAndFetchGuestUser(user);
            
            //set up the secure ajax extension
           $.ajaxSetup({
              beforeSend: configureAjaxHeaders
          });
    });
</script>
