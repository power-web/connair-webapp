<?php

$directaccess = true;

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

require("config.php");

/*
<config debug="true">
  <global>
    <timezone>Europe/Berlin</timezone>
    <longitude>10.237894</longitude>
    <latitude>52.347423</latitude>
    <timerRunOnce>false</timerRunOnce>
  </global>
  <gui>
    <showDeviceStatus>OFF</showDeviceStatus>
    <showRoomButtonInDevices>OFF</showRoomButtonInDevices>
    <showMenuOnLoad>false</showMenuOnLoad>
    <sortOrderDevices>SORT_BY_NAME</sortOrderDevices>
    <sortOrderGroups>SORT_BY_NAME</sortOrderGroups>
    <sortOrderRooms>SORT_BY_NAME</sortOrderRooms>
    <sortOrderTimers>SORT_BY_TYPE_AND_NAME</sortOrderTimers>
  </gui>
*/

$r_action = (string)$_POST['action'];
$r_debug = (string)$_POST['debug'];
$r_debug_timer = (string)$_POST['debug_timer'];
$r_timezone = (string)$_POST['timezone'];
$r_longitude = (string)$_POST['longitude'];
$r_latitude = (string)$_POST['latitude'];
$r_timerRunOnce = (string)$_POST['timerRunOnce'];
$r_connairPort = (string)$_POST['connairPort'];
$r_connairIP = (string)$_POST['connairIP'];
$r_multiDeviceSleep = intval($_POST['multiDeviceSleep']);
$r_showDeviceStatus = (string)$_POST['showDeviceStatus'];
$r_showRoomButtonInDevices = (string)$_POST['showRoomButtonInDevices'];
$r_showMenuOnLoad = (string)$_POST['showMenuOnLoad'];
$r_showAllOnOffBtnInMenu = (string)$_POST['showAllOnOffBtnInMenu'];
$r_sortOrderDevices = (string)$_POST['sortOrderDevices'];
$r_sortOrderGroups = (string)$_POST['sortOrderGroups'];
$r_sortOrderRooms = (string)$_POST['sortOrderRooms'];
$r_sortOrderTimers = (string)$_POST['sortOrderTimers'];
$r_theme = (string)$_POST['theme'];

switch ($r_action) {
    
    case "edit":
        $xml["debug"] = $r_debug;
        $xml->timers["debug"] = $r_debug_timer;
        $xml->global->timezone = $r_timezone;
        $xml->global->longitude = $r_longitude;
        $xml->global->latitude = $r_latitude;
        $xml->global->timerRunOnce = $r_timerRunOnce;
		$xml->connairs->connair->port = $r_connairPort;
		$xml->connairs->connair->address = $r_connairIP;
        $xml->global->multiDeviceSleep = $r_multiDeviceSleep;
        $xml->gui->showDeviceStatus = $r_showDeviceStatus;
        $xml->gui->showRoomButtonInDevices = $r_showRoomButtonInDevices;
        $xml->gui->showMenuOnLoad = $r_showMenuOnLoad;
        $xml->gui->showAllOnOffBtnInMenu = $r_showAllOnOffBtnInMenu;
        $xml->gui->sortOrderDevices = $r_sortOrderDevices;
        $xml->gui->sortOrderGroups = $r_sortOrderGroups;
        $xml->gui->sortOrderRooms = $r_sortOrderRooms;
        $xml->gui->sortOrderTimers = $r_sortOrderTimers;
        $xml->gui->theme = $r_theme;
        if(check_config_global()) {
            echo "ok";
            config_save();
        }
        break;
    
    default:
        echo "action unsupported";
        break;
}


?>

