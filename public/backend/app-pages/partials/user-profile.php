<?php
use Carbon\Carbon;

use Jenga\App\Request\Url;
use Jenga\App\Request\Session;

$user = $this->user();
?>
<script type="text/javascript">    
$(function(){

    $('a.close-profile').on('click', function(){
        
        $('#dashboard').removeClass('search-on');
        $("#my-profile-holder").closePanelDown();
    });
});
</script>
<div class="row">
    <div class="col text-right m-t-10 m-r-20 p-t-5">
        <a class="close-profile">
            <i class="ti-close" style="font-weight: bold;"></i>
        </a>
    </div>
</div>
<div class="row">
    <div class="col text-center m-t-0">
        <div id="profile-icon" class="shadow">
            <i class="ti-user"></i>
        </div>
    </div>
</div>
<div class="row m-b-20">
    <div class="col text-center">
        <h4><?= $agency ?></h4>
    </div>
</div>
<div class="profile-links shadow">
    <div class="row">
        <div class="col">
            <a href="<?= Url::route('/user/logout/'.Session::id())?>" rel="external">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>
</div>
