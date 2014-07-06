<?php

$directaccess = true;

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

require('config.php');
require('debug.php');


if (isset($_POST['configxml'])) {
    header("Content-Type: text/plain; charset=utf-8");
    $configxmlbackup="config.xml.backup.".time();
    //file_put_contents("outputfile.txt", file_get_contents("php://input"));  //RAW
    if (rename("config.xml", $configxmlbackup)) {
        if (file_put_contents("config.xml", str_replace('&','&amp;',$_POST['configxml'])) > 0) {
            chmod("config.xml", 0666);
            echo "ok";
        } else {
            echo "Konnte neue config.xml nicht speichern. Stelle Backup wieder her.";
            copy($configxmlbackup, "config.xml");
            chmod("config.xml", 0666);
        }
    } else {
        echo "Konnte kein Backup erstellen.";
    }
} else {
    header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Mobile Connair</title>

<link rel="stylesheet" href="jquery.mobile-1.3.1.min.css" />
<link rel="stylesheet" href="jquery-mobile-red-button-theme.css" />
<link rel="stylesheet" href="jquery-mobile-green-button-theme.css" />
<script type="text/javascript" charset="utf-8" src="jquery-1.9.1.min.js"></script>
<script type="text/javascript">

    function refreshPage() {
        location.reload();
    }
    
    $(document).ready(function() {

        $("#reloadbtn").click(function() {
            refreshPage();
        });
        
        $('#editconfigxmlsubmit').click(function (e) {
            $.ajax({
	            url: "editor.php",
	            type: "POST",
	            data: $('#editconfigxmlform').serialize(),
                async: true,
	            success: function(response) {
		            if(response.trim()=="ok") {
                        setTimeout(function(){window.location.href="index.php";}, 1500);
                        toast('gespeichert');
		            } else {
		                toast('response:'+response);
                    }
	            },
	            error: function(response) {
		            toast('response:'+response);
	            }
            });
        });
    });
</script>
<script type="text/javascript" charset="utf-8" src="jquery.mobile-1.3.1.min.js"></script>
<script type="text/javascript" charset="utf-8" src="jquery.toast.mobile.js"></script>

<!-- WebApp -->
<!-- standard viewport tag to set the viewport to the device's width -->
<meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width" />
<!-- width=device-width causes the iPhone 5  to exclude it for iPhone 5 to allow full screen apps -->
<meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1" media="(device-width: 320px) and (device-height: 568px)" />
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black" />

</head>
<body>

<div data-role="page" id="editor" data-theme="<?php echo $theme_page; ?>">

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="index.php#configurations" rel="external" id="cancelbtn" data-transition="slide" data-direction="reverse" data-role="button" data-theme="r">Abbrechen</a>
        <h1>Editor</h1>
        <div data-type="horizontal" data-role="controlgroup"  class="ui-btn-right"> 
            <a href="#" id="reloadbtn" data-role="button" data-iconpos="notext" data-icon="refresh"></a>
            <a href="#" id="editconfigxmlsubmit" data-role="button" data-theme="g">Speichern</a>
        </div>              
    </div><!-- /header -->

    <div data-role="content" id="content">  
        <ul data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-inset="false">

            <li>
                <form id="editconfigxmlform" method="post" data-ajax="false">
                <textarea id="configxml" name="configxml"><?php
                $configxml = file_get_contents("config.xml");
                echo $configxml;
                ?></textarea>
                </form>
            </li>     

        </ul>
    </div><!-- /content -->
</div><!-- /page -->

</body>
</html>

<?php } ?>

