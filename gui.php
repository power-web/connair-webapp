<?php

if((!isset($directaccess)) OR (!$directaccess)) die();

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

header("Content-Type: text/html; charset=utf-8");

?>
<!DOCTYPE html>
<!--html manifest="cache.manifest"-->
<html>
<head>
<meta charset="UTF-8">
<title>Mobile Connair</title>

<link rel="icon" type="image/x-icon" href="favicon.ico">
<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
<link type="image/x-icon" href="favicon.ico">

<link rel="stylesheet" href="jquery.mobile-1.3.1.min.css" />
<link rel="stylesheet" href="jquery-mobile-red-button-theme.css" />
<link rel="stylesheet" href="jquery-mobile-green-button-theme.css" />
<style type="text/css">

/* icon größe von der liste */
.ui-li-thumb, .ui-li-icon {
    left: 1px;
    max-height: 32px; 
    max-width: 32px;
    position: absolute;
    top: 0;
}

.ui-icon-on {
	background-image: url("app-icon-on.png");
}
.ui-icon-off {
	background-image: url("app-icon-off.png");
}

/*
.ui-grid-a .ui-block-a { width: 66.95%; }
.ui-grid-a .ui-block-b { width: 32.925%; }
.ui-grid-a .ui-block-a { clear: left; }
*/

.hide {
    visibility:hidden;
    display:none;
}
.show {
    visibility:visible;
    display:inline;
}


/*
.ui-grid-a .ui-block-a { width: 66.95%; }
.ui-grid-a .ui-block-b { width: 32.925%; }
.ui-grid-a .ui-block-a { clear: left; }
*/













@media (min-width:35em) {
 
/* wrap on wide viewports once open */
 
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-display-push.ui-panel-content-fixed-toolbar-position-left,
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-display-reveal.ui-panel-content-fixed-toolbar-position-left,
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-wrap-display-push.ui-panel-content-wrap-position-left,
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-wrap-display-reveal.ui-panel-content-wrap-position-left {
    margin-right: 17em;
}
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-display-push.ui-panel-content-fixed-toolbar-position-right,
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-display-reveal.ui-panel-content-fixed-toolbar-position-right,
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-wrap-display-push.ui-panel-content-wrap-position-right,
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-wrap-display-reveal.ui-panel-content-wrap-position-right {
    margin-left: 17em;
}
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-display-push,
.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-display-reveal {
    width: auto;	
}
 
/* disable "dismiss" on wide viewports */
.ui-responsive-panel .ui-panel-dismiss-display-push {
    display: none;
}
 
}



.box-btn-switch .ui-btn, .box-btn-edit .ui-btn { 
    height: 45px; 
    min-width: 55px; 
    max-width: 116px;
    margin: 0 auto;
}
.box-btn-edit .ui-btn-icon-notext .ui-btn-inner .ui-icon { 
    position: absolute;
    left: 16px;
    right: 16px;
    top: 7px;
}

.box-btn-switch .ui-btn-corner-all, .box-btn-edit .ui-btn-corner-all, [data-role="header"] .ui-btn-corner-all, [data-role="panel"] .ui-btn-corner-all {
    -moz-border-radius:             .5em;
    -webkit-border-radius:          .5em;
    border-radius:                  .5em;
}​
.box-btn-hide {
    visibility:hidden;
    display:none;
}
.box-btn-show {
    visibility:visible;
    display:inline;
}

.box-btn-switch {
    /*display: block;
    white-space: nowrap;*/
}
.box-btn-edit {
    /*display: none;
    white-space: nowrap;*/
}




/*
Zeilenumbruch in der Listview
*/
.ui-page .ui-content .ui-listview .ui-li-heading {
    white-space: normal;
}
.ui-page .ui-content .ui-listview .ui-li-desc {
    white-space: normal;
}

</style>
    
<script type="text/javascript" charset="utf-8" src="jquery-1.9.1.min.js"></script>
<script type="text/javascript">




    $(document).bind("mobileinit", function(){
        $.support.touchOverflow = true;
        $.mobile.touchOverflowEnabled = true;
        //$.mobile.fixedToolbars.setTouchToggleEnabled(false);
        $.mobile.defaultPageTransition = 'none';
        //$.mobile.page.prototype.options.domCache = true;
    });
    
    $(document).ready(function() {
        $.event.special.swipe.scrollSupressionThreshold=10;
        $.event.special.swipe.durationThreshold=1000;
        $.event.special.swipe.horizontalDistanceThreshold=150;
        $.event.special.swipe.verticalDistanceThreshold=20;
        $(document).on( 'swiperight', swiperightHandler );
        function swiperightHandler( event ){
            $.mobile.activePage.find('#mypanel').panel( "open" );
        }
        $(document).on( 'swipeleft', swipeleftHandler );
        function swipeleftHandler( event ){
            $.mobile.activePage.find('#mypanel').panel( "close" );
        }
<?php 
	if ($xml->gui->showMenuOnLoad=="true") {
?>
            setTimeout(function() {
                $.mobile.activePage.find('#mypanel').panel( "open" );
            }, 500);
<?php 
	}
?>

        $('#newdevicesubmit').click(function (e) {
            $.ajax({
	            url: "edit_device.php",
	            type: "POST",
	            data: $('#newdeviceform').serialize(),
                    async: true,
	            success: function(response) {
		        if(response.trim()=="ok") {
                            setTimeout(function(){refreshPage()}, 1500);
		            $.mobile.changePage('#devices', {
                            	transition: "slide",
                            	reverse: true
                            });
	                    toast('gespeichert');
	                    resetNewDeviceForm();
		        } else {
		            toast('response:'+response);
		        }
	            }
            });
	});


        $('#editconfigsubmit').click(function (e) {
            $.ajax({
	            url: "edit_config.php",
	            type: "POST",
	            data: $('#editconfigform').serialize(),
                    async: true,
	            success: function(response) {
		        if(response.trim()=="ok") {
	                    setTimeout(function(){refreshPage()}, 1500);
	                    toast('gespeichert');
                        } else {
                            toast('response:'+response);
                        }
	            }
            });
	});
	    
	$('#newtimersubmit').click(function (e) {
            $.ajax({
	            url: "edit_timer.php",
	            type: "POST",
	            data: $('#newtimerform').serialize(),
                    async: true,
	            success: function(response) {
		        if(response.trim()=="ok") {
	                    setTimeout(function(){refreshPage()}, 1500);
		            $.mobile.changePage('#timers', {
                            	transition: "slide",
                            	reverse: true
                            });
                            toast('gespeichert');
	                    resetNewTimerForm();
                        } else {
                            toast('response:'+response);
                        }
	            }
            });
	});



    });
<?php 
    $menuAnimated="true";

	//if ($xml->gui->showMenuOnLoad=="xxx") {
?>
 
//if (isMedia("screen and (min-width:35em)")){
  /*    $(document).delegate('.ui-page', 'pagebeforeshow', function () {
        if($(window).width() > 640) {
            $.mobile.activePage.find('#mypanel').panel( "open" );
            $.mobile.activePage.trigger('updatelayout');
            $.mobile.activePage.find('#content').trigger('updatelayout');
        }
    });
 */  /*
    $(document).delegate('.ui-page', 'pageshow', function () {
        if($(window).width() >= 640) {
            //$.mobile.activePage.find('#mypanel').panel( "option", "animate", false );
            $.mobile.activePage.find('#mypanel').panel( "open");
            //$.mobile.activePage.find('#mypanel').panel( "option", "animate", true );
        }
    });
    */
//}
<?php
     //   	$menuAnimated="false";
	//}
?>
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
<!-- iPhone -->
<link href="images/apple-touch-icon-57x57.png" sizes="57x57" rel="apple-touch-icon">
<link href="images/apple-touch-startup-image-320x460.png" media="(device-width: 320px) and (device-height: 480px) and (-webkit-device-pixel-ratio: 1)" rel="apple-touch-startup-image">
<!-- iPhone (Retina) -->
<link href="images/apple-touch-icon-114x114.png" sizes="114x114" rel="apple-touch-icon">
<link href="images/apple-touch-startup-image-640x920.png" media="(device-width: 320px) and (device-height: 480px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
<!-- iPhone 5 -->
<link href="images/apple-touch-startup-image-640x1096.png" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
<!-- iPad -->
<link href="images/apple-touch-icon-72x72.png" sizes="72x72" rel="apple-touch-icon">
<link href="images/apple-touch-startup-image-768x1004.png" media="(device-width: 768px) and (device-height: 1024px) and (orientation: portrait) and (-webkit-device-pixel-ratio: 1)" rel="apple-touch-startup-image">
<link href="images/apple-touch-startup-image-748x1024.png" media="(device-width: 768px) and (device-height: 1024px) and (orientation: landscape) and (-webkit-device-pixel-ratio: 1)" rel="apple-touch-startup-image">
<!-- iPad (Retina) -->
<link href="images/apple-touch-icon-144x144.png" sizes="144x144" rel="apple-touch-icon">
<link href="images/apple-touch-startup-image-1536x2008.png" media="(device-width: 768px) and (device-height: 1024px) and (orientation: portrait) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
<link href="images/apple-touch-startup-image-1496x2048.png" media="(device-width: 768px) and (device-height: 1024px) and (orientation: landscape) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
    <script type="text/javascript">
        function send_connair(action, type, id) {
            var data={ 'action': action, 'type': type, 'id': id };
//toast( 'action:'+ action+ '  type:'+ type+ '  id:'+ id);
            $.ajax({
                type:'POST', 
                url: '<?php echo $_SERVER['PHP_SELF']; ?>', 
                data: data,
                async: true,
                success: function(response) {
                    toast(response);
<?php if($xml->gui->showDeviceStatus != 'OFF') { ?>
                    if(action == 'allon' || action == 'alloff' || type == 'room' || type =='group') {
                        refreshPage();
                    }
<?php } ?>
                },
                error: function(response) {
                    toast(response);
                }
            });
        }
        
        function refreshPage()
{
location.reload();
/*
    alert(window.location.href);
    jQuery.mobile.changePage(window.location.href, {
        allowSamePageTransition: true,
        transition: 'none',
        changeHash: false,
        reloadPage: true
    });
*/
}

        function updateTheme(newTheme) {
            var rmbtnClasses = '';
            var rmhfClasses = '';
            var rmbdClassess = '';
            var arr = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"  ];

            $.each(arr,function(index, value){
                rmbtnClasses = rmbtnClasses + " ui-btn-up-"+value + " ui-btn-hover-"+value;
                rmhfClasses = rmhfClasses + " ui-bar-"+value;
                rmbdClassess = rmbdClassess + " ui-body-"+value;
            });

            // reset all the buttons widgets
             $.mobile.activePage.find('.ui-btn').not('.ui-li-divider').removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);

             // reset the header/footer widgets
             $.mobile.activePage.find('.ui-header, .ui-footer').removeClass(rmhfClasses).addClass('ui-bar-' + newTheme).attr('data-theme', newTheme);

             // reset the page widget
             $.mobile.activePage.removeClass(rmbdClassess).addClass('ui-body-' + newTheme).attr('data-theme', newTheme);

             // target the list divider elements, then iterate through them and
             // change its theme (this is the jQuery Mobile default for
             // list-dividers)
             $.mobile.activePage.find('.ui-li-divider').each(function(index, obj) {
                $(this).removeClass(rmhfClasses).addClass('ui-bar-' + newTheme).attr('data-theme',newTheme);
             });
        }

        function switchRowTheme(action, id, onColor, offColor) {
            var rmbtnClasses = '';
            var rmhfClasses = '';
            var rmbdClassess = '';
            var arr = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"  ];
            $.each(arr,function(index, value){
                rmbtnClasses = rmbtnClasses + " ui-btn-up-"+value + " ui-btn-hover-"+value;
                rmhfClasses = rmhfClasses + " ui-bar-"+value;
                rmbdClassess = rmbdClassess + " ui-body-"+value;
            });
            if(action == "on") {
                newTheme = onColor;
            } else if(action == "off") {
                newTheme = offColor;
            }         
            $("[id=deviceRow"+id+"]").each(function() {
                $(this).removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
            });
        }

        function switchButtonTheme(action, id, onColor, offColor, curColor) {
            var rmbtnClasses = '';
            var rmhfClasses = '';
            var rmbdClassess = '';
            var arr = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"  ];
            $.each(arr,function(index, value){
                rmbtnClasses = rmbtnClasses + " ui-btn-up-"+value + " ui-btn-hover-"+value;
                rmhfClasses = rmhfClasses + " ui-bar-"+value;
                rmbdClassess = rmbdClassess + " ui-body-"+value;
            });
            if(action == "on") {
                newTheme = curColor;
                $("[id=btnOn"+id+"]").each(function() {
                    $(this).button().attr('data-theme', newTheme).parent('.ui-btn').removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
                });
                newTheme = offColor;
                $("[id=btnOff"+id+"]").each(function() {
                    $(this).button().attr('data-theme', newTheme).parent('.ui-btn').removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
                });
            } else if(action == "off") {
                newTheme = onColor;
                $("[id=btnOn"+id+"]").each(function() {
                    $(this).button().attr('data-theme', newTheme).parent('.ui-btn').removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
                });
                newTheme = curColor;
                $("[id=btnOff"+id+"]").each(function() {
                    $(this).button().attr('data-theme', newTheme).parent('.ui-btn').removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
                });
            }
        }

        function switchButtonIcon(action, id, onIcon, offIcon) {
            var rmbtnClasses = '';
            var rmhfClasses = '';
            var rmbdClassess = '';
            var arr = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"  ];
            $.each(arr,function(index, value){
                rmbtnClasses = rmbtnClasses + " ui-btn-up-"+value + " ui-btn-hover-"+value;
                rmhfClasses = rmhfClasses + " ui-bar-"+value;
                rmbdClassess = rmbdClassess + " ui-body-"+value;
            });
            if(action == "on") {
                $("[id=btnOn"+id+"]").each(function() {
                    $(this).button().buttonMarkup({ icon: onIcon });
                });
            } else if(action == "off") {
                $("[id=btnOn"+id+"]").each(function() {
                    $(this).button().buttonMarkup({ icon: offIcon });
                });
            }
        }
        
        
        function showEditButtonsA() { 
            zeit=100;
            if($('#editButton').hasClass("l3x-active")) {
                $('#editButton').removeClass("ui-btn-active"); 
                $('#editButton').removeClass("l3x-active"); 

                $('.box-btn-edit').each(function() {
                    $(this).animate({ opacity: "hide" }, zeit, function() {
                        $(this).siblings(".box-btn-switch").animate({ opacity: "show" }, zeit);
                    });
                });
            } else {
                $('#editButton').addClass("ui-btn-active");
                $('#editButton').addClass("l3x-active"); 
                
                $('.box-btn-switch').each(function() {
                    $(this).animate({ opacity: "hide" }, zeit, function() {
                        $(this).siblings(".box-btn-edit").animate({ opacity: "show" }, zeit);
                    });
                });
            }
        }       
        
        function showEditButtons() {
            var editButton = $.mobile.activePage.find('#editButton');
            if(editButton.hasClass("l3x-active")) {
                editButton.removeClass("ui-btn-active");
                editButton.removeClass("l3x-active");
                
                $.mobile.activePage.find(".box-btn-edit").each(function() {
                    $(this).removeClass('show').addClass('hide');
                });
                $.mobile.activePage.find(".box-btn-switch").each(function() {
                    $(this).removeClass('hide').addClass('show');
                });
            } else {
                editButton.addClass("ui-btn-active");
                editButton.addClass("l3x-active"); 
                
                $.mobile.activePage.find(".box-btn-switch").each(function() {
                    $(this).removeClass('show').addClass('hide');
                });
                $.mobile.activePage.find(".box-btn-edit").each(function() {
                    $(this).removeClass('hide').addClass('show');
                });
            }
        }
                
        function showEditButtonsAlle() { 
            if($('#editButton').hasClass("l3x-active")) {
                $('#editButton').each(function() {
                    $(this).removeClass("ui-btn-active");
                    $(this).removeClass("l3x-active");
                }); 
                
                $(".box-btn-edit").css("display","none");
                $(".box-btn-switch").css("display","block");
            } else {
                $('#editButton').each(function() {
                    $(this).addClass("ui-btn-active");
                    $(this).addClass("l3x-active"); 
                });
                
                $(".box-btn-switch").css("display","none");
                $(".box-btn-edit").css("display","block");
            }
        }
        
        
        function edit_device(id) {
            alert('edit_device '+id);
        }
        function delete_device(id) {
            $.ajax({
	            url: "edit_device.php",
	            type: "POST",
	            data: "action=delete&id="+id,
                async: true,
	            success: function(response) {
		            if(response.trim()=="ok") {
		                $.mobile.changePage('#devices', {
                            transition: "slide",
                            reverse: true
                        });
		                toast('gelöscht');
		                refreshPage();
                    } else {
                        toast('response:'+response);
                    }
	            }
            });
        }
        function edit_group(id) {
            alert('edit_group '+id);
        }
        function delete_group(id) {
            $.ajax({
	            url: "edit_group.php",
	            type: "POST",
	            data: "action=delete&id="+id,
                async: true,
	            success: function(response) {
		            if(response.trim()=="ok") {
		                $.mobile.changePage('#groups', {
                            transition: "slide",
                            reverse: true
                        });
		                toast('gelöscht');
		                refreshPage();
                    } else {
                        toast('response:'+response);
                    }
	            }
            });
        }
        function edit_timer(id, action) {
            if(action=="EIN") {
                $.ajax({
                    url: "edit_timer.php",
                    type: "POST",
                    data: "action=on&id="+id,
                    async: true,
	            success: function(response) {
		        if(response.trim()=="ok") {
		                toast('gespeichert');
		                refreshPage();
                        } else {
                            toast('response:'+response);
                        }
	            }
                });
            }
            if(action=="AUS") {
                $.ajax({
	            url: "edit_timer.php",
	            type: "POST",
	            data: "action=off&id="+id,
                    async: true,
	            success: function(response) {
		        if(response.trim()=="ok") {
		                toast('gespeichert');
		                refreshPage();
                        } else {
                            toast('response:'+response);
                        }
	            }
                });
            }
        }
        function delete_timer(id) {
            $.ajax({
	            url: "edit_timer.php",
	            type: "POST",
	            data: "action=delete&id="+id,
                async: true,
	            success: function(response) {
		            if(response.trim()=="ok") {
		                $.mobile.changePage('#timer', {
                            transition: "slide",
                            reverse: true
                        });
		                toast('gelöscht');
		                refreshPage();
                    } else {
                        toast('response:'+response);
                    }
	            }
            });
        }
    </script>
</head>




<body>










<div data-role="page" id="favorites" class="ui-responsive-panel" data-theme="<?php echo $theme_page; ?>">

    <div data-role="panel" id="mypanel" data-position="left" data-display="push" data-animate="<?php echo $menuAnimated; ?>" data-theme="a" data-position-fixed="true">
        <center>
            <a href="#favorites" data-role="button" data-theme="e" class="ui-disabled">Favoriten</a>
            <!--a href="#my-header" data-rel="close" data-role="button" data-theme="b">Favoriten</a-->
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
<?php if($xml->gui->showAllOnOffBtnInMenu == "true") { ?>
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
<?php } ?>
            <a href="#favorites" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Favoriten</h1>
        <div data-type="horizontal" data-role="controlgroup"  class="ui-btn-right"> 
            <a href="#" id="editButton" data-role="button" data-iconpos="notext" data-icon="edit" onClick="showEditButtons();"></a>
            <a href="#newdevice" id="newButton" data-transition="slide" data-role="button" data-iconpos="notext" data-icon="plus"></a>
        </div>              
    </div><!-- /header -->

    <div data-role="content" id="content">  
        <ul id="favlist" data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-inset="false">

            <li data-role="list-divider" role="heading">
                Gruppen
            </li>
 
<?php
        $groupsFound = $xml->xpath("//groups/group/favorite[text()='true']/parent::*");
        switch ($xml->gui->sortOrderGroups){
            case "SORT_BY_NAME":
                usort($groupsFound, "compareGroupsByName");
                break;
            case "SORT_BY_ID":
                usort($groupsFound, "compareGroupsByID");
                break;
            default:
                break;
        }
        foreach($groupsFound as $group) {
?>

            <li>
                <div class="ui-grid-a">
	                <div class="ui-block-a" style="text-align:left">
	                    <h2><?php echo $group->name; ?></h2>
<?php
                        foreach($group->deviceid as $deviceid) {
                            $devicesFound = $xml->xpath("//devices/device/id[text()='".$deviceid."']/parent::*");
                            echo "<p>".$devicesFound[0]->name."</p>";
                        }
?>
	                </div>
	                <div class="ui-block-b" style="text-align:right">
                        <div class="box-btn-switch">
                            <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on','group','<?php echo $group->id; ?>')"><?php echo empty($device['buttonLabelOn']) ? 'EIN' : $device['buttonLabelOn']; ?></button>
                            <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off','group','<?php echo $group->id; ?>')"><?php echo empty($device['buttonLabelOff']) ? 'AUS' : $device['buttonLabelOff']; ?></button>
	                    </div>
	                    <div class="box-btn-edit hide">
	                        <button data-theme="b" data-iconpos="notext" data-icon="edit" data-mini="true" data-inline="true" onclick="edit_group('<?php echo $group->id; ?>')">Bearbeiten</button>
	                        <button data-theme="r" data-iconpos="notext" data-icon="delete" data-mini="true" data-inline="true" onclick="delete_group('<?php echo $group->id; ?>')">Löschen</button>
	                     </div>
	                </div>
                </div>
            </li>
     
<?php
    }
?>

            <li data-role="list-divider" role="heading">
                Geräte
            </li>

<?php
        $devicesFound = $xml->xpath("//devices/device/favorite[text()='true']/parent::*");
        switch ($xml->gui->sortOrderDevices){
            case "SORT_BY_NAME":
                usort($devicesFound, "compareDevicesByName");
                break;
            case "SORT_BY_ID":
                usort($devicesFound, "compareDevicesByID");
                break;
            default:
                break;
        }
        foreach($devicesFound as $device) {

            switch ($xml->gui->showDeviceStatus) {
                case "ROW_COLOR":
                    $rowOnDataTheme="g";
                    $rowOffDataTheme="r";
                    if($device->status=='ON') {
                        $rowDataTheme=$rowOnDataTheme;
                    } else {
                        $rowDataTheme=$rowOffDataTheme;
                    }
                    $btnOnDataTheme="g";
                    $btnOffDataTheme="r";
                    $btnOnIcon="";
                    $btnOnJS="send_connair('on','device','".$device->id."'); switchRowTheme('on','".$device->id."','".$rowOnDataTheme."','".$rowOffDataTheme."')";
                    $btnOffJS="send_connair('off','device','".$device->id."'); switchRowTheme('off','".$device->id."','".$rowOnDataTheme."','".$rowOffDataTheme."')";
                break;
                case "BUTTON_COLOR":
                    $rowDataTheme=$theme_row;
                    $btnOnColor="g";
                    $btnOffColor="r";
                    $btnCurColor="e";
                    if($device->status=='ON') {
                        $btnOnDataTheme=$btnOnColor;
                        $btnOffDataTheme=$btnCurColor;
                    } else {
                        $btnOnDataTheme=$btnCurColor;
                        $btnOffDataTheme=$btnOffColor;
                    }
                    $btnOnIcon="";
                    $btnOnJS="send_connair('on','device','".$device->id."'); switchButtonTheme('on','".$device->id."','".$btnOnColor."','".$btnOffColor."','".$btnCurColor."')";
                    $btnOffJS="send_connair('off','device','".$device->id."'); switchButtonTheme('off','".$device->id."','".$btnOnColor."','".$btnOffColor."','".$btnCurColor."')";
                break;
                case "BUTTON_ICON":
                    $onIcon="check";
                    $offIcon="off";
                    $rowDataTheme=$theme_row;
                    $btnOnDataTheme="g";
                    $btnOffDataTheme="r";
                    if($device->status=='ON') {
                        $btnOnIcon=$onIcon;
                    } else {
                        $btnOnIcon=$offIcon;
                    }
                    $btnOnJS="send_connair('on','device','".$device->id."'); switchButtonIcon('on','".$device->id."','".$onIcon."','".$offIcon."')";
                    $btnOffJS="send_connair('off','device','".$device->id."'); switchButtonIcon('off','".$device->id."','".$onIcon."','".$offIcon."')";
                break;
                default:
                    $rowDataTheme=$theme_row;
                    $btnOnDataTheme="g";
                    $btnOffDataTheme="r";
                    $btnOnIcon="";
                    $btnOnJS="send_connair('on','device','".$device->id."')";
                    $btnOffJS="send_connair('off','device','".$device->id."')";
                break;
            }

?>

                <li id="deviceRow<?php echo $device->id; ?>" data-theme="<?php echo $rowDataTheme; ?>">
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left">
	                        <h2><?php echo $device->name; ?></h2>
                            <p><?php echo $device->room; ?></p>
	                    </div>
	                    <div class="ui-block-b" style="text-align:right">
                            <div class="box-btn-switch">
	                            <button id="btnOn<?php echo $device->id; ?>" data-theme="<?php echo $btnOnDataTheme; ?>" data-mini="true" data-inline="true" <?php if(!empty($btnOnIcon)) { echo 'data-icon="'.$btnOnIcon.'"'; } ?> onclick="<?php echo $btnOnJS; ?>"><?php echo empty($device['buttonLabelOn']) ? 'EIN' : $device['buttonLabelOn']; ?></button>
<?php
    if($device["hideButtonOff"] != "yes") {
?>
	                            <button id="btnOff<?php echo $device->id; ?>" data-theme="<?php echo $btnOffDataTheme; ?>" data-mini="true" data-inline="true" onclick="<?php echo $btnOffJS; ?>"><?php echo empty($device['buttonLabelOff']) ? 'AUS' : $device['buttonLabelOff']; ?></button>
<?php
    }
?>
	                        </div>
	                        <div class="box-btn-edit hide">
	                            <button data-theme="b" data-iconpos="notext" data-icon="edit" data-mini="true" data-inline="true" onclick="edit_device('<?php echo $device->id; ?>')">Bearbeiten</button>
	                            <button data-theme="r" data-iconpos="notext" data-icon="delete" data-mini="true" data-inline="true" onclick="delete_device('<?php echo $device->id; ?>')">Löschen</button>
	                        </div>
	                    </div>
                    </div>
                </li>

<?php
        }
?>


        </ul>
    </div><!-- /content -->
</div><!-- /page -->









<div data-role="page" id="devices" class="ui-responsive-panel" data-theme="<?php echo $theme_page; ?>">

    <div data-role="panel" id="mypanel" data-position="left" data-display="push" data-animate="<?php echo $menuAnimated; ?>" data-theme="a" data-position-fixed="true">
	    <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e" class="ui-disabled">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
<?php if($xml->gui->showAllOnOffBtnInMenu == "true") { ?>
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
<?php } ?>
            <a href="#devices" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->
 
       
    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Geräte</h1>
        <div data-type="horizontal" data-role="controlgroup"  class="ui-btn-right"> 
            <a href="#" id="editButton" data-role="button" data-iconpos="notext" data-icon="edit" onClick="showEditButtons();"></a>
            <a href="#newdevice" id="newDeviceButton" data-transition="slide" data-role="button" data-iconpos="notext" data-icon="plus"></a>
        </div>
    </div><!-- /header -->


    <div data-role="content">  
        <ul data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-filter-theme="<?php echo $theme_divider; ?>" data-inset="false" data-filter="true" data-filter-placeholder="Geräte suchen...">

<?php
    $roomDevices = array();
    foreach($xml->devices->device as $device) {
        $curRoom = (string)$device->room;
        if(!array_key_exists($curRoom, $roomDevices)) {
            $roomDevices[$curRoom] = array();
        }
        $roomDevices[$curRoom][] = $device;
    }
    switch ($xml->gui->sortOrderRooms){
        case "SORT_BY_NAME":
            ksort($roomDevices);
            break;
        default:
            break;
    }
    foreach($roomDevices as $room => $devices) {
        switch ($xml->gui->sortOrderDevices){
            case "SORT_BY_NAME":
                usort($devices, "compareDevicesByName");
                break;
            case "SORT_BY_ID":
                usort($devices, "compareDevicesByID");
                break;
            default:
                break;
        }
?>

            <li data-role="list-divider" role="heading">
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left"><?php echo $room; ?></div>
	                    <div class="ui-block-b" style="text-align:right">
<?php
    if($xml->gui->showRoomButtonInDevices == "true") {
?>
                            <div class="box-btn-switch">
	                            <button data-theme="<?php echo $theme_row; ?>" data-mini="true" data-inline="true" onclick="send_connair('on','room','<?php echo $room; ?>')">EIN</button>
	                            <button data-theme="<?php echo $theme_row; ?>" data-mini="true" data-inline="true" onclick="send_connair('off','room','<?php echo $room; ?>')">AUS</button>
	                        </div>
<?php
    }
?>
	                    </div>
                    </div>
            </li>

<?php
        foreach($devices as $device) {

        switch ($xml->gui->showDeviceStatus){
            case "ROW_COLOR":
                $rowOnDataTheme="g";
                $rowOffDataTheme="r";
                if($device->status=='ON') {
                    $rowDataTheme=$rowOnDataTheme;
                } else {
                    $rowDataTheme=$rowOffDataTheme;
                }
                $btnOnDataTheme="g";
                $btnOffDataTheme="r";
                $btnOnIcon="";
                $btnOnJS="send_connair('on','device','".$device->id."'); switchRowTheme('on','".$device->id."','".$rowOnDataTheme."','".$rowOffDataTheme."')";
                $btnOffJS="send_connair('off','device','".$device->id."'); switchRowTheme('off','".$device->id."','".$rowOnDataTheme."','".$rowOffDataTheme."')";
            break;
            case "BUTTON_COLOR":
                $rowDataTheme=$theme_row;
                $btnOnColor="g";
                $btnOffColor="r";
                $btnCurColor="e";
                if($device->status=='ON') {
                    $btnOnDataTheme=$btnOnColor;
                    $btnOffDataTheme=$btnCurColor;
                } else {
                    $btnOnDataTheme=$btnCurColor;
                    $btnOffDataTheme=$btnOffColor;
                }
                $btnOnIcon="";
                $btnOnJS="send_connair('on','device','".$device->id."'); switchButtonTheme('on','".$device->id."','".$btnOnColor."','".$btnOffColor."','".$btnCurColor."')";
                $btnOffJS="send_connair('off','device','".$device->id."'); switchButtonTheme('off','".$device->id."','".$btnOnColor."','".$btnOffColor."','".$btnCurColor."')";
            break;
            case "BUTTON_ICON":
                $onIcon="check";
                $offIcon="off";
                $rowDataTheme=$theme_row;
                $btnOnDataTheme="g";
                $btnOffDataTheme="r";
                if($device->status=='ON') {
                    $btnOnIcon=$onIcon;
                } else {
                    $btnOnIcon=$offIcon;
                }
                $btnOnJS="send_connair('on','device','".$device->id."'); switchButtonIcon('on','".$device->id."','".$onIcon."','".$offIcon."')";
                $btnOffJS="send_connair('off','device','".$device->id."'); switchButtonIcon('off','".$device->id."','".$onIcon."','".$offIcon."')";
            break;
            default:
                $rowDataTheme=$theme_row;
                $btnOnDataTheme="g";
                $btnOffDataTheme="r";
                $btnOnIcon="";
                $btnOnJS="send_connair('on','device','".$device->id."')";
                $btnOffJS="send_connair('off','device','".$device->id."')";
            break;
        }

?>

                <li id="deviceRow<?php echo $device->id; ?>" data-theme="<?php echo $rowDataTheme; ?>">
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left">
	                    	<h2><?php echo $device->name; php?></h2>
	                    <?php 
	                    	if($debug == "true") {
	                    		echo "<p><i>".$device->id." ".$device->vendor." ".$device->address->masterdip." ".$device->address->slavedip."</i></p>";
	                    	}
	                    ?>
	                    </div>
	                    <div class="ui-block-b" style="text-align:right">
                            <div class="box-btn-switch">
<?php
    if($device["hideButtonOn"] != "yes") {
?>
	                            <button id="btnOn<?php echo $device->id; ?>" data-theme="<?php echo $btnOnDataTheme; ?>" data-mini="true" data-inline="true" <?php if(!empty($btnOnIcon)) { echo 'data-icon="'.$btnOnIcon.'"'; } ?> onclick="<?php echo $btnOnJS; ?>"><?php echo empty($device['buttonLabelOn']) ? 'EIN' : $device['buttonLabelOn']; ?></button>
<?php
    }
    if($device["hideButtonOff"] != "yes") {
?>
	                            <button id="btnOff<?php echo $device->id; ?>" data-theme="<?php echo $btnOffDataTheme; ?>" data-mini="true" data-inline="true" onclick="<?php echo $btnOffJS; ?>"><?php echo empty($device['buttonLabelOff']) ? 'AUS' : $device['buttonLabelOff']; ?></button>
<?php
    }
?>
	                        </div>
	                        <div class="box-btn-edit hide">
	                            <button data-theme="b" data-iconpos="notext" data-icon="edit" data-mini="true" data-inline="true" onclick="edit_device('<?php echo $device->id; ?>')">Bearbeiten</button>
	                            <button data-theme="r" data-iconpos="notext" data-icon="delete" data-mini="true" data-inline="true" onclick="delete_device('<?php echo $device->id; ?>')">Löschen</button>
	                        </div>
	                    </div>
                    </div>
                </li>

<?php
        }
    }
?>

         </ul>
    </div><!-- /content -->
</div><!-- /page -->








<div data-role="page" id="groups" class="ui-responsive-panel" data-theme="<?php echo $theme_page; ?>">
    
    <div data-role="panel" id="mypanel" data-position="left" data-display="push" data-animate="<?php echo $menuAnimated; ?>" data-theme="a" data-position-fixed="true">
        <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e" class="ui-disabled">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
<?php if($xml->gui->showAllOnOffBtnInMenu == "true") { ?>
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
<?php } ?>
            <a href="#groups" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Gruppen</h1>
        <div data-type="horizontal" data-role="controlgroup"  class="ui-btn-right"> 
            <a href="#" id="editButton" data-role="button" data-iconpos="notext" data-icon="edit" onClick="showEditButtons();"></a>
            <a href="#newgroup" id="newGroupButton" data-rel="dialog" data-role="button" data-iconpos="notext" data-icon="plus"></a>
        </div>
    </div><!-- /header -->

    <div data-role="content">  

        <ul data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-inset="false">
 
<?php
    $groups = array();
    foreach($xml->groups->group as $group) {
        $groups[] = $group;
    }
    switch ($xml->gui->sortOrderGroups){
        case "SORT_BY_NAME":
            usort($groups, "compareGroupsByName");
            break;
        case "SORT_BY_ID":
            usort($groups, "compareGroupsByID");
            break;
        default:
            break;
    }
    foreach($groups as $group) {
?>

            <li>
                <div class="ui-grid-a">
	                <div class="ui-block-a" style="text-align:left">
	                    <h2><?php echo $group->name; ?></h2>
<?php
        foreach($group->deviceid as $deviceid) {
            $devicesFound = $xml->xpath("//devices/device/id[text()='".$deviceid."']/parent::*");
            $device = $devicesFound[0];
            $text = $device->name;
            if(!empty($deviceid['onaction'])) {
                if($deviceid['onaction'] == "on") {
                    $text = $text."<small> [ <i><font color=#3A7315>on</font></i> ]</small>";
                } else if($deviceid['onaction'] == "off") {
                    $text = $text."<small> [ <i><font color=#3A7315>off</font></i> ]</small>";
                } else if($deviceid['onaction'] == "none") {
                    $text = $text."<small> [ <i><font color=#3A7315>none</font></i> ]</small>";
                }
            }
            if(!empty($deviceid['offaction'])) {
                if($deviceid['offaction'] == "on") {
                    $text = $text."<small> [ <i><font color=#731515>on</font></i> ]</small>";
                } else if($deviceid['offaction'] == "off") {
                    $text = $text."<small> [ <i><font color=#731515>off</font></i> ]</small>";
                } else if($deviceid['offaction'] == "none") {
                    $text = $text."<small> [ <i><font color=#731515>none</font></i> ]</small>";
                }
            }
            echo "<p>".$text."</p>";
        }
?>
	                </div>
	                <div class="ui-block-b" style="text-align:right">
	                    <div class="box-btn-switch">
                            <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on','group','<?php echo $group->id; ?>')"><?php echo empty($device['buttonLabelOn']) ? 'EIN' : $device['buttonLabelOn']; ?></button>
                            <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off','group','<?php echo $group->id; ?>')"><?php echo empty($device['buttonLabelOff']) ? 'AUS' : $device['buttonLabelOff']; ?></button>
                        </div>
	                    <div class="box-btn-edit hide">
	                        <button data-theme="b" data-iconpos="notext" data-icon="edit" data-mini="true" data-inline="true" onclick="edit_group('<?php echo $group->id; ?>')">Bearbeiten</button>
	                        <button data-theme="r" data-iconpos="notext" data-icon="delete" data-mini="true" data-inline="true" onclick="delete_group('<?php echo $group->id; ?>')">Löschen</button>
	                     </div>
	                </div>
                </div>
            </li>
     
<?php
    }
?>

        </ul>
    </div><!-- /content -->
</div><!-- /page -->









<div data-role="page" id="rooms" class="ui-responsive-panel" data-theme="<?php echo $theme_page; ?>">

    <div data-role="panel" id="mypanel" data-position="left" data-display="push" data-animate="<?php echo $menuAnimated; ?>" data-theme="a" data-position-fixed="true">
	    <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e" class="ui-disabled">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
<?php if($xml->gui->showAllOnOffBtnInMenu == "true") { ?>
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
<?php } ?>
            <a href="#rooms" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->
 
       
    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Räume</h1>
    </div><!-- /header -->


    <div data-role="content">  
        <ul data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-inset="false">

<?php
    $roomDevices = array();
    foreach($xml->devices->device as $device) {
        $curRoom = (string)$device->room;
        if(!array_key_exists($curRoom, $roomDevices)) {
            $roomDevices[$curRoom] = array();
        }
        $roomDevices[$curRoom][] = $device;
    }
    switch ($xml->gui->sortOrderRooms){
        case "SORT_BY_NAME":
            ksort($roomDevices);
            break;
        default:
            break;
    }
    foreach($roomDevices as $room => $devices) {
?>

                <li>
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left"><?php echo $room; ?></div>
	                    <div class="ui-block-b" style="text-align:right">
	                        <div class="box-btn-switch">
	                            <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on','room','<?php echo $room; ?>')">EIN</button>
	                            <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off','room','<?php echo $room; ?>')">AUS</button>
	                        </div>
	                    </div>
                    </div>
                </li>

<?php
    }
?>
   
         </ul>
    </div><!-- /content -->
</div><!-- /page -->









<style type="text/css">

.timer_activ_on  {
	color: #6AB638;
}

.timer_activ_off  {
	color: #B63737;
}

</style>
<div data-role="page" id="timers" class="ui-responsive-panel" data-theme="<?php echo $theme_page; ?>">

    <div data-role="panel" id="mypanel" data-position="left" data-display="push" data-animate="<?php echo $menuAnimated; ?>" data-theme="a" data-position-fixed="true">
       <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e" class="ui-disabled">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
<?php if($xml->gui->showAllOnOffBtnInMenu == "true") { ?>
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
<?php } ?>
            <a href="#timers" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->
 
       
    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Timer</h1>
        <div data-type="horizontal" data-role="controlgroup"  class="ui-btn-right"> 
            <a href="#" id="editButton" data-role="button" data-iconpos="notext" data-icon="edit" onClick="showEditButtons();"></a>
            <a href="#newtimer" id="newTimerButton" data-transition="slide" data-role="button" data-iconpos="notext" data-icon="plus" onclick="sessionStorage.TimerID=''"></a>
        </div>
    </div><!-- /header -->


    <div data-role="content"> 
        <ul data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-inset="false">

<?php
    $timers = array();
    foreach($xml->timers->timer as $timer) {
        $timers[] = $timer;
    }
    switch ($xml->gui->sortOrderTimers){
        case "SORT_BY_NAME":
            usort($timers, "compareTimersByName");
            break;
        case "SORT_BY_ID":
            usort($timers, "compareTimersByID");
            break;
        case "SORT_BY_TYPE_AND_NAME":
            usort($timers, "compareTimersByTypeAndName");
            break;
        default:
            break;
    }
    foreach($timers as $timer) {
?>

               <!-- <li><a href="#newtimer" data-transition="slide" onclick="sessionStorage.TimerID=<?php echo $timer->id; ?>"> -->
                <li>
                <div class="ui-grid-a">
                <div class="ui-block-a" style="text-align:left">
<?php
    switch ($timer->active) {
        case "on":
            echo "<h2 class='timer_activ_on'>";
            break;
        case "off":
        default:
            echo "<h2 class='timer_activ_off'>";
            break;
    }
?>
<?php
    if($timer->type=="device") {
        foreach($xml->devices->device as $tmp_device) {
            //echo $timer->tid."-".$tmp_device->id."<br>";
            if ((string)$timer->typeid === (string)$tmp_device->id) {
                echo $tmp_device->name;
                $tmp_room = $tmp_device->room;
            }      
        }
    }
    if($timer->type=="group") {
        foreach($xml->groups->group as $tmp_group) {
            //echo $timer->tid."-".$tmp_device->id."<br>";
            if ((string)$timer->typeid === (string)$tmp_group->id) {
                echo $tmp_group->name;
            }      
        }
    }
    if($timer->type=="room") {
        echo $timer->typeid;
    }
?>
</h2>
                     <p><b>Aktiv: </b>
<?php
    switch ($timer->active) {
        case "on":
            echo "Ja";
            break;
        case "off":
        default:
            echo "Nein";
            break;
    }
?>
                    </p>
                    <p><b>Typ: </b>
<?php
    switch ($timer->type) {
        case "device":
            echo "Gerät";
            break;
        case "group":
            echo "Gruppe";
            break;
        case "room":
            echo "Raum";
            break;
        default:
            echo "unbekannt";
            break;
    }
?>
                    </p>
<?php 
                    if($timer->type=="device") {
                       echo "<p><b>Raum: </b>".$tmp_room."</p>";
                    }
?>
                    <p><b>Tage: </b>
<?php 
    echo $timer->day;
?>                             
                    </p>
                    <p><b>An: </b>
<?php
    switch ($timer->timerOn) {
        case "SD":
            echo "Sonnenuntergang";
            if(!empty($timer->timerOn["offset"])) { echo "  <i>(".$timer->timerOn["offset"]." Minuten)</i>"; }
            break;
        case "SU":
            echo "Sonnenaufgang";
            if(!empty($timer->timerOn["offset"])) { echo "  <i>(".$timer->timerOn["offset"]." Minuten)</i>"; }
            break;
        default:
            echo $timer->timerOn." Uhr";
            break;
    }
?>
                    </p>
                    <p><b>Aus: </b>
<?php
    switch ($timer->timerOff) {
        case "SD":
            echo "Sonnenuntergang";
            if(!empty($timer->timerOff["offset"])) { echo "  mit einem Versatz von ".$timer->timerOff["offset"]." Minuten"; }
            break;
        case "SU":
            echo "Sonnenaufgang";
            if(!empty($timer->timerOff["offset"])) { echo "  mit einem Versatz von ".$timer->timerOff["offset"]." Minuten"; }
            break;
        default:
            echo $timer->timerOff." Uhr";
            break;
    }
?>
                    </p>
                    
                </div>
                <div class="ui-block-b" style="text-align:right">
                    <div class="box-btn-switch">
                        <button data-theme="g"  data-mini="true" data-inline="true" onclick="edit_timer('<?php echo $timer->id; ?>','EIN')">EIN</button>
                        <button data-theme="r"  data-mini="true" data-inline="true" onclick="edit_timer('<?php echo $timer->id; ?>','AUS')">AUS</button>
                    </div>
                    <div class="box-btn-edit hide">
                        <button data-theme="b" data-iconpos="notext" data-icon="edit" data-mini="true" data-inline="true" onclick="edit_timer('<?php echo $timer->id; ?>')">Bearbeiten</button>
                        <button data-theme="r" data-iconpos="notext" data-icon="delete" data-mini="true" data-inline="true" onclick="delete_timer('<?php echo $timer->id; ?>')">Löschen</button>
                     </div>
                </div>
                </div>
                </li>
                <!-- </a></li> -->

<?php
    }
?>
   
         </ul>
    </div><!-- /content -->
</div><!-- /page -->










<script type="text/JavaScript">
function resetEditConfigForm() {
    $('#editconfigform')[0].reset();
}

$(document).ready(function() {
    $("#currentPosition").click(function() {
        navigator.geolocation.getCurrentPosition(function(position) {
            $.mobile.activePage.find('#latitude').val(position.coords.latitude);
	        $.mobile.activePage.find('#longitude').val(position.coords.longitude);
        });
    });
});
</script>
<div data-role="page" id="configurations" class="ui-responsive-panel" data-theme="<?php echo $theme_page; ?>">

    <div data-role="panel" id="mypanel" data-position="left" data-display="push" data-animate="<?php echo $menuAnimated; ?>" data-theme="a" data-position-fixed="true">
        <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e" class="ui-disabled">Einstellungen</a>
            <br />
<?php if($xml->gui->showAllOnOffBtnInMenu == "true") { ?>
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
<?php } ?>
            <a href="#configurations" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel" data-role="button" onClick="resetEditConfigForm();">Menu</a>
        <h1>Einstellungen</h1>
        <a href="#" id="editconfigsubmit" data-role="button" data-theme="g">Speichern</a>
    </div><!-- /header -->

   
    <div data-role="content">  
        
        
<form id="editconfigform" method="post" data-ajax="false">
<input type="hidden" name="action" id="action" value="edit" />
    <ul data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-inset="false">
        <li data-role="list-divider">
        ConnAir
        </li>
        <li data-role="fieldcontain">
            <label for="connairIP">IP-Adresse:</label>
            <input name="connairIP" id="connairIP" value="<?php echo $xml->connairs->connair->address; ?>" data-clear-btn="true" type="text">
        </li>
		<li data-role="fieldcontain">
            <label for="connairPort">Port:</label>
            <input name="connairPort" id="connairPort" value="<?php echo $xml->connairs->connair->port; ?>" data-clear-btn="true" type="text">
        </li>
        <li data-role="list-divider">
        Global
        </li>
        <li data-role="fieldcontain">
            <label for="timezone">Zeitzone:</label>
            <input name="timezone" id="timezone" value="<?php echo $xml->global->timezone; ?>" data-clear-btn="true" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="longitude">Longitude:</label>
            <input name="longitude" id="longitude" value="<?php echo $xml->global->longitude; ?>" data-clear-btn="true" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="latitude">Latitude:</label>
            <input name="latitude" id="latitude" value="<?php echo $xml->global->latitude; ?>" data-clear-btn="true" type="text">
        </li>
        <li data-role="fieldcontain">
            <input name="currentPosition" id="currentPosition" value="Aktuelle Position verwenden" type="button">
        </li>
        <li data-role="fieldcontain">
            <label for="multiDeviceSleep">Wartezeit beim Senden (ms):</label>
            <input type="range" name="multiDeviceSleep" id="multiDeviceSleep" value="<?php echo $xml->global->multiDeviceSleep; ?>" min="200" max="5000" step="50" />
        </li>
        <li data-role="fieldcontain">
            <label for="timerRunOnce">Timer schaltet nur wenn nötig:</label>
            <select name="timerRunOnce" id="timerRunOnce" data-role="slider">
                <option value="false" <?php if($xml->global->timerRunOnce == "false") { echo "selected"; } ?>>Off</option>
                <option value="true" <?php if($xml->global->timerRunOnce == "true") { echo "selected"; } ?>>On</option>
            </select>
        </li>
        <li data-role="list-divider">
        GUI
        </li>
        <li data-role="fieldcontain">
            <label for="theme">Theme:</label>
            <select name="theme" id="theme">
                <option value="LIGHT" <?php if($xml->gui->theme == "LIGHT") { echo "selected"; } ?>>Hell</option>
                <option value="DARK" <?php if($xml->gui->theme == "DARK") { echo "selected"; } ?>>Dunkel</option>
            </select>
        </li>
        <li data-role="fieldcontain">
            <label for="showDeviceStatus">Zeige Geräte Status:</label>
            <select name="showDeviceStatus" id="showDeviceStatus">
                <option value="OFF" <?php if($xml->gui->showDeviceStatus == "OFF") { echo "selected"; } ?>>OFF</option>
                <option value="ROW_COLOR" <?php if($xml->gui->showDeviceStatus == "ROW_COLOR") { echo "selected"; } ?>>ROW_COLOR</option>
                <option value="BUTTON_COLOR" <?php if($xml->gui->showDeviceStatus == "BUTTON_COLOR") { echo "selected"; } ?>>BUTTON_COLOR</option>
                <option value="BUTTON_ICON" <?php if($xml->gui->showDeviceStatus == "BUTTON_ICON") { echo "selected"; } ?>>BUTTON_ICON</option>
            </select>
        </li>
        <li data-role="fieldcontain">
            <label for="showRoomButtonInDevices">Zeige Raum Schalter in der Geräteübersicht:</label>
            <select name="showRoomButtonInDevices" id="showRoomButtonInDevices" data-role="slider">
                <option value="false" <?php if($xml->gui->showRoomButtonInDevices == "false") { echo "selected"; } ?>>Off</option>
                <option value="true" <?php if($xml->gui->showRoomButtonInDevices == "true") { echo "selected"; } ?>>On</option>
            </select>
        </li>
        <li data-role="fieldcontain">
            <label for="showMenuOnLoad">Zeige das Menu beim Starten:</label>
            <select name="showMenuOnLoad" id="showMenuOnLoad" data-role="slider">
                <option value="false" <?php if($xml->gui->showMenuOnLoad == "false") { echo "selected"; } ?>>Off</option>
                <option value="true" <?php if($xml->gui->showMenuOnLoad == "true") { echo "selected"; } ?>>On</option>
            </select>
        </li>
        <li data-role="fieldcontain">
            <label for="showAllOnOffBtnInMenu">Zeige Alle EIN/AUS Button im Menu:</label>
            <select name="showAllOnOffBtnInMenu" id="showAllOnOffBtnInMenu" data-role="slider">
                <option value="false" <?php if($xml->gui->showAllOnOffBtnInMenu == "false") { echo "selected"; } ?>>Off</option>
                <option value="true" <?php if($xml->gui->showAllOnOffBtnInMenu == "true") { echo "selected"; } ?>>On</option>
            </select>
        </li>
        <li data-role="fieldcontain">
            <label for="sortOrderDevices" class="select">Sortierung der Geräte:</label>
            <select name="sortOrderDevices" id="sortOrderDevices">
                <option value="SORT_BY_NAME" <?php if($xml->gui->sortOrderDevices == "SORT_BY_NAME") { echo "selected"; } ?>>SORT_BY_NAME</option>
                <option value="SORT_BY_ID" <?php if($xml->gui->sortOrderDevices == "SORT_BY_ID") { echo "selected"; } ?>>SORT_BY_ID</option>
                <option value="SORT_BY_XML" <?php if($xml->gui->sortOrderDevices != "SORT_BY_NAME" && $xml->gui->sortOrderDevices != "SORT_BY_ID") { echo "selected"; } ?>>SORT_BY_XML</option>
            </select>
        </li>
        <li data-role="fieldcontain">
            <label for="sortOrderGroups" class="select">Sortierung der Gruppen:</label>
            <select name="sortOrderGroups" id="sortOrderGroups">
                <option value="SORT_BY_NAME" <?php if($xml->gui->sortOrderGroups == "SORT_BY_NAME") { echo "selected"; } ?>>SORT_BY_NAME</option>
                <option value="SORT_BY_XML" <?php if($xml->gui->sortOrderDevices != "SORT_BY_NAME") { echo "selected"; } ?>>SORT_BY_XML</option>
            </select>
        </li>
        <li data-role="fieldcontain">
            <label for="sortOrderRooms" class="select">Sortierung der Räume:</label>
            <select name="sortOrderRooms" id="sortOrderRooms">
                <option value="SORT_BY_NAME" <?php if($xml->gui->sortOrderRooms == "SORT_BY_NAME") { echo "selected"; } ?>>SORT_BY_NAME</option>
                <option value="SORT_BY_XML" <?php if($xml->gui->sortOrderDevices != "SORT_BY_NAME") { echo "selected"; } ?>>SORT_BY_XML</option>
            </select>
        </li>
        <li data-role="fieldcontain">
            <label for="sortOrderTimers" class="select">Sortierung der Timer:</label>
            <select name="sortOrderTimers" id="sortOrderTimers">
                <option value="SORT_BY_NAME" <?php if($xml->gui->sortOrderTimers == "SORT_BY_NAME") { echo "selected"; } ?>>SORT_BY_NAME</option>
                <option value="SORT_BY_ID" <?php if($xml->gui->sortOrderTimers == "SORT_BY_ID") { echo "selected"; } ?>>SORT_BY_ID</option>
                <option value="SORT_BY_TYPE_AND_NAME" <?php if($xml->gui->sortOrderTimers == "SORT_BY_TYPE_AND_NAME") { echo "selected"; } ?>>SORT_BY_TYPE_AND_NAME</option>
                <option value="SORT_BY_XML" <?php if($xml->gui->sortOrderDevices != "SORT_BY_NAME" && $xml->gui->sortOrderDevices != "SORT_BY_ID" && $xml->gui->sortOrderDevices != "SORT_BY_TYPE_AND_NAME") { echo "selected"; } ?>>SORT_BY_XML</option>
            </select>
        </li>

        <li data-role="list-divider">
        System Informationen
        </li>
        <li data-role="fieldcontain">
            <label for="mcversion">Entwickler:</label>
            <input name="mcversion" id="mcversion"  value="mentox" type="text">
        </li>
        <li data-role="fieldcontain">
            <input name="mcsite" id="mcsite" value="Webseite besuchen" type="button" data-theme="g" onClick="(window.open('http://www.l3x.de/connair/','mcsite'))">
        </li>
        <li data-role="fieldcontain">
            <input name="mcsite" id="mcsite" value="E-Mail schreiben" type="button" onClick="location='mailto:connair@l3x.de?subject=Mobile%20Connair'">
        </li>
        <li data-role="fieldcontain">
            <label for="mcversion">Mobile Connair Version:</label>
            <input name="mcversion" id="mcversion"  value="0.6.6" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="serversoftware">Server Software:</label>
            <input name="serversoftware" id="serversoftware"  value="<?php echo $_SERVER['SERVER_SOFTWARE']; ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="phpversion">PHP Version:</label>
            <input name="phpversion" id="phpversion"  value="<?php echo phpversion(); ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="time">Server Zeit:</label>
            <input name="time" id="time"  value="<?php echo date("Y-m-d H:i:s"); ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="timezone">Server Zeitzone:</label>
            <input name="timezone" id="timezone"  value="<?php echo date_default_timezone_get(); ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="longitude">Longitude:</label>
            <input name="longitude" id="longitude"  value="<?php echo $longitude; ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="latitude">Latitude:</label>
            <input name="latitude" id="latitude"  value="<?php echo $latitude; ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="sunrise">Sonnenaufgang:</label>
            <input name="sunrise" id="sunrise"  value="<?php echo date('H:i', $sunrise); ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="sunset">Sonnenuntergang:</label>
            <input name="sunset" id="sunset"  value="<?php echo date('H:i', $sunset); ?>" type="text">
        </li>
        <li data-role="list-divider">
        Debug
        </li>
        <li data-role="fieldcontain">
            <label for="debug">Global:</label>
            <select name="debug" id="debug" data-role="slider">
                <option value="false" <?php if($xml["debug"] == "false") { echo "selected"; } ?>>Off</option>
                <option value="true" <?php if($xml["debug"] == "true") { echo "selected"; } ?>>On</option>
            </select>
        </li>
        <li data-role="fieldcontain">
            <label for="debug_timer">Timer:</label>
            <select name="debug_timer" id="debug_timer" data-role="slider">
                <option value="false" <?php if($xml->timers["debug"] == "false") { echo "selected"; } ?>>Off</option>
                <option value="true" <?php if($xml->timers["debug"] == "true") { echo "selected"; } ?>>On</option>
            </select>
            <div>Ausgaben in die debug.log erscheinen nur wenn der globale Debug-Schalter auch an ist.</div>
        </li>
        <li>
            <a href="#debug">show debug.log</a>
        </li>
        <li data-role="list-divider">
        Editor
        </li>
        <li>
            <a href="editor.php" rel="external">Open config.xml</a>
        </li>
    </ul>
</form>


    </div><!-- /content -->
</div><!-- /page -->









<script type="text/JavaScript">
$(document).ready(function() {
    $("#vendor").change(function() {
        if ($(this).val() == "Brennenstuhl" || $(this).val() == "Elro") {
            $("#dip_switch_box").removeClass().addClass('show');
        } else {
            $("#dip_switch_box").removeClass().addClass('hide');
        }
    });
});
function resetNewDeviceForm() {
    $('#newdeviceform')[0].reset();
    $("#vendor").trigger('change');
}
</script>
<div data-role="page" id="newdevice" data-theme="<?php echo $theme_page; ?>">

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#devices" data-transition="slide" data-direction="reverse" data-role="button" data-theme="r" onClick="resetNewDeviceForm();">Abbrechen</a>
        <h1>Neues Gerät</h1>
        <a href="#" id="newdevicesubmit" data-role="button" data-theme="g">Speichern</a>
    </div><!-- /header -->

    <div data-role="content">
        <form id="newdeviceform" method="post" data-ajax="false">
            <input type="hidden" name="action" id="action" value="add" />
    <ul data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-inset="false">
        <li data-role="fieldcontain">
	            <label for="name">Name:</label>
	            <input type="text" name="name" id="name" value="" />
        </li>
        <li data-role="fieldcontain">
	            <label for="room">Raum:</label>
	            <input type="text" name="room" id="room" value="" />
        </li>
        <li data-role="fieldcontain">
                    <label for="vendor">Hersteller:</label>
                    <select name="vendor" id="vendor">
                        <option value="Brennenstuhl">Brennenstuhl</option>
                        <option value="Elro">Elro</option>
                        <option value="Intertechno">Intertechno</option>
                    </select>
        </li>
	            
	        <style type="text/css">

.desc, .titles {
	font-family: Verdana, Arial, Helvetica, sans-serif;
	letter-spacing: 0;
	font-size: 11px;
	letter-spacing: 0;
}

.switch {
  margin-left: auto ;
  margin-right: auto ;
	display: block;
	float: left;
	background: #AD2929;
	width: 260px;
	#width: 90%;
	height: 80px;
	padding: 5px;
	border: 1px solid #333;
}

.switch_box {
  margin-left: auto ;
  margin-right: auto ;
	width: 100%;
}

.titles {
	display: block;
	height: 26px;
	font-weight: bold;
	color: #fff;
}

.title_left {
	float: left;
	width: 100px;
}

.title_right {
	float: right;
	text-align: right;
}

.dip {
	float: left;
	margin: 0px 5px;
	width: 16px;
	#width: 7%;
	height: 40px;
	display: block;
	text-align: center;
	color: #ffffff;
	font-weight: bold;
}

.dip_bar {
  margin-left: auto ;
  margin-right: auto ;
	#width: 89%;
}

.dip input {
	border: none;
}

.on, .off {
	float: left;
	display: block;
	height: 12px;
	width: 15px;
	border: 1px solid #999999;
	background: #ffffff;
	margin: 0 0 5px 0;
}

.on  {
	border-bottom: 15px solid #C24949;
}

.off  {
	border-top: 15px solid #C24949;
}

.clear {
	clear: both;
}
</style>
<script type="text/JavaScript">

function updateDIPTextField () {
	var masterdip="";
	masterdip+=$("#dip_switch0").children().val();
	masterdip+=$("#dip_switch1").children().val();
	masterdip+=$("#dip_switch2").children().val();
	masterdip+=$("#dip_switch3").children().val();
	masterdip+=$("#dip_switch4").children().val();
    $("#masterdip").val(masterdip);

	var slavedip="";
	slavedip+=$("#dip_switch5").children().val();
	slavedip+=$("#dip_switch6").children().val();
	slavedip+=$("#dip_switch7").children().val();
	slavedip+=$("#dip_switch8").children().val();
	slavedip+=$("#dip_switch9").children().val();
    $("#slavedip").val(slavedip);
}


$(document).ready(function() {
    $("[name=dip_switch]").each(function() {
        $(this).click(function() {
            var input=$(this).children();
            if ($(this).hasClass('off')) {
                $(this).removeClass().addClass('on');
                input.val("1");
            } else {
                $(this).removeClass().addClass('off');
                input.val("0");
            }
            updateDIPTextField();
        });
    });
    updateDIPTextField();
});
            


</script>


        <li data-role="fieldcontain">

<div id="dip_switch_box" class="show">
	            
	            
<div class="switch_box">
<div class="switch">
	<div class="titles">
	    <span class="title_left">ON</span>
	    <span class="title_right">DIP</span>
	</div>
	<div class="dip_bar">
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch0"><input type="hidden" name="b[0]" id="b0" value="1" /></div>
            <span class="desc">1</span>
        </span>
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch1"><input type="hidden" name="b[1]" id="b1" value="1" /></div>
            <span class="desc">2</span>
        </span>
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch2"><input type="hidden" name="b[2]" id="b2" value="1" /></div>
            <span class="desc">3</span>
        </span>
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch3"><input type="hidden" name="b[3]" id="b3" value="1" /></div>
            <span class="desc">4</span>
        </span>
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch4"><input type="hidden" name="b[4]" id="b4" value="1" /></div>
            <span class="desc">5</span>
        </span>
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch5"><input type="hidden" name="b[5]" id="b5" value="1" /></div>
            <span class="desc">A</span>
        </span>
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch6"><input type="hidden" name="b[6]" id="b6" value="1" /></div>
            <span class="desc">B</span>
        </span>
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch7"><input type="hidden" name="b[7]" id="b7" value="1" /></div>
            <span class="desc">C</span>
        </span>
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch8"><input type="hidden" name="b[8]" id="b8" value="1" /></div>
            <span class="desc">D</span>
        </span>
        <span class="dip">
            <div class="on" name="dip_switch" id="dip_switch9"><input type="hidden" name="b[9]" id="b9" value="1" /></div>
            <span class="desc">E</span>
        </span>
    </div>
</div>
</div>
<div class="clear"></div>
	</div>            
	            
	            
	            
	            
	                </li>
        <li data-role="fieldcontain">
    
	            <label for="masterdip">Masterdip:</label>
	            <input type="text" name="masterdip" id="masterdip" value="" />
	           </li>
        <li data-role="fieldcontain">
         <label for="slavedip">Slavedip:</label>
	            <input type="text" name="slavedip" id="slavedip" value="" />
	                 </li>
        <li data-role="fieldcontain">
                <label for="tx433version">Version:</label>
	            <input type="text" name="tx433version" id="tx433version" value="" />
	                  </li>
        <li data-role="fieldcontain">
                        <label for="btnLabelOn">Schalter-Beschriftung EIN:</label>
                        <input type="text" name="btnLabelOn" id="btnLabelOn" value="" placeholder="EIN"/>
	                  </li>
        <li data-role="fieldcontain">
                        <label for="btnLabelOn">Schalter-Beschriftung AUS:</label>
                        <input type="text" name="btnLabelOff" id="btnLabelOff" value="" placeholder="AUS"/>
	                  </li>
        <li data-role="fieldcontain">
                    <label for="favorite">Favorit:</label>
                    <select name="favorite" id="favorite" data-role="slider">
	                    <option value="false">Nein</option>
	                    <option value="true">Ja</option>
                    </select> 
           </li>
    </ul>
            
        </form>
    </div><!-- /content -->
</div><!-- /page -->










<script type="text/JavaScript">
$(document).ready(function() {
    $("#timertype_device").change(function() {
                $("#typeiddevice_box").removeClass().addClass('show');
                $("#typeidgroup_box").removeClass().addClass('hide');
                $("#typeidroom_box").removeClass().addClass('hide');
    });
    $("#timertype_group").change(function() {
                $("#typeiddevice_box").removeClass().addClass('hide');
                $("#typeidgroup_box").removeClass().addClass('show');
                $("#typeidroom_box").removeClass().addClass('hide');
    });
    $("#timertype_room").change(function() {
                $("#typeiddevice_box").removeClass().addClass('hide');
                $("#typeidgroup_box").removeClass().addClass('hide');
                $("#typeidroom_box").removeClass().addClass('show');
    });

    $("#OnTimerType").change(function() {
        var $this = $(this);
        switch($this.val()) {
            case "M":
                $("#timeronmanuell_box").removeClass().addClass('show');
                $("#timerontime_box").removeClass().addClass('hide');
                $("#timeronoffset_box").removeClass().addClass('hide');
                break;
            case "A":
                $("#timeronmanuell_box").removeClass().addClass('hide');
                $("#timerontime_box").removeClass().addClass('show');
                $("#timeronoffset_box").removeClass().addClass('hide');
                break;
            case "SU":
                $("#timeronmanuell_box").removeClass().addClass('hide');
                $("#timerontime_box").removeClass().addClass('hide');
                $("#timeronoffset_box").removeClass().addClass('show');
                break;
            case "SD":
                $("#timeronmanuell_box").removeClass().addClass('hide');
                $("#timerontime_box").removeClass().addClass('hide');
                $("#timeronoffset_box").removeClass().addClass('show');
                break;
        }
    });
    $("#OffTimerType").change(function() {
        var $this = $(this);
        switch($this.val()) {
            case "M":
                $("#timeroffmanuell_box").removeClass().addClass('show');
                $("#timerofftime_box").removeClass().addClass('hide');
                $("#timeroffoffset_box").removeClass().addClass('hide');
                break;
            case "A":
                $("#timeroffmanuell_box").removeClass().addClass('hide');
                $("#timerofftime_box").removeClass().addClass('show');
                $("#timeroffoffset_box").removeClass().addClass('hide');
                break;
            case "SU":
                $("#timeroffmanuell_box").removeClass().addClass('hide');
                $("#timerofftime_box").removeClass().addClass('hide');
                $("#timeroffoffset_box").removeClass().addClass('show');
                break;
            case "SD":
                $("#timeroffmanuell_box").removeClass().addClass('hide');
                $("#timerofftime_box").removeClass().addClass('hide');
                $("#timeroffoffset_box").removeClass().addClass('show');
                break;
        }
    });
});

function resetNewTimerForm() {
    $('#newtimerform')[0].reset();
    $("#timertype_device").trigger('change');
    $("#timertype_group").trigger('change');
    $("#timertype_room").trigger('change');
    $("#OnTimerType").trigger('change');
    $("#OffTimerType").trigger('change');
    $("input[name='timerday[]']").attr({
            checked: $(this).is(':checked')
    });
    $("input[name='timerday[]']").checkboxradio("refresh");
}

/*
    $(document).delegate('#newtimer', 'pagebeforeshow', function () {
        var timerid = sessionStorage.TimerID;
        //alert(timerid);
        
        if(timerid) {
            // jetzt ajax
            
            $("#newtimer #timertitle").text("Timer ändern ["+timerid+"]");
            $("#newtimer #active").val('on').slider('refresh');
        } else {
            //neuer Timer
            $("#newtimer #timertitle").text("Timer anlegen");
            $("#newtimer #active").val('off').slider('refresh');
            $("#newtimer #timertype_device").attr('checked', true);
            $("#newtimer #timertype_group").attr('checked');
            $("#newtimer #timertype_room").attr('checked');
            //$("#newtimer #timertypecontrolgroup").controlgroup("refresh");
            $("input[type='radio']").checkboxradio("refresh");
        }
    });
*/
</script>
<div data-role="page" id="newtimer" data-theme="<?php echo $theme_page; ?>">

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#timers" data-transition="slide" data-direction="reverse" data-role="button" data-theme="r" onClick="resetNewTimerForm();">Abbrechen</a>
        <h1 id="timertitle">Neuer Timer</h1>
        <a href="#" id="newtimersubmit" data-role="button" data-theme="g">Speichern</a>
    </div><!-- /header -->

    <div data-role="content">
        <form id="newtimerform" method="post">
        <input type="hidden" name="action" id="action" value="add" />
    <ul data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-inset="false">
        <li data-role="fieldcontain">
            <label for="active">Aktiv:</label>
            <select name="active" id="active" data-role="slider">
                <option value="off">Nein</option>
                <option value="on">Ja</option>
            </select>
        </li>
        <li data-role="fieldcontain">
                <fieldset id="timertypecontrolgroup" data-role="controlgroup" data-mini="true" data-type="horizontal">
                   <legend>Typ:</legend>
                        <input type="radio" name="timertype" id="timertype_device" value="device" checked="checked" />
                        <label for="timertype_device">Gerät</label>
            
                        <input type="radio" name="timertype" id="timertype_group" value="group"  />
                        <label for="timertype_group">Gruppe</label>
            
                        <input type="radio" name="timertype" id="timertype_room" value="room"  />
                        <label for="timertype_room">Raum</label>
            
                </fieldset>
        </li>
        <li data-role="fieldcontain">
            <div data-role="fieldcontain" id="typeiddevice_box" class="show">
                <label for="typeiddevice">Gerät:</label>
                <select name="typeiddevice" id="typeiddevice" data-mini="false">
                     <?php
                        $devices = array();
                        foreach($xml->devices->device as $device) {
                            $devices[] = $device;
                        }
                        switch ($xml->gui->sortOrderDevices){
                            case "SORT_BY_NAME":
                                usort($devices, "compareDevicesByName");
                                break;
                            case "SORT_BY_ID":
                                usort($devices, "compareDevicesByID");
                                break;
                            default:
                                break;
                        }
                        foreach($devices as $device) {
                            echo "<option value='".$device->id."'>".$device->name."</option>";
                        }
                     ?>
                </select>
            </div>
                        
            <div data-role="fieldcontain" id="typeidgroup_box" class="hide">
                <label for="typeidgroup">Gruppe:</label>
                <select name="typeidgroup" id="typeidgroup" data-mini="false">
                     <?php
                        $groups = array();
                        foreach($xml->groups->group as $group) {
                            $groups[] = $group;
                        }
                        switch ($xml->gui->sortOrderGroups){
                            case "SORT_BY_NAME":
                                usort($groups, "compareGroupsByName");
                                break;
                            case "SORT_BY_ID":
                                usort($groups, "compareGroupsByID");
                                break;
                            default:
                                break;
                        }
                        foreach($groups as $group) {
                            echo "<option value='".$group->id."'>".$group->name."</option>";
                        }
                     ?>
                </select>
            </div>
                        
            <div data-role="fieldcontain" id="typeidroom_box" class="hide">
                <label for="typeidroom">Raum:</label>
                <select name="typeidroom" id="typeidroom" data-mini="false">
                     <?php
                        $roomDevices = array();
                        foreach($xml->devices->device as $device) {
                            $curRoom = (string)$device->room;
                            if(!array_key_exists($curRoom, $roomDevices)) {
                                $roomDevices[$curRoom] = array();
                            }
                            $roomDevices[$curRoom][] = $device;
                        }
                        switch ($xml->gui->sortOrderRooms){
                            case "SORT_BY_NAME":
                                ksort($roomDevices);
                                break;
                            default:
                                break;
                        }
                        foreach($roomDevices as $room => $devices) {
                            echo "<option value='".$room."'>".$room."</option>";
                        }
                     ?>
                </select>
            </div>
        </li>
        <li data-role="fieldcontain">
                <fieldset data-role="controlgroup" data-mini="true" data-type="horizontal">
                   <legend>Tage:</legend>
                        <input type="checkbox" name="timerday[]" id="timerday-1" value="0" />
                        <label for="timerday-1">M</label>
            
                        <input type="checkbox" name="timerday[]" id="timerday-2" value="1" />
                        <label for="timerday-2">D</label>
            
                        <input type="checkbox" name="timerday[]" id="timerday-3" value="2" />
                        <label for="timerday-3">M</label>
            
                        <input type="checkbox" name="timerday[]" id="timerday-4" value="3" />
                        <label for="timerday-4">D</label>
            
                        <input type="checkbox" name="timerday[]" id="timerday-5" value="4" />
                        <label for="timerday-5">F</label>
            
                        <input type="checkbox" name="timerday[]" id="timerday-6" value="5" />
                        <label for="timerday-6">S</label>
            
                        <input type="checkbox" name="timerday[]" id="timerday-7" value="6" />
                        <label for="timerday-7">S</label>
            
                </fieldset>
        </li>
        <li data-role="fieldcontain">
                <label for="OnTimerType">An:</label>
                <select name="OnTimerType" id="OnTimerType" data-mini="false">
                    <option value="M" selected>Manuell</option>
                    <option value="A">Automatik</option>
                    <option value="SU">Sonnenaufgang (<?php echo date('H:i', $sunrise); ?>)</option>
                    <option value="SD">Sonnenuntergang (<?php echo date('H:i', $sunset); ?>)</option>
                </select>
        </li>
        <li data-role="fieldcontain">
            <div id="timeronmanuell_box" class="show">
                Dieser Timer schaltet nicht ein.
            </div>
            <div data-role="fieldcontain" id="timerontime_box" class="hide">
                <fieldset id="timerontime" data-role="controlgroup" data-type="horizontal">
                    <legend>Uhrzeit:</legend>
               
                    <label for="OnTimerHH">Stunden</label>
                    <select name="OnTimerHH" id="OnTimerHH" data-mini="false">
                        <option>Stunden</option>
                        <?php
                        for ($i = 0; $i <= 23; $i++) {
                         echo "<option value='".sprintf ("%02d", $i)."'>".sprintf ("%02d", $i)."</option>";
                     }
                     ?>
                    </select>
               
                    <label for="OnTimerMM">Minuten</label>
                    <select name="OnTimerMM" id="OnTimerMM" data-mini="false">
                        <option>Minuten</option>
                        <?php
                        for ($i = 0; $i <= 55; $i+=5) {
                         echo "<option value='".sprintf ("%02d", $i)."'>".sprintf ("%02d", $i)."</option>";
                     }
                     ?>
                    </select>
                </fieldset>
            </div>
            <div data-role="fieldcontain" id="timeronoffset_box" class="hide">
                       <label for="timeronoffset">Offset:</label>
                       <input type="range" name="timeronoffset" id="timeronoffset" value="0" min="-240" max="240" step="5" />
            </div>
        </li>
        <li data-role="fieldcontain">
                <label for="OffTimerType">Aus:</label>
                <select name="OffTimerType" id="OffTimerType" data-mini="false">
                    <option value="M" selected>Manuell</option>
                    <option value="A">Automatik</option>
                    <option value="SU">Sonnenaufgang (<?php echo date('H:i', $sunrise); ?>)</option>
                    <option value="SD">Sonnenuntergang (<?php echo date('H:i', $sunset); ?>)</option>
                </select>
        </li>
        <li data-role="fieldcontain">
            <div id="timeroffmanuell_box" class="show">
                Dieser Timer schaltet nicht aus.
            </div>
            <div data-role="fieldcontain" id="timerofftime_box" class="hide">
                 <fieldset id="timerofftime" data-role="controlgroup" data-type="horizontal">
                    <legend>Uhrzeit:</legend>
               
                    <label for="OffTimerHH">Stunden</label>
                    <select name="OffTimerHH" id="OffTimerHH" data-mini="false">
                        <option>Stunden</option>
                        <?php
                        for ($i = 0; $i <= 23; $i++) {
                         echo "<option value='".sprintf ("%02d", $i)."'>".sprintf ("%02d", $i)."</option>";
                     }
                     ?>
                    </select>
               
                    <label for="OffTimerMM">Minuten</label>
                    <select name="OffTimerMM" id="OffTimerMM" data-mini="false">
                        <option>Minuten</option>
                        <?php
                        for ($i = 0; $i <= 55; $i+=5) {
                         echo "<option value='".sprintf ("%02d", $i)."'>".sprintf ("%02d", $i)."</option>";
                     }
                     ?>
                    </select>
                  
                </fieldset>
            </div>
            <div data-role="fieldcontain" id="timeroffoffset_box" class="hide">
                       <label for="timeroffoffset">Offset:</label>
                       <input type="range" name="timeroffoffset" id="timeroffoffset" value="0" min="-240" max="240" step="5" />
            </div>
        </li>
    </ul>
              
        </form>
    </div><!-- /content -->
</div><!-- /page -->











<script type="text/javascript">
    function load_debug() {
        $.ajax({
            type:'GET', 
            url: 'gui_debug.php?taillog=yes', 
            async: true,
            success: function(response) {
                $('#debugtext').val(response);
            },
            error: function(response) {
                $('#debugtext').val(response);
            }
        });
    }
    $(document).ready(function() {
        $(document).delegate('#debug', 'pageshow', function () {
            load_debug();
        });
        $("#reloadbtn").click(function() {
            load_debug();
        });
    });
</script>
<div data-role="page" id="debug" data-theme="<?php echo $theme_page; ?>">

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#configurations" data-transition="slide" data-direction="reverse">Einstellungen</a>
        <h1>Debug</h1>
        <a href="#" id="reloadbtn">Reload</a>
    </div><!-- /header -->

    <div data-role="content" id="content">  
        <ul data-role="listview" data-theme="<?php echo $theme_row; ?>" data-divider-theme="<?php echo $theme_divider; ?>" data-inset="false">

            <li data-role="list-divider" role="heading">
                debug.log
            </li>

            <li>
                <textarea id="debugtext">
                </textarea>
            </li>     

        </ul>
    </div><!-- /content -->
</div><!-- /page -->

</body>
</html>

