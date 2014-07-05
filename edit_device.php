<?php

$directaccess = true;

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

require("config.php");

/*
    <devices>
        <device>
            <id>1</id>
            <name>Lichterkette Wohnzimmertür</name>
            <vendor>intertechno</vendor>
            <address>
                <masterdip>M</masterdip>
                <slavedip>2</slavedip>
                <tx433version/>
                <rawCodeOn/>
                <rawCodeOff/>
            </address>
            <room>Flur</room>
            <favorite>true</favorite>
            <status>OFF</status>
        </device>
*/

$r_action = (string)$_POST['action'];
$r_id = (string)$_POST['id'];
$r_name = (string)$_POST['name'];
$r_room = (string)$_POST['room'];
$r_vendor = (string)$_POST['vendor'];
$r_masterdip = (string)$_POST['masterdip'];
$r_slavedip = (string)$_POST['slavedip'];
$r_tx433version = (string)$_POST['tx433version'];
$r_btnLabelOn = (string)$_POST['btnLabelOn'];
$r_btnLabelOff = (string)$_POST['btnLabelOff'];
$r_favorite = (string)$_POST['favorite'];

switch ($r_action) {

    case "add":

        $newid=1;
        foreach($xml->devices->device as $device) {
            $oldid=(integer)$device->id;
            if($oldid >= $newid) {
                $newid = $oldid + 1;
            }
        }

        $newdevice = $xml->devices->addChild('device');
        
        if(!empty($r_btnLabelOn)) {
            $newdevice->addAttribute('buttonLabelOn', $r_btnLabelOn);
        }
        if(!empty($r_btnLabelOff)) {
            $newdevice->addAttribute('buttonLabelOff', $r_btnLabelOff);
        }
        
        $newdevice->addChild('id', $newid);
        $newdevice->addChild('name', $r_name);
        $newdevice->addChild('vendor', $r_vendor);

        $newdeviceaddress = $newdevice->addChild('address');
        $newdeviceaddress->addChild('masterdip', $r_masterdip);
        $newdeviceaddress->addChild('slavedip', $r_slavedip);
        $newdeviceaddress->addChild('tx433version', $r_tx433version);
        $newdeviceaddress->addChild('rawCodeOn');
        $newdeviceaddress->addChild('rawCodeOff');

        $newdevice->addChild('room', $r_room);
        $newdevice->addChild('favorite', $r_favorite);
        $newdevice->addChild('status', 'OFF');
    
        if(check_device($newdevice)) {
            echo "ok";
            config_save();
        }
    
        break;
    
    case "edit":
        break;
    
    case "delete":
        $xpath='//device/id[.="'.$r_id.'"]/parent::*';
        $res = $xml->xpath($xpath); 
        $parent = $res[0]; 
        unset($parent[0]);
        echo "ok";
        config_save();
	break;
    
    default:
        echo "unsupported";
        break;
}


?>

