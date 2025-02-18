<?php
use Jenga\App\Request\Url;
use Jenga\App\Views\HTML;
use Jenga\App\Request\Session;

header('Access-Control-Allow-Origin: *'); 
?>
<div id="doc-upload" class="upload-form frontend p-t-0">   
    <div class="top m-t-20 m-b-30 p-t-20">
        <h2 style="font-weight: bold;">Upload Document</h2>
    </div>        
    <div class="row">
        <div class="col-md-12">
        <form method="post" enctype="multipart/form-data" id="uploadform" class="m-t-20" data-parsley-validate>
            <div class="form-area">
            <?= $userhidden ?>    
                <div class="form-group row">
                    <div class="col-md-6 text-left">Document Type</div>
                    <div class="col-md-6">Letter of Intent</div>
                </div>
            <div class="form-group row">
                <div class="col-md-6 text-left">Asset Type</div>
                <div class="col-md-6">
                    <select name="asset_type" id="asset_type" class="form-control" data-role="none">
                        <option value="Industrial">Industrial</option>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6 text-left">Perspective</div>
                <div class="col-md-6">
                    <select name="perspective" id="perspective" class="form-control" data-role="none">
                        <option value="tenant">Tenant</option>
                    </select>
                </div>
            </div>
                <hr/>
                <div class="row">
                    <div class="col-md-12">
                        <p>Upload Your Letter of Intent to analyze it with these settings</p>
                    </div>
                </div>
                <input type="file" id="docfile" name="docfile" style="display: none;" accept=".pdf" />
                <div class="row" id="upload-box">
                    <div class="col-md-12 text-center">
                        <i class="fa fa-cloud-upload" style="font-size: 70px;" aria-hidden="true"></i>
                        <p>Drag and drop your document here or click to browse</p>
                        <span class="small">Supports PDF only</span>
                    </div>
                </div>
                <div id="preloader-upload" class="loader text-center" style="display: none;">
                    <img src="<?= TEMPLATE_URL ?>/frontend/img/generic-loader.gif" style="width: 100px;"  />
                </div>
            </div>
        </form>        
        </div>
    </div>
    <div class="loader text-center" style="display: none;">
        <img src="<?= TEMPLATE_URL ?>/frontend/img/generic-loader.gif" />
    </div>
</div>
