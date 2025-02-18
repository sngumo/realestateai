<?php
use Jenga\App\Request\Url;
use Jenga\App\Views\Overlays;
?>
<!-- START CONTAINER -->
<div class="row presentation">
    <div class="col-lg-6 col-md-6 titles">
        <div class="row">
            <div class="col-lg-2 col-md-2">
                <span class="icon color6-bg">
                    <i class="icon-user-following"></i>
                </span>
            </div>
            <div class="col-lg-10 col-md-10">
                <h1>User Management</h1>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-md-6" style="padding-top: 15px;">
        <div class="btn-group pull-right"> 
            <ul class="list-inline">
                <li>
                    <a data-toggle="modal" data-target="#addeditmodal" data-backdrop="static" href="<?= Url::link('/settings/users/addedit') ?>" class="form-action btn btn-light">
                        <i class="fa fa-plus-circle"></i> 
                        <span style="display:  block;float: right;">
                            Add
                        </span>
                    </a>
                </li>
                <li id="agents_table-delete">
                    <a href="<?= Url::link('/ajax/settings/users/delete') ?>" class="form-action btn btn-light">
                        <i class="fa fa-trash-o"></i> Delete
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
    
<!-- Start First Row -->
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-body">
            <?php
                echo $userslistingtable;
            ?>
            </div>
        </div>
    </div>
</div>  
<!-- End First Row -->

