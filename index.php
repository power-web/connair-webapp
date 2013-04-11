<?php

$directaccess = true;

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

/*
Hilfreiche Links:

Dev:
http://developer.apple.com/library/safari/#documentation/iPhone/Conceptual/SafariJSDatabaseGuide/OfflineApplicationCache/OfflineApplicationCache.html
http://jquerymobile.com/demos/1.0rc2/docs/pages/page-cache.html
http://code.google.com/p/jqueryrotate/
http://zsprawl.com/iOS/2012/03/css-for-iphone-ipad-and-retina-displays/
http://appcropolis.com/blog/advanced-customization-jquery-mobile-buttons/

Config:
http://www.fhemwiki.de/wiki/Intertechno_Code_Berechnung
http://isn-systems.com/tools/it2elro/
*/

require("config.php");
require("debug.php");

$authentificated=false;
$errormessage="";


//debug("Request start");


//http://php.net/manual/de/function.ip2long.php
function clientInSameSubnet($client_ip=false,$server_ip=false) {
    if (!$client_ip) {
        $client_ip = $_SERVER['REMOTE_ADDR'];
    }
    if (!$server_ip) {
        $server_ip = $_SERVER['SERVER_ADDR'];
    }
    // Extract broadcast and netmask from ifconfig
    if (!($p = popen("ifconfig","r"))) return false;
    $out = "";
    while(!feof($p)) {
        $out .= fread($p,1024);
    }
    fclose($p);
    // This is because the php.net comment function does not
    // allow long lines.
    $match  = "/^.*".$server_ip;
    $match .= ".*Bcast:(\d{1,3}\.\d{1,3}i\.\d{1,3}\.\d{1,3}).*";
    $match .= "Mask:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/im";
    if (!preg_match($match,$out,$regs)) {
        return false;
    }
    $bcast = ip2long($regs[1]);
    $smask = ip2long($regs[2]);
    $ipadr = ip2long($client_ip);
    $nmask = $bcast & $smask;
    return (($ipadr & $smask) == ($nmask & $smask));
}


//mini Login wenn remote IP nicht im Subnetz des Servers
/*
if( clientInSameSubnet() ) {
    $authentificated = true;
} else {
    echo "LOGIN";
    exit;
}
*/


function compareDevicesByName($a, $b) {
   return strcmp($a->name,$b->name);
}
function compareDevicesByID($a, $b) {
   return strcmp($a->id,$b->id);
}
function compareDevicesByRoom($a, $b) {
   return strcmp($a->room,$b->room);
}
function compareGroupsByName($a, $b) {
   return strcmp($a->name,$b->name);
}
function compareGroupsByID($a, $b) {
   return strcmp($a->id,$b->id);
}
function compareTimersByTypeAndName($a, $b) {
    global $xml;
    switch($a->type) {
        case "device":
            $devicesFound = $xml->xpath("//devices/device/id[text()='".$a->typeid."']/parent::*");
            $deviceA = $devicesFound[0];
            $nameA = $deviceA->name;
            break;
        case "group":
            $groupsFound = $xml->xpath("//groups/group/id[text()='".$a->typeid."']/parent::*");
            $groupA = $groupsFound[0];
            $nameA = $groupA->name;
            break;
        case "room":
            $nameA = $a->typeid;
            break;
        default:
            $nameA = $a->id;
            break;
    }
    switch($b->type) {
        case "device":
            $devicesFound = $xml->xpath("//devices/device/id[text()='".$b->typeid."']/parent::*");
            $deviceB = $devicesFound[0];
            $nameB = $deviceB->name;
            break;
        case "group":
            $groupsFound = $xml->xpath("//groups/group/id[text()='".$b->typeid."']/parent::*");
            $groupB = $groupsFound[0];
            $nameB = $groupB->name;
            break;
        case "room":
            $nameB = $b->typeid;
            break;
        default:
            $nameB = $b->id;
            break;
    }
    return strcmp($nameA,$nameB);
}
function compareTimersByID($a, $b) {
   return strcmp($a->id,$b->id);
}
function compareTimersByType($a, $b) {
    $cmp = strcmp($a->type,$b->type);
    if($cmp == 0) {
        $cmp = compareTimersByName($a, $b);
    }
    return $cmp;
}
function compareTimersByName($a, $b) {
   return strcmp($a->name,$b->name);
}





// Über Tastenfunktion -> POST
if (isset($_POST['action'])) {
    $r_action = (string)$_POST['action'];
    $r_type = (string)$_POST['type'];
    $r_id = (string)$_POST['id'];
}
// Über Linkfunktion -> GET
if (isset($_GET['action'])) {
    $r_action = (string)$_GET['action'];
    $r_type = (string)$_GET['type'];
    $r_id = (string)$_GET['id'];
}
// Über Timerfunktion -> GET
if (isset($_GET['timerrun'])) {
    require("send_msg.php");
    require("timer.php");
    timer_check();
    exit();
}



if (isset($r_action)) {
    debug("Running in action='".$r_action."'");  

    require("send_msg.php");

    if (($r_action)=="alloff") {
        foreach($xml->devices->device as $device) {
            send_message($device, "OFF");
            usleep($multiDeviceSleep);
        }
        echo $errormessage;

    } else if (($r_action)=="allon") {
        foreach($xml->devices->device as $device) {
            send_message($device, "ON");
            usleep($multiDeviceSleep);
        }
        echo $errormessage;

    } else {
        if (($r_action)=="on") { 
            $action="ON"; 
        } else { 
            $action="OFF";
        }
        
        if (($r_type)=="device") {
            $devicesFound = $xml->xpath("//devices/device/id[text()='".$r_id."']/parent::*");
            $device = $devicesFound[0];
            send_message($device, $action);

        } else if (($r_type)=="room") {
            $devicesFound = $xml->xpath("//devices/device/room[text()='".$r_id."']/parent::*");
            foreach($devicesFound as $device) {
                send_message($device, $action);
                usleep($multiDeviceSleep);
            }

        } else if (($r_type)=="group") { 
            $groupsFound = $xml->xpath("//groups/group/id[text()='".$r_id."']/parent::*");
            foreach($groupsFound[0]->deviceid as $deviceid) {
                $devicesFound = $xml->xpath("//devices/device/id[text()='".$deviceid."']/parent::*");
                $device = $devicesFound[0];
                if($action == "ON") {
                    if(empty($deviceid['onaction'])) {
                        send_message($device, $action);
                    } else {
                        switch ($deviceid['onaction']) {
                            case "on":
                                send_message($device, "ON");
                                break;
                            case "off":
                                send_message($device, "OFF");
                                break;
                            case "none":
                                break;
                        }
                    }
                } else if($action == "OFF") {
                    if(empty($deviceid['offaction'])) {
                        send_message($device, $action);
                    } else {
                        switch ($deviceid['offaction']) {
                            case "on":
                                send_message($device, "ON");
                                break;
                            case "off":
                                send_message($device, "OFF");
                                break;
                            case "none":
                                break;
                        }
                    }
                }
                usleep($multiDeviceSleep);
            }
        }
        echo $errormessage;
    }
    config_save(); 
} else {
    debug("Sending HTML Site");  
    require("gui.php");
} 
    //debug("END");  
?> 
