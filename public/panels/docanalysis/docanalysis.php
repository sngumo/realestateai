<?php
use Jenga\App\Request\Url;
use Jenga\App\Views\HTML;
use Jenga\App\Request\Session;

header('Access-Control-Allow-Origin: *'); 
?>
<div id="doctopbar">
    <div class="container">
        <div class="row">
            <div class="col-md-8 text-left">
                <h3 class="top-heading">REDA
                    <sup>Ai</sup>
                </h3>
            </div>
            <div class="col-md-4">
                <div class="row">
                    <div class="col-md-8 text-right p-r-0">
                        <a href="#" class="btn btn-hover-blue" onclick="moveToUpload()">Upload New Document</a>
                    </div>
                    <div class="col-md-4 text-left p-l-0">
                        <a href="#" class="btn btn-hover-red" onclick="logOut()">Logout</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-left">
                <a href="#" class="btn btn-hover-blue" onclick="reAnalyze()">< Back to All Files</a>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-left">
                <h2 class="m-0">RESIDENTIAL LEASE AGREEMENT</h2>
            </div>
        </div>
    </div>
</div>
<div id="block-sections" class="container">
        <div class="row">
            <div id="doc-header" class="col-md-8 block white p-10">
                <div class="row white" >
                    <div class="col-md-4 text-left p-l-20">
                        <span>Contract Type</span><br/>
                        <span class="blue">Residential Lease Agreement</span>
                    </div>
                    <div class="col-md-4">
                        <span>Parties</span><br/>
                        <span class="blue">Side 1</span>
                        <span class="blue">Side 2</span>
                    </div>
                    <div class="col-md-4 text-right p-r-20">
                        <span>Date Uploaded</span><br/>
                        <span class="blue">February 13, 2025</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div id="terms-concerns" class="row">
                    <div class="col-md-6 block center">
                        <p class="m-t-10"><strong>Concerns</strong>
                            <span>3/24</span>
                        </p>
                    </div>
                    <div class="col-md-6 block center active">
                        <p class="m-t-10"><strong>Terms</strong>
                            <span>1/5</span>
                        </p>
                    </div>
                </div>
            </div>            
        </div>
</div>
<div id="docanalysis" class="container">
    <div class="row">
        <div class="col-md-8">
            <div id="document" class="doc-block frontend p-t-0">   
                <div class="row">
                    <div id="doctext" class="col-md-12" >
                        <div id="adobe-dc-view">
                            <div id="preloader-login" class="loader text-center">
                                <img src="<?= TEMPLATE_URL ?>/frontend/img/generic-loader.gif" style="width: 100px" />
                            </div>
                            <p>Loading Document</p>
                        </div>
                        <!--<iframe src="" width="600" height="600"></iframe>-->
                    </div>
                </div>
            </div>
        </div>
        <div id="business-terms" class="col-md-4 doc-block">
            <div class="row p-t-20">
                <div class="col-md-12 text-left m-b-5 p-l-20">
                    <strong>Favorability</strong>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="progress-border">
                      <div class="progress-green" style="height:24px;width:40%"></div>
                    </div>
                </div>
            </div>
                <div id=analysis" class="frontend row m-t-20 p-t-0">   
                        <div id="analysis-tabs" class="col-md-12">    
                            <div id="preloader-login" class="loader text-center">
                                <img src="<?= TEMPLATE_URL ?>/frontend/img/generic-loader.gif" style="width: 100px" />
                            </div>
                            <p>Analysing Document</p>
                        </div>
                </div>
        </div>
    </div>
</div>

<!--               
<div class="row text-left  analysis-row row-success">
        <div class="col-md-4">
            <strong>Term: Base Rent</strong> 
            <span class="small">[Line No 12]</span>
        </div>
        <div class="col-md-8">
            Status: <strong>PRESENT</strong><br/>
            <span class="small">Term Used: Lease Term</span>
        </div>
 </div>
-->





