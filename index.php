<?php

//http://php.net/manual/de/function.ip2long.php
function clientInSameSubnet($client_ip=false,$server_ip=false) {
    if (!$client_ip)
        $client_ip = $_SERVER['REMOTE_ADDR'];
    if (!$server_ip)
        $server_ip = $_SERVER['SERVER_ADDR'];
    // Extract broadcast and netmask from ifconfig
    if (!($p = popen("ifconfig","r"))) return false;
    $out = "";
    while(!feof($p))
        $out .= fread($p,1024);
    fclose($p);
    // This is because the php.net comment function does not
    // allow long lines.
    $match  = "/^.*".$server_ip;
    $match .= ".*Bcast:(\d{1,3}\.\d{1,3}i\.\d{1,3}\.\d{1,3}).*";
    $match .= "Mask:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/im";
    if (!preg_match($match,$out,$regs))
        return false;
    $bcast = ip2long($regs[1]);
    $smask = ip2long($regs[2]);
    $ipadr = ip2long($client_ip);
    $nmask = $bcast & $smask;
    return (($ipadr & $smask) == ($nmask & $smask));
}



libxml_use_internal_errors(true);
$xml = simplexml_load_file('config.xml');
if (!$xml) {
    echo "Laden des XML fehlgeschlagen\n";
    foreach(libxml_get_errors() as $error) {
        echo "\t", $error->message;
    }
    exit(1);
}


//mini Login wenn nicht im Subnetz
//if( ! clientInSameSubnet() ) {
//    echo "LOGIN";
//    exit;
//}


$errormessage="";
   
function connair_send($msg) {
    global $xml;
    global $errormessage;
    $len = strlen($msg);
    if(!($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))) {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);
        
        $errormessage="Couldn't create socket: [$errorcode] $errormsg \n";

        return;
    }
    foreach($xml->connairs->connair as $connair) {
        if( ! socket_sendto ( $sock , $msg, $len , 0, (string)$connair->address , (integer)$connair->port)) {
	        $errorcode = socket_last_error();
	        if($errorcode>0) {
                $errormsg = socket_strerror($errorcode);
                $errormessage="Could not send data: [$errorcode] $errormsg \n";
            } else {
                $errormessage="Befehl an Connair gesendet \n";
            }
        } else {
            $errormessage="Befehl an Connair gesendet \n";
        }
    }
    if($sock) {
        socket_close($sock);
    }
}


function tx433_brennstuhl($device, $action) {   
    if(empty($device->address->masterdip)) {
        echo "ERROR: masterdip ist ungültig für device id ".$device->id;
        return;
    }
    if(empty($device->address->slavedip)) {
        echo "ERROR: slavedip ist ungültig für device id ".$device->id;
        return;
    }
    if(empty($device->address->tx433version)) {
        echo "ERROR: tx433version ist ungültig für device id ".$device->id;
        return;
    }
    $sA=0;
    $sG=0;
    $sRepeat=10;
    $sPause=5600;
    $sTune=350;
    $sBaud=25;
    $sSpeed=16;

    $uSleep=800000;
    if ($device->address->tx433version==1) $txversion=3;
    else $txversion=1;

    $HEAD="TXP:$sA,$sG,$sRepeat,$sPause,$sTune,$sBaud,";
    $TAIL=",$txversion,1,$sSpeed,;";
    $AN="1,3,1,3,3";
    $AUS="3,1,1,3,1";

    $bitLow=1;
    $bitHgh=3;

    $seqLow=$bitHgh.",".$bitHgh.",".$bitLow.",".$bitLow.",";
    $seqHgh=$bitHgh.",".$bitLow.",".$bitHgh.",".$bitLow.",";

    $bits=$device->address->masterdip;
    $msg="";
    for($i=0;$i<strlen($bits);$i++) {   
      $bit=substr($bits,$i,1);
      if($bit=="0")
         $msg=$msg.$seqLow;
      else
         $msg=$msg.$seqHgh;
    }
    $msgM=$msg;
    $bits=$device->address->slavedip;
    $msg="";
    for($i=0;$i<strlen($bits);$i++) {
      $bit=substr($bits,$i,1);
      if($bit=="0")
         $msg=$msg.$seqLow;
      else
         $msg=$msg.$seqHgh;
    }

    $msgS=$msg;
    $msg_ON=$HEAD.$bitLow.",".$msgM.$msgS.$bitHgh.",".$AN.$TAIL;
    $msg_OFF=$HEAD.$bitLow.",".$msgM.$msgS.$bitHgh.",".$AUS.$TAIL;

    if($action=="ON") {
        $msg=$msg_ON;
    } else {
        $msg=$msg_OFF;
    }
    
    connair_send($msg);
}   

function tx433_intertechno($device, $action) {
    if(empty($device->address->masterdip)) {
        echo "ERROR: masterdip ist ungültig für device id ".$device->id;
        return;
    }
    if(empty($device->address->slavedip)) {
        echo "ERROR: slavedip ist ungültig für device id ".$device->id;
        return;
   }
    $sA=0;
    $sG=0;
    $sRepeat=6;
    $sPause=11125;
    $sTune=89;
    $sBaud=25;
    $sSpeed=125;

    $uSleep=800000;

    $HEAD="TXP:$sA,$sG,$sRepeat,$sPause,$sTune,$sBaud,";
    $TAIL=",1,$sSpeed,;";
    $AN="12,4,4,12,12,4";
    $AUS="12,4,4,12,4,12";

    $bitLow=4;
    $bitHgh=12;

    $seqLow=$bitHgh.",".$bitHgh.",".$bitLow.",".$bitLow.",";
    $seqHgh=$bitHgh.",".$bitLow.",".$bitHgh.",".$bitLow.",";

    $msgM="";

    switch ($device->address->masterdip){
      case "A":
         $msgM=$seqHgh.$seqHgh.$seqHgh.$seqHgh;
      break;
      case "B":
         $msgM=$seqLow.$seqHgh.$seqHgh.$seqHgh;
      break;   
      case "C":
         $msgM=$seqHgh.$seqLow.$seqHgh.$seqHgh;
      break; 
      case "D":
         $msgM=$seqLow.$seqLow.$seqHgh.$seqHgh;
      break;
      case "E":
         $msgM=$seqHgh.$seqHgh.$seqLow.$seqHgh;
      break;
      case "F":
         $msgM=$seqLow.$seqHgh.$seqLow.$seqHgh;
      break;
      case "G":
         $msgM=$seqHgh.$seqLow.$seqLow.$seqHgh;
      break;
      case "H":
         $msgM=$seqLow.$seqLow.$seqLow.$seqHgh;
      break;
      case "I":
         $msgM=$seqHgh.$seqHgh.$seqHgh.$seqLow;
      break;
      case "J":
         $msgM=$seqLow.$seqHgh.$seqHgh.$seqLow;
      break;
      case "K":
         $msgM=$seqHgh.$seqLow.$seqHgh.$seqLow;
      break;
      case "L":
         $msgM=$seqLow.$seqLow.$seqHgh.$seqLow;
      break;
      case "M":
         $msgM=$seqHgh.$seqHgh.$seqLow.$seqLow;
      break;
      case "N":
         $msgM=$seqLow.$seqHgh.$seqLow.$seqLow;
      break;
      case "O":
         $msgM=$seqHgh.$seqLow.$seqLow.$seqLow;
      break;
      case "P":
         $msgM=$seqLow.$seqLow.$seqLow.$seqLow;
      break;
    }

    $msgS="";   
    switch ($device->address->slavedip){
      case "1":
         $msgS=$seqHgh.$seqHgh.$seqHgh.$seqHgh;
      break;
      case "2":
         $msgS=$seqLow.$seqHgh.$seqHgh.$seqHgh;
      break;   
      case "3":
         $msgS=$seqHgh.$seqLow.$seqHgh.$seqHgh;
      break; 
      case "4":
         $msgS=$seqLow.$seqLow.$seqHgh.$seqHgh;
      break;
      case "5":
         $msgS=$seqHgh.$seqHgh.$seqLow.$seqHgh;
      break;
      case "6":
         $msgS=$seqLow.$seqHgh.$seqLow.$seqHgh;
      break;
      case "7":
         $msgS=$seqHgh.$seqLow.$seqLow.$seqHgh;
      break;
      case "8":
         $msgS=$seqLow.$seqLow.$seqLow.$seqHgh;
      break;
      case "9":
         $msgS=$seqHgh.$seqHgh.$seqHgh.$seqLow;
      break;
      case "10":
         $msgS=$seqLow.$seqHgh.$seqHgh.$seqLow;
      break;
      case "11":
         $msgS=$seqHgh.$seqLow.$seqHgh.$seqLow;
      break;
      case "12":
         $msgS=$seqLow.$seqLow.$seqHgh.$seqLow;
      break;
      case "13":
         $msgS=$seqHgh.$seqHgh.$seqLow.$seqLow;
      break;
      case "14":
         $msgS=$seqLow.$seqHgh.$seqLow.$seqLow;
      break;
      case "15":
         $msgS=$seqHgh.$seqLow.$seqLow.$seqLow;
      break;
      case "16":
         $msgS=$seqLow.$seqLow.$seqLow.$seqLow;
      break;
    }

    $msg_ON=$HEAD.$bitLow.",".$msgM.$msgS.$seqHgh.$seqLow.$bitHgh.",".$AN.$TAIL;
    $msg_OFF=$HEAD.$bitLow.",".$msgM.$msgS.$seqHgh.$seqLow.$bitHgh.",".$AUS.$TAIL;

    if($action=="ON") {
        $msg=$msg_ON;
    } else {
        $msg=$msg_OFF;
    }

    connair_send($msg);
}


function tx433_elro($device, $action) {
    if(empty($device->address->masterdip)) {
        echo "ERROR: masterdip ist ungültig für device id ".$device->id;
        return;
    }
    if(empty($device->address->slavedip)) {
        echo "ERROR: slavedip ist ungültig für device id ".$device->id;
        return;
   }

   $sA=0;
   $sG=0;
   $sRepeat=10;
   $sPause=5600;
   $sTune=350;
   $sBaud=25;
   $sSpeed=16;
   
   $uSleep=800000;
   
/*
system code 10111
Dann in Reihenfolge unit code
A 10000
B 01000
E 00001   
*/

//Elro AB440D 200W         TXP:0,0,10,5600,350,25   ,16:
//Elro AB440D 300W         TXP:0,0,10,5600,350,25   ,16:
//Elro AB440ID            TXP:0,0,10,5600,350,25   ,16:
//Elro AB440IS            TXP:0,0,10,5600,350,25   ,16:
//Elro AB440L            TXP:0,0,10,5600,350,25   ,16:
//Elro AB440WD            TXP:0,0,10,5600,350,25   ,16:
   
   
 
    if ($device->address->tx433version==1) $txversion=3;
    else $txversion=1;
   
   $HEAD="TXP:$sA,$sG,$sRepeat,$sPause,$sTune,$sBaud,";
   $TAIL=",$txversion,1,$sSpeed,;";
   $AN="1,3,1,3,3";
   $AUS="3,1,1,3,1";
   
   $bitLow=1;
   $bitHgh=3;
   
   $seqLow=$bitHgh.",".$bitHgh.",".$bitLow.",".$bitLow.",";
   $seqHgh=$bitHgh.",".$bitLow.",".$bitHgh.",".$bitLow.",";

   $bits=$device->address->masterdip;
   $msg="";
   for($i=0;$i<strlen($bits);$i++) {   
      $bit=substr($bits,$i,1);
      if($bit=="0")
         $msg=$msg.$seqLow;
      else
         $msg=$msg.$seqHgh;
   }
   $msgM=$msg;
   $bits=$device->address->slavedip;
   $msg="";
   for($i=0;$i<strlen($bits);$i++) {
      $bit=substr($bits,$i,1);
      if($bit=="0")
         $msg=$msg.$seqLow;
      else
         $msg=$msg.$seqHgh;
   }

   $msgS=$msg;
   $msg_ON=$HEAD.$bitLow.",".$msgM.$msgS.$bitHgh.",".$AN.$TAIL;
   $msg_OFF=$HEAD.$bitLow.",".$msgM.$msgS.$bitHgh.",".$AUS.$TAIL;
   
    if($action=="ON") {
        $msg=$msg_ON;
    } else {
        $msg=$msg_OFF;
    }

   connair_send($msg);
}


function tx433_message($device, $action) {
    if ($action=="ON" && !empty($device->address->rawCodeOn) ) {
        connair_send($device->address->rawCodeOn);
    } else if ($action=="OFF" && !empty($device->address->rawCodeOff)) {
        connair_send($device->address->rawCodeOff);
    } else if ($device->vendor=="Brennstuhl") {
        tx433_brennstuhl($device, $action);
    } else if ($device->vendor=="Intertechno") {
        tx433_intertechno($device, $action);
    }
}


if (isset($_POST['action'])) {

    if (($_POST['action'])=="alloff") {
        foreach($xml->devices->device as $device) {
            tx433_message($device, "OFF");
            usleep(300000);
        }
        echo $errormessage;

    } else if (($_POST['action'])=="allon") {
        foreach($xml->devices->device as $device) {
            tx433_message($device, "ON");
            usleep(300000);
        }
        echo $errormessage;

    } else {
        if (($_POST['action'])=="on") { 
            $action="ON"; 
        } else { 
            $action="OFF";
        }
        
        if (($_POST['type'])=="device") {
            $devicesFound = $xml->xpath("//devices/device/id[text()='".(string)$_POST['id']."']/parent::*");
            tx433_message($devicesFound[0], $action);

        } else if (($_POST['type'])=="room") {
            $devicesFound = $xml->xpath("//devices/device/room[text()='".(string)$_POST['id']."']/parent::*");
            foreach($devicesFound as $device) {
                tx433_message($device, $action);
                usleep(300000);
            }

        } else if (($_POST['type'])=="group") { 
            $groupsFound = $xml->xpath("//groups/group/id[text()='".(string)$_POST['id']."']/parent::*");
            foreach($groupsFound[0]->deviceid as $deviceid) {
                $devicesFound = $xml->xpath("//devices/device/id[text()='".$deviceid."']/parent::*");
                $device = $devicesFound[0];
                if($action == "ON") {
                    if(empty($deviceid['onaction'])) {
                        tx433_message($device, $action);
                    } else {
                        switch ($deviceid['onaction']){
                            case "on":
                            tx433_message($device, "ON");
                            break;
                            case "off":
                            tx433_message($device, "OFF");
                            break;
                            case "none":
                            break;
                        }
                    }
                } else if($action == "OFF") {
                    if(empty($deviceid['offaction'])) {
                        tx433_message($device, $action);
                    } else {
                        switch ($deviceid['offaction']){
                            case "on":
                            tx433_message($device, "ON");
                            break;
                            case "off":
                            tx433_message($device, "OFF");
                            break;
                            case "none":
                            break;
                        }
                    }
                }

                usleep(300000);
            }
        }
        
        echo $errormessage;
    }
} else {
    header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Mobile Connair</title>

<link rel="stylesheet" href="jquery.mobile-1.3.0-rc.1.min.css" />
<link rel="stylesheet" href="jquery-mobile-red-button-theme.css" />
<link rel="stylesheet" href="jquery-mobile-green-button-theme.css" />
<style type="text/css">
/*
.ui-grid-a .ui-block-a { width: 66.95%; }
.ui-grid-a .ui-block-b { width: 32.925%; }
.ui-grid-a .ui-block-a { clear: left; }
*/


/* wrap push on wide viewports once open
@media (min-width: 35em){
	.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-open.ui-panel-content-fixed-toolbar-display-push,
	.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-open.ui-panel-content-fixed-toolbar-display-reveal,
	.ui-responsive-panel.ui-page-panel-open .ui-panel-content-wrap-open.ui-panel-content-wrap-display-push,
	.ui-responsive-panel.ui-page-panel-open .ui-panel-content-wrap-open.ui-panel-content-wrap-display-reveal {
		margin-right: 17em;
	}
	.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-open.ui-panel-content-wrap-display-push.ui-panel-content-fixed-toolbar-position-right,
	.ui-responsive-panel.ui-page-panel-open .ui-panel-content-fixed-toolbar-open.ui-panel-content-wrap-display-reveal.ui-panel-content-fixed-toolbar-position-right,
	.ui-responsive-panel.ui-page-panel-open .ui-panel-content-wrap-open.ui-panel-content-wrap-display-push.ui-panel-content-wrap-position-right,
	.ui-responsive-panel.ui-page-panel-open .ui-panel-content-wrap-open.ui-panel-content-wrap-display-reveal.ui-panel-content-wrap-position-right {
		margin: 0 0 0 17em;
	}
	.ui-responsive-panel .ui-panel-dismiss-display-push {
		display: none;
	}
}
*/

</style>

    
<script type="text/javascript" charset="utf-8" src="jquery-1.9.0.min.js"></script>
<script type="text/javascript">
    $(document).bind("mobileinit", function(){
        $.mobile.defaultPageTransition = 'none';
    });
    $(document).ready(function() {
        $.event.special.swipe.scrollSupressionThreshold=10;
        $.event.special.swipe.durationThreshold=1000;
        $.event.special.swipe.horizontalDistanceThreshold=30;
        $.event.special.swipe.verticalDistanceThreshold=75;
        $(document).on( 'swiperight', swiperightHandler );
        function swiperightHandler( event ){
            $( "#mypanel" ).panel( "open" );
        }
        $(document).on( 'swipeleft', swipeleftHandler );
        function swipeleftHandler( event ){
            $( "#mypanel" ).panel( "close" );
        }
        /*
        $(document).delegate('.ui-page', 'pageshow', function () {
            setTimeout(function() {
                $( "#mypanel" ).panel( "open" );
                setTimeout(function() {
                    $( "#mypanel" ).panel( "close" );
                }, 1000);
            }, 1000);
        });
        */
    });
</script>
<script type="text/javascript" charset="utf-8" src="jquery.mobile-1.3.0-rc.1.min.js"></script>
<script type="text/javascript" charset="utf-8" src="jquery.toast.mobile.js"></script>


<!-- WebApp -->
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scaleable=no">
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
            $.ajax({
                type:'POST', 
                url: '<?php echo $_SERVER['PHP_SELF']; ?>', 
                data: data,
                async: true,
                success: function(response) {
                    toast(response);
                },
                error: function(response) {
                    toast(response);
                }
            });
        }
    </script>
</head>
<body>










<div data-role="page" id="Favoriten">

    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
        <center>
            <a href="#Favoriten" data-role="button" data-theme="e" class="ui-disabled">Favoriten</a>
            <a href="#Geräte" data-role="button" data-theme="e">Geräte</a>
            <a href="#Gruppen" data-role="button" data-theme="e">Gruppen</a>
            <a href="#Räume" data-role="button" data-theme="e">Räume</a>
            <a href="#Einstellungen" data-role="button" data-theme="e" class="ui-disabled">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#my-header" data-role="button" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Favoriten</h1>
    </div><!-- /header -->


    <div data-role="content">  
        <ul data-role="listview" data-divider-theme="e" data-inset="false">

            <li data-role="list-divider" role="heading" data-theme="a">
                Gruppen
            </li>
 
<?php
        $groupsFound = $xml->xpath("//groups/group/favorite[text()='true']/parent::*");
        foreach($groupsFound as $group) {
?>

            <li data-theme="c">
                <div class="ui-grid-a">
	                <div class="ui-block-a" style="text-align:left"><?php echo $group->name; ?></div>
	                <div class="ui-block-b" style="text-align:right">
                        <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on','group','<?php echo $group->id; ?>')">Ein</button>
                        <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off','group','<?php echo $group->id; ?>')">Aus</button>
	                </div>
                </div>
                
<?php
            
            foreach($group->deviceid as $deviceid) {
                $devicesFound = $xml->xpath("//devices/device/id[text()='".$deviceid."']/parent::*");
            echo "<p>".$devicesFound[0]->name."</p>";
        }
?>

            </li>
     
<?php
    }
?>

            <li data-role="list-divider" role="heading" data-theme="a">
                Geräte
            </li>

<?php
        $devicesFound = $xml->xpath("//devices/device/favorite[text()='true']/parent::*");
        foreach($devicesFound as $device) {
?>

                <li data-theme="c">
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left"><?php echo $device->name; ?></div>
	                    <div class="ui-block-b" style="text-align:right">
	                        <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on','device','<?php echo $device->id; ?>')">Ein</button>
	                        <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off','device','<?php echo $device->id; ?>')">Aus</button>
	                    </div>
                    </div>
                </li>

<?php
        }
?>


        </ul>
    </div><!-- /content -->
</div><!-- /page -->









<div data-role="page" id="Geräte">

    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
	    <center>
            <a href="#Favoriten" data-role="button" data-theme="e">Favoriten</a>
            <a href="#Geräte" data-role="button" data-theme="e" class="ui-disabled">Geräte</a>
            <a href="#Gruppen" data-role="button" data-theme="e">Gruppen</a>
            <a href="#Räume" data-role="button" data-theme="e">Räume</a>
            <a href="#Einstellungen" data-role="button" data-theme="e" class="ui-disabled">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#my-header" data-role="button" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->
 
       
    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Geräte</h1>
        <a href="#newdevice" data-rel="dialog" data-transition="slidedown" class="ui-disabled">+</a>
    </div><!-- /header -->


    <div data-role="content">  
        <ul data-role="listview" data-divider-theme="e" data-inset="false" data-filter="true" data-filter-placeholder="Geräte suchen...">

<?php
    $roomDevices = array();
    foreach($xml->devices->device as $device) {
        $curRoom = (string)$device->room;
        if(!array_key_exists($curRoom, $roomDevices)) {
            $roomDevices[$curRoom] = array();
        }
        $roomDevices[$curRoom][] = $device;
    }
    ksort($roomDevices);
    foreach($roomDevices as $room => $devices) {
        sort($devices);
?>

            <li data-role="list-divider" role="heading" data-theme="a">
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left"><?php echo $room; ?></div>
	                    <div class="ui-block-b" style="text-align:right">
	                        <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on','room','<?php echo $room; ?>')">Ein</button>
	                        <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off','room','<?php echo $room; ?>')">Aus</button>
	                    </div>
                    </div>
            </li>

<?php
        foreach($devices as $device) {
?>

                <li data-theme="c">
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left"><?php echo $device->name; ?></div>
	                    <div class="ui-block-b" style="text-align:right">
	                        <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on','device','<?php echo $device->id; ?>')">Ein</button>
	                        <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off','device','<?php echo $device->id; ?>')">Aus</button>
	                    </div>
                    </div>
                </li>

<?php
        }
    }
?>
   
            <li data-role="list-divider" role="heading" data-theme="a">
                Alle
            </li>
            <li data-theme="c">
                <div class="ui-grid-a">
                    <div class="ui-block-a"><button data-theme="g" data-rel="close" onclick="send_connair('allon')">An</button></div>
                    <div class="ui-block-b"><button data-theme="r" data-rel="close" onclick="send_connair('alloff')">Aus</button></div>     
                </div>
            </li>
         </ul>
    </div><!-- /content -->
</div><!-- /page -->








<div data-role="page" id="Gruppen">
    
    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
        <center>
            <a href="#Favoriten" data-role="button" data-theme="e">Favoriten</a>
            <a href="#Geräte" data-role="button" data-theme="e">Geräte</a>
            <a href="#Gruppen" data-role="button" data-theme="e" class="ui-disabled">Gruppen</a>
            <a href="#Räume" data-role="button" data-theme="e">Räume</a>
            <a href="#Einstellungen" data-role="button" data-theme="e" class="ui-disabled">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#my-header" data-role="button" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Gruppen</h1>
    </div><!-- /header -->

    <div data-role="content">  

        <ul data-role="listview" data-divider-theme="e" data-inset="false">
 
<?php
    foreach($xml->groups->group as $group) {
?>

            <li data-theme="c">
                <div class="ui-grid-a">
	                <div class="ui-block-a" style="text-align:left"><?php echo $group->name; ?></div>
	                <div class="ui-block-b" style="text-align:right">
                        <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on','group','<?php echo $group->id; ?>')">Ein</button>
                        <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off','group','<?php echo $group->id; ?>')">Aus</button>
	                </div>
                </div>
                
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

            </li>
     
<?php
    }
?>

        </ul>
    </div><!-- /content -->
</div><!-- /page -->









<div data-role="page" id="Räume">

    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
	    <center>
            <a href="#Favoriten" data-role="button" data-theme="e">Favoriten</a>
            <a href="#Geräte" data-role="button" data-theme="e">Geräte</a>
            <a href="#Gruppen" data-role="button" data-theme="e">Gruppen</a>
            <a href="#Räume" data-role="button" data-theme="e" class="ui-disabled">Räume</a>
            <a href="#Einstellungen" data-role="button" data-theme="e" class="ui-disabled">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#my-header" data-role="button" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->
 
       
    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Räume</h1>
    </div><!-- /header -->


    <div data-role="content">  
        <ul data-role="listview" data-divider-theme="e" data-inset="false">

<?php
    $roomDevices = array();
    foreach($xml->devices->device as $device) {
        $curRoom = (string)$device->room;
        if(!array_key_exists($curRoom, $roomDevices)) {
            $roomDevices[$curRoom] = array();
        }
        $roomDevices[$curRoom][] = $device;
    }
    ksort($roomDevices);
    foreach($roomDevices as $room => $devices) {
?>

                <li data-theme="c">
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left"><?php echo $room; ?></div>
	                    <div class="ui-block-b" style="text-align:right">
	                        <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on','room','<?php echo $room; ?>')">Ein</button>
	                        <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off','room','<?php echo $room; ?>')">Aus</button>
	                    </div>
                    </div>
                </li>

<?php
    }
?>
   
         </ul>
    </div><!-- /content -->
</div><!-- /page -->










<div data-role="page" id="Einstellungen">

    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
        <center>
            <a href="#Favoriten" data-role="button" data-theme="e">Favoriten</a>
            <a href="#Geräte" data-role="button" data-theme="e">Geräte</a>
            <a href="#Gruppen" data-role="button" data-theme="e">Gruppen</a>
            <a href="#Räume" data-role="button" data-theme="e">Räume</a>
            <a href="#Einstellungen" data-role="button" data-theme="e" class="ui-disabled">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#my-header" data-role="button" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Einstellungen</h1>
    </div><!-- /header -->

   
    <div data-role="content">  
        <button data-theme="r"  onclick="send_connair('alloff')">Alles aus</button>
        <div data-role="controlgroup" data-type="horizontal">
            <a href="index.html" data-role="button" data-theme="g">I</a>
            <a href="index.html" data-role="button" data-theme="r">0</a>
        </div>
        <div data-role="controlgroup" data-mini="true" data-type="horizontal">
            <a href="index.html" data-role="button" data-theme="g">I</a>
            <a href="index.html" data-role="button" data-theme="a">T</a>
            <a href="index.html" data-role="button" data-theme="r">0</a>
        </div>
        <button data-theme="g"   data-inline="true" onclick="send_connair('on',1)">Ein</button>
        <button data-theme="r"   data-inline="true" onclick="send_connair('off',1)">Aus</button>
        <br>
        <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on',1)">Ein</button>
        <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off',1)">Aus</button>
        <br>
        <button data-theme="g"   data-inline="true" onclick="send_connair('on',1)">I</button>
        <button data-theme="r"   data-inline="true" onclick="send_connair('off',1)">O</button>
        <br>
        <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on',1)">I</button>
        <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off',1)">O</button>

    </div><!-- /content -->
</div><!-- /page -->










<div data-role="page" id="newdevice" data-theme="e">

    <div data-role="header">
        <h1>Neues Gerät</h1>
    </div><!-- /header -->

    <div data-role="content">
        <form id="newdeviceform" method="post">
            <div data-role="fieldcontain">
	            <label for="name">Name:</label>
	            <input type="text" name="name" id="name" value="" />
	            <br/>
	            <label for="name">Raum:</label>
	            <input type="text" name="room" id="room" value="" />
	            <br/>
	            <label for="name">Hersteller:</label>
	            <input type="text" name="vendor" id="vendor" value="" />
	            <br/>
	            <label for="name">Masterdip:</label>
	            <input type="text" name="masterdip" id="masterdip" value="" />
	            <br/>
	            <label for="name">Slavedip:</label>
	            <input type="text" name="slavedip" id="slavedip" value="" />
	            <br/>
	            <label for="name">Version:</label>
	            <input type="text" name="tx433version" id="tx433version" value="" />
	            <br/>
	            <div data-role="fieldcontain">
                    <label for="favorite">Favorit:</label>
                    <select name="favorite" id="favorite" data-role="slider">
	                    <option value="false">Nein</option>
	                    <option value="true">Ja</option>
                    </select> 
                </div>
	        </div>
	        <input type="submit" value="Speichern" data-theme="g"/>
            <a href="#" data-role="button" data-rel="back" data-theme="r">Abbrechen</a>
        </form>
    </div><!-- /content -->
</div><!-- /page -->


</body>
</html>

<?php
} 
?> 
