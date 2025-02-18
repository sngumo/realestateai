<?php
use Jenga\App\Request\Url;
use Jenga\App\Request\Session;
use Jenga\App\Views\HTML;
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<!--Dashboard-->
<div data-role="page" id="dashboard" data-dom-cache="false">
    <?php
        require ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'app-pages' .DS. 'dashboard.php';
    ?>
</div>

<!--Opened FollowUp Page-->
<div data-role="page" id="follow-up-opened">
    <?php
        require ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'app-pages' .DS. 'open-followup.php';
    ?>
</div>

<!--Create FollowUp Page-->
<div data-role="page" id="create-follow-up">
    <?php
        require ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'app-pages' .DS. 'create-followup.php';
    ?>
</div>
<div id="transition" class="container-fluid">
    <div class="row h-100">
        <div class="col text-center align-self-center">
            <img src="<?= RELATIVE_VIEWS ?>/loading/fups-loader.gif" />
        </div>
    </div>
</div>
<?php
    //add the followup tools panel
    require ABSOLUTE_PATH .DS. 'public' .DS. 'backend' .DS. 'app-pages' .DS. 'partials' .DS. 'followup-tools-panel.php';

    //end HTML
    HTML::end();
?>
<!-- ================================================
Sweet Alert
================================================ -->
<script src="<?= TEMPLATE_URL ?>/backend/js/sweet-alert/sweet-alert.min.js"></script>
