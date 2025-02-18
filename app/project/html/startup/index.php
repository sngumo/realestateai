<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Jenga Startup Page</title>
<!-- App Favicon -->
<link rel="shortcut icon" href="<?php echo RELATIVE_APP_PROJECT ?>/html/images/smalljng.png">

<style type="text/css">
body {
	margin: 0px;
	padding: 0px;
        font-family: Calibri;
}
#logo {
	height: 277px;
	width: 300px;
	margin-top: 10%;
	margin-right: auto;
	margin-bottom: auto;
	margin-left: auto;
}
</style>
    
</head>
<body>
<div id="logo">
    <img src="<?php echo RELATIVE_APP_PROJECT ?>/html/images/jng.png" width="300" height="277" />

    <div>
        <?php
        
        if(is_null($message)){
            echo '<small>Note: Please create the first element for them to be displayed here </small>';
        }
        else{
            echo '<small>'.$message.'</small>';
        }
        ?>
    </div>
</div>
</body>
</html>
