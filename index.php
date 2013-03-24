<?php

$directaccess = true;

/*
Hilfreiche Links:

Dev:
http://developer.apple.com/library/safari/#documentation/iPhone/Conceptual/SafariJSDatabaseGuide/OfflineApplicationCache/OfflineApplicationCache.html
http://jquerymobile.com/demos/1.0rc2/docs/pages/page-cache.html
http://code.google.com/p/jqueryrotate/

Config:
http://www.fhemwiki.de/wiki/Intertechno_Code_Berechnung
http://isn-systems.com/tools/it2elro/
*/


// Suppress DateTime warnings
date_default_timezone_set(@date_default_timezone_get());

require("config.php");

if(!empty($xml->global->timezone)) {
    date_default_timezone_set($xml->global->timezone);
}

$authentificated=false;
$errormessage="";


//funktion um in das debug log zu schreiben
function debug($msg) {
    global $debug;
    if($debug == "true") {
		$file = 'debug.log';
        $handle = fopen ($file, 'a');
        fwrite($handle, date("Y-m-d H:i:s")." ".$_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_TIME']."   ".$msg."\r\n");
        fclose($handle);
    }
}


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


function connair_send($msg) {
    debug("Sending Message to ConnAir");
    global $debug;
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
        if ((string)$connair["type"]=="itgw") {
            $newmsg=str_replace("TXP:","",$msg);
            $newmsg=str_replace("#baud#","26,0",$newmsg);
        } else {
            $newmsg=str_replace("#baud#","25",$msg);
        }
        debug("Sending Message '".$newmsg."' to ConnAir ".(string)$connair->address.":".(integer)$connair->port);
        if( ! socket_sendto ( $sock , $newmsg, $len , 0, (string)$connair->address , (integer)$connair->port)) {
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


function connair_create_msg_brennenstuhl($device, $action) {
    debug("Create ConnAir Message for Brennenstuhl device='".(string)$device->id."' action='".(string)$action."'");  
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
    $sBaud="#baud#";
    $sSpeed=16;
    $uSleep=800000;
    if ($device->address->tx433version==1) {
        $txversion=3;
    } else {
        $txversion=1;
    }
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
        if($bit=="0") {
            $msg=$msg.$seqLow;
        } else {
            $msg=$msg.$seqHgh;
        }
    }
    $msgM=$msg;
    $bits=$device->address->slavedip;
    $msg="";
    for($i=0;$i<strlen($bits);$i++) {
        $bit=substr($bits,$i,1);
        if($bit=="0") {
            $msg=$msg.$seqLow;
        } else {
            $msg=$msg.$seqHgh;
        }
    }
    $msgS=$msg;
    if($action=="ON") {
        return $HEAD.$bitLow.",".$msgM.$msgS.$bitHgh.",".$AN.$TAIL;
    } else {
        return $HEAD.$bitLow.",".$msgM.$msgS.$bitHgh.",".$AUS.$TAIL;
    }
}   


function connair_create_msg_intertechno($device, $action) {
    debug("Create ConnAir Message for Intertechno device='".(string)$device->id."' action='".(string)$action."'");  
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
    $sBaud="#baud#";
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
    switch (strtoupper($device->address->masterdip)) {
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
    if($action=="ON") {
        return $HEAD.$bitLow.",".$msgM.$msgS.$seqHgh.$seqLow.$bitHgh.",".$AN.$TAIL;
    } else {
        return $HEAD.$bitLow.",".$msgM.$msgS.$seqHgh.$seqLow.$bitHgh.",".$AUS.$TAIL;
    }
}


/*
system code 10111
Dann in Reihenfolge unit code
A 10000
B 01000
E 00001   

Elro AB440D 200W       TXP:0,0,10,5600,350,25   ,16:
Elro AB440D 300W       TXP:0,0,10,5600,350,25   ,16:
Elro AB440ID           TXP:0,0,10,5600,350,25   ,16:
Elro AB440IS           TXP:0,0,10,5600,350,25   ,16:
Elro AB440L            TXP:0,0,10,5600,350,25   ,16:
Elro AB440WD           TXP:0,0,10,5600,350,25   ,16:
*/
function connair_create_msg_elro($device, $action) {
    debug("Create ConnAir Message for Elro device='".(string)$device->id."' action='".(string)$action."'");  
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
    $sBaud="#baud#";
    $sSpeed=14;
    $uSleep=800000;
    $HEAD="TXP:$sA,$sG,$sRepeat,$sPause,$sTune,$sBaud,";
    $TAIL="1,$sSpeed,;";
    $AN="1,3,1,3,1,3,3,1,";
    $AUS="1,3,3,1,1,3,1,3,";
    $bitLow=1;
    $bitHgh=3;
    $seqLow=$bitLow.",".$bitHgh.",".$bitLow.",".$bitHgh.",";
    $seqHgh=$bitLow.",".$bitHgh.",".$bitHgh.",".$bitLow.",";
    $bits=$device->address->masterdip;
    $msg="";
    for($i=0;$i<strlen($bits);$i++) {   
        $bit=substr($bits,$i,1);
        if($bit=="1") {
            $msg=$msg.$seqLow;
        } else {
            $msg=$msg.$seqHgh;
        }
    }
    $msgM=$msg;
    $bits=$device->address->slavedip;
    $msg="";
    for($i=0;$i<strlen($bits);$i++) {
        $bit=substr($bits,$i,1);
        if($bit=="1") {
            $msg=$msg.$seqLow;
        } else {
            $msg=$msg.$seqHgh;
        }
    }
    $msgS=$msg;
    if($action=="ON") {
        return $HEAD.$msgM.$msgS.$AN.$TAIL;
    } else {
        return $HEAD.$msgM.$msgS.$AUS.$TAIL;
    }
}


function cul_send($msg) {
    debug("Sending Message to CUL");
    global $debug;
    global $xml;
    global $errormessage;
    $len = strlen($msg);
    foreach($xml->culs->cul as $cul) {
        debug("Sending Message '".$msg."' to CUL ".(string)$cul->device);
        if(is_writable((string)$cul->device)) {
            $handle = fopen((string)$cul->device, "wb");
            if(!$handle) {
                $errormessage="CUL Device ".(string)$cul->device." ist nicht schreibbar!\n";
                debug($errormessage);
                echo $errormessage;
                continue;
            }
            fwrite($handle, $msg, $len);
            fclose($handle);
            $errormessage="Befehl an CUL gesendet \n";
        } else {
            $errormessage="CUL Device ".(string)$cul->device." ist nicht schreibbar!\n";
            debug($errormessage);
            echo $errormessage;
        }
    }
}


function cul_create_msg_intertechno($device, $action) {
    debug("Create CUL Message for Intertechno device='".(string)$device->id."' action='".(string)$action."'");  
    if(empty($device->address->masterdip)) {
        echo "ERROR: masterdip ist ungültig für device id ".$device->id;
        return;
    }
    if(empty($device->address->slavedip)) {
        echo "ERROR: slavedip ist ungültig für device id ".$device->id;
        return;
    }
    $AN="FF";
    $AUS="F0";
    switch (strtoupper($device->address->masterdip)) {
        case "A":
            $msgM="0000";
            break;
        case "B":
            $msgM="F000";
            break;   
        case "C":
            $msgM="0F00";
            break; 
        case "D":
            $msgM="FF00";
            break;
        case "E":
            $msgM="00F0";
            break;
        case "F":
            $msgM="F0F0";
            break;
        case "G":
            $msgM="0FF0";
            break;
        case "H":
            $msgM="FFF0";
            break;
        case "I":
            $msgM="000F";
            break;
        case "J":
            $msgM="F00F";
            break;
        case "K":
            $msgM="0F0F";
            break;
        case "L":
            $msgM="FF0F";
            break;
        case "M":
            $msgM="00FF";
            break;
        case "N":
            $msgM="F0FF";
            break;
        case "O":
            $msgM="0FFF";
            break;
        case "P":
            $msgM="FFFF";
            break;
    }
    $msgS="";   
    switch ($device->address->slavedip){
        case "1":
            $msgS="0000";
            break;
        case "2":
            $msgS="F000";
            break;   
        case "3":
            $msgS="0F00";
            break; 
        case "4":
            $msgS="FF00";
            break;
        case "5":
            $msgS="00F0";
            break;
        case "6":
            $msgS="F0F0";
            break;
        case "7":
            $msgS="0FF0";
            break;
        case "8":
            $msgS="FFF0";
            break;
        case "9":
            $msgS="000F";
            break;
        case "10":
            $msgS="F00F";
            break;
        case "11":
            $msgS="0F0F";
            break;
        case "12":
            $msgS="FF0F";
            break;
        case "13":
            $msgS="00FF";
            break;
        case "14":
            $msgS="F0FF";
            break;
        case "15":
            $msgS="0FFF";
            break;
        case "16":
            $msgS="FFFF";
            break;
    }
    if($action=="ON") {
        return "is".$msgM.$msgS."0F".$AN."\n";
    } else {
        return "is".$msgM.$msgS."0F".$AUS."\n";
    }
}


function send_message($device, $action) {
    debug("Send Message for device='".(string)$device->id."' action='".(string)$action."'");
    global $xml;
    $vendor=strtolower($device->vendor);
    //wenn connairs configuriert senden
    if(@count($xml->connairs->children()) > 0) {
        $msg="";
        if ($vendor=="raw") {
            if ($action=="ON") {
                $msg = $device->address->rawCodeOn;
            } else {
                $msg = $device->address->rawCodeOff;
            }    
        } else if ($vendor=="brennenstuhl") {
            $msg = connair_create_msg_brennenstuhl($device, $action);
        } else if ($vendor=="intertechno") {
            $msg = connair_create_msg_intertechno($device, $action);
        } else if ($vendor=="elro") {
            $msg = connair_create_msg_elro($device, $action);
        }
        if(!empty($msg)) {
            connair_send($msg);
            $device->status = $action;
        }
    }
    //wenn CULS Configuriert auch über die senden
    if(@count($xml->culs->children()) > 0) {
        $msg="";
        if ($vendor=="intertechno" && !empty($device->address->masterdip) && !empty($device->address->slavedip)) {
            $msg = cul_create_msg_intertechno($device, $action);
        }
        if(!empty($msg)) {
            cul_send($msg);
            $device->status = $action;
        }
    }
}


function timer_check() {
//    debug("Timer Checking...");
    global $xml;
    if(@count($xml->timers->children()) > 0 ) {
        // Sonnenauf- und -untergangskonfiguration (sunrise = Sonnenaufgang = SU (Sun Up) // sunset = Sonnenuntergang = SD (Sun Down)
        $latitude=empty($xml->global->latitude) ? 48.64727 : ($xml->global->latitude)*1;
        $longitude=empty($xml->global->longitude) ? 9.44858 : ($xml->global->longitude)*1;
        $sunrise = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+5/6, date("O")/100);
        $sunset = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+5/6, date("O")/100);
        //Aktuelle Zeit ermitteln und Puffer definieren, die beim Timer berücksichtigt werden sollen
        $now = time();
        $timepuffer = 5.5; // Zeitpuffer in Minuten
        $timeWindowStart = $now - (60 * $timepuffer);
        $timeWindowStop = $now;
        //Wochentag ermitteln
        $nowday = date("N") -1;
        $preday = date ("N", time() - ( 24 * 60 * 60)) -1; //Vortag
        // Timer auslesen und bei gefunden Timern Aktionen ausführen
        foreach($xml->timers->timer as $timer) {
//            debug("Timer: \n".$timer->asXML());
            $timerday=(string)$timer->day;
            ###### Timer ermitteln ################
            // On Timer
            switch ($timer->timerOn) {
                case "SU":
                    $OnTimer = $sunrise;
                    if(!empty($timer->timerOn[offset])) {
                        $OnTimer += ($timer->timerOn[offset]*60);
                    }
                    break;
                case "SD":
                    $OnTimer = $sunset;
                    if(!empty($timer->timerOn[offset])) {
                        $OnTimer += ($timer->timerOn[offset]*60);
                    }
                    break;
                default:
                    $OnTimer = strtotime($timer->timerOn);
            }
            // Off Timer
            switch ($timer->timerOff) {
                case "SU":
                    $OffTimer = $sunrise;
                    if(!empty($timer->timerOff[offset])) {
                        $OffTimer += ($timer->timerOff[offset]*60);
                    }
                    break;
                case "SD":
                    $OffTimer = $sunset;
                    if(!empty($timer->timerOff[offset])) {
                        $OffTimer += ($timer->timerOff[offset]*60);
                    }
                    break;
                default:
                    $OffTimer = strtotime($timer->timerOff);
            }
            ###### Timer On bearbeiten ############
            // Prüfen, ob aktueller Tag mit dem OnTimer Tag zulässig ist
            $checkDayOn = strpos("MDTWFSS",$timerday[$nowday]);
            if (is_numeric($checkDayOn)) {
                // debug("Timer Tag stimmt (ON) ".$timer->id);
                // debug("++++TimerID:".$timer->id." OnTimer ".date('H:i', $OnTimer)." Von ".date('H:i', $timeWindowStart)." - ".date('H:i', $timeWindowStop));
                // Tag gültig -> Prüfen, ob On Timer innerhalb des Zeitfensters liegt
                if (($OnTimer >= $timeWindowStart) && ($OnTimer <= $timeWindowStop)) {
                    // Timer liegt innerhalb des Zeitfensters -> Schaltungen durchführen
                    debug("Timer in action (ON) ".$timer->id);
                    $action = "ON";
                    // Timer mit Device
                    if (($timer->type)=="device") {
                        $devicesFound = $xml->xpath("//devices/device/id[text()='".$timer->typeid."']/parent::*");
                        $device = $devicesFound[0];
                        send_message($device, $action);
                    }
                    // Timer mit Room
                    if (($timer->type)=="room") {
                        $devicesFound = $xml->xpath("//devices/device/room[text()='".$timer->typeid."']/parent::*");
                        foreach($devicesFound as $device) {
                            send_message($device, $action);
                            usleep(300000);
                        }
                    }
                    // Timer mit Group
                    if (($timer->type)=="group") {
                        $groupsFound = $xml->xpath("//groups/group/id[text()='".$timer->typeid."']/parent::*");
                        foreach($groupsFound[0]->deviceid as $deviceid) {
                            $devicesFound = $xml->xpath("//devices/device/id[text()='".$deviceid."']/parent::*");
                            $device = $devicesFound[0];
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
                            usleep(300000);
                        }
                    }
                    config_save();
                }
            }
            ###### Timer Off bearbeiten ############
            // Prüfen, ob aktueller Tag mit dem OffTimer Tag zulässig ist
            if ($OffTimer < $OnTimer) {
                // OffTimer ist geringer als OnTimer => Für die Zulässigkeitsprüfung wird der PHP Vortag genommen
                $checkDayOff = strpos("MDTWFSS",$timerday[$preday]);
            } else {
                // Off Timer ist höher als OnTimer => Für die Zulässigkeitsprüfung wird der aktuelle PHP Tag genommen
                $checkDayOff = strpos("MDTWFSS",$timerday[$nowday]);
            }
            if (is_numeric($checkDayOff)) {
//                debug("Timer Tag stimmt (OFF) ".$timer->id);
//                debug("TimerID:".$timer->id." OffTimer ".date('H:i', $OffTimer)." Von ".date('H:i', $timeWindowStart)." - ".date('H:i', $timeWindowStop));
                // Tag gültig -> Prüfen, ob On Timer innerhalb des Zeitfensters liegt
                if (($OffTimer >= $timeWindowStart) && ($OffTimer <= $timeWindowStop)) {
                    // Timer liegt innerhalb des Zeitfensters -> Schaltungen durchführen
                    debug("Timer in action (OFF) ".$timer->id);
                    $action = "OFF";                  
                    // Timer mit Device
                    if (($timer->type)=="device") {
                        $devicesFound = $xml->xpath("//devices/device/id[text()='".$timer->typeid."']/parent::*");
                        $device = $devicesFound[0];
                        send_message($device, $action);
                    }
                    // Timer mit Room
                    if (($timer->type)=="room") {
                        $devicesFound = $xml->xpath("//devices/device/room[text()='".$timer->typeid."']/parent::*");
                        foreach($devicesFound as $device) {
                            send_message($device, $action);
                            usleep(300000);
                        }
                    }
                    // Timer mit Group
                    if (($timer->type)=="group") {
                        $groupsFound = $xml->xpath("//groups/group/id[text()='".$timer->typeid."']/parent::*");
                        foreach($groupsFound[0]->deviceid as $deviceid) {
                            $devicesFound = $xml->xpath("//devices/device/id[text()='".$deviceid."']/parent::*");
                            $device = $devicesFound[0];
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
                            usleep(300000);
                        }
                    }
                    config_save();
                }           
            }
        }
    }
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
    timer_check();
    exit();
}



if (isset($r_action)) {
    debug("Running in action='".$r_action."'");  

    if (($r_action)=="editdevice") {
        
        
        

    } else if (($r_action)=="edittimer") {
        
        
        

    } else if (($r_action)=="alloff") {
        foreach($xml->devices->device as $device) {
            send_message($device, "OFF");
            usleep(300000);
        }
        echo $errormessage;

    } else if (($r_action)=="allon") {
        foreach($xml->devices->device as $device) {
            send_message($device, "ON");
            usleep(300000);
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
                usleep(300000);
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
                usleep(300000);
            }
        }
        echo $errormessage;
    }
    config_save(); 
} else {
    debug("Sending HTML Site");  
    header("Content-Type: text/html; charset=utf-8");
?>


<!DOCTYPE html>
<!--html manifest="cache.manifest"-->
<html>
<head>
<meta charset="UTF-8">
<title>Mobile Connair</title>

<link rel="stylesheet" href="jquery.mobile-1.3.0.min.css" />
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

</style>

    
<script type="text/javascript" charset="utf-8" src="jquery-1.9.0.min.js"></script>
<script type="text/javascript">
    $(document).bind("mobileinit", function(){
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
            }, 1000);
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
		            //alert('response:'+response);
		            if(response.trim()=="ok") {
		                $('#newdevice').dialog('close');
		                toast('gespeichert');
		                refreshPage();
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
		                toast('gespeichert');
		                refreshPage();
                    } else {
                        toast('response:'+response);
                    }
	            }
            });
	    });
    });
</script>
<script type="text/javascript" charset="utf-8" src="jquery.mobile-1.3.0.min.js"></script>
<script type="text/javascript" charset="utf-8" src="jquery.toast.mobile.js"></script>


<!-- WebApp -->
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scaleable=no">
<!--
<meta name="viewport" content="320.1, initial-scale=1.0">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scaleable=no">
-->
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
    </script>
</head>
<body>










<div data-role="page" id="favorites">

    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
        <center>
            <a href="#favorites" data-role="button" data-theme="e" class="ui-disabled">Favoriten</a>
            <!--a href="#my-header" data-rel="close" data-role="button" data-theme="b">Favoriten</a-->
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#favorites" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
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
                    $rowDataTheme="c";
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
                    $rowDataTheme="c";
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
                    $rowDataTheme="c";
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
	                    <div class="ui-block-a" style="text-align:left"><?php echo $device->name; ?></div>
	                    <div class="ui-block-b" style="text-align:right">
	                        <button id="btnOn<?php echo $device->id; ?>" data-theme="<?php echo $btnOnDataTheme; ?>" data-mini="true" data-inline="true" <?php if(!empty($btnOnIcon)) { echo 'data-icon="'.$btnOnIcon.'"'; } ?> onclick="<?php echo $btnOnJS; ?>">Ein</button>
	                        <button id="btnOff<?php echo $device->id; ?>" data-theme="<?php echo $btnOffDataTheme; ?>" data-mini="true" data-inline="true" onclick="<?php echo $btnOffJS; ?>">Aus</button>
	                    </div>
                    </div>
                    <p><?php echo $device->room; ?></p>
                </li>

<?php
        }
?>


        </ul>
    </div><!-- /content -->
</div><!-- /page -->









<div data-role="page" id="devices">

    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
	    <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e" class="ui-disabled">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#devices" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->
 
       
    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Geräte</h1>
        <a href="#newdevice" data-rel="dialog" data-transition="slidedown">+</a>
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

            <li data-role="list-divider" role="heading" data-theme="a">
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left"><?php echo $room; ?></div>
	                    <div class="ui-block-b" style="text-align:right">
<?php
    if($xml->gui->showRoomButtonInDevices == "true") {
?>
	                        <button data-theme="a"  data-mini="true" data-inline="true" onclick="send_connair('on','room','<?php echo $room; ?>')">Ein</button>
	                        <button data-theme="a"  data-mini="true" data-inline="true" onclick="send_connair('off','room','<?php echo $room; ?>')">Aus</button>
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
                $rowDataTheme="c";
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
                $rowDataTheme="c";
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
                $rowDataTheme="c";
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
	                    <?php 
	                    	if($debug == "true") {
	                    		echo "<h3>".$device->name."</h3>";
	                    		echo "<p><i>".$device->id." ".$device->vendor." ".$device->address->masterdip." ".$device->address->slavedip."</i></p>";
	                    	} else {
	                    		echo $device->name;
	                    	}
	                    ?>
	                    </div>
	                    <div class="ui-block-b" style="text-align:right">

<?php 
    if($xml->gui->showDeviceStatus == "BUTTON_SLIDER") {
?>

                            <select name="btn<?php echo $device->id; ?>" id="btn<?php echo $device->id; ?>" data-role="slider" data-mini="true">
                                <option value="off" <?php if($device->status == "OFF") { echo "selected"; } ?>>Aus</option>
                                <option value="on" <?php if($device->status == "ON") { echo "selected"; } ?>>An</option>
                            </select>
                            <script type="text/javascript">
                                $('#btn<?php echo $device->id; ?>').on('slidestop', function(){
                                    send_connair($(this).slider().val(),'device',<?php echo $device->id; ?>);
                                });
                            </script>

<?php
    } else {
?>

	                        <button id="btnOn<?php echo $device->id; ?>" data-theme="<?php echo $btnOnDataTheme; ?>" data-mini="true" data-inline="true" <?php if(!empty($btnOnIcon)) { echo 'data-icon="'.$btnOnIcon.'"'; } ?> onclick="<?php echo $btnOnJS; ?>">Ein</button>
	                        <button id="btnOff<?php echo $device->id; ?>" data-theme="<?php echo $btnOffDataTheme; ?>" data-mini="true" data-inline="true" onclick="<?php echo $btnOffJS; ?>">Aus</button>

<?php
    }
?>

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








<div data-role="page" id="groups">
    
    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
        <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e" class="ui-disabled">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#groups" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Gruppen</h1>
    </div><!-- /header -->

    <div data-role="content">  

        <ul data-role="listview" data-divider-theme="e" data-inset="false">
 
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









<div data-role="page" id="rooms">

    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
	    <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e" class="ui-disabled">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#rooms" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
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
    switch ($xml->gui->sortOrderRooms){
        case "SORT_BY_NAME":
            ksort($roomDevices);
            break;
        default:
            break;
    }
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










<div data-role="page" id="timers">

    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
       <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e" class="ui-disabled">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#timers" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->
 
       
    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Timer</h1>
        <a href="#newtimer" data-rel="dialog" data-transition="slidedown">+</a>
    </div><!-- /header -->


    <div data-role="content"> 
        <ul data-role="listview" data-divider-theme="e" data-inset="false">

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

                <li data-theme="c">
                    <div class="ui-grid-a">
                       <div class="ui-block-a" style="text-align:left">
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
                        </div>
                        <div class="ui-block-b" style="text-align:right">
                            <a href="#newtimer" data-role="button" data-mini="true" data-inline="true" data-theme="r" data-rel="dialog" data-transition="slidedown">Edit</a>
                        </div>
                    </div>
                    <p></p>
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
            break;
        case "SU":
            echo "Sonnenaufgang";
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
            break;
        case "SU":
            echo "Sonnenaufgang";
            break;
        default:
            echo $timer->timerOff." Uhr";
            break;
    }
?>
                    </p>
                </li>

<?php
    }
?>
   
         </ul>
    </div><!-- /content -->
</div><!-- /page -->











<div data-role="page" id="configurations">

    <div data-role="panel" id="mypanel" data-position="left" data-display="reveal" data-theme="a">
        <center>
            <a href="#favorites" data-role="button" data-theme="e">Favoriten</a>
            <a href="#devices" data-role="button" data-theme="e">Geräte</a>
            <a href="#groups" data-role="button" data-theme="e">Gruppen</a>
            <a href="#rooms" data-role="button" data-theme="e">Räume</a>
            <a href="#timers" data-role="button" data-theme="e">Timer</a>
            <a href="#configurations" data-role="button" data-theme="e" class="ui-disabled">Einstellungen</a>
            <br />
            <div class="ui-grid-a">
                <div class="ui-block-a"><button data-theme="g" data-mini="true" data-rel="close" onclick="send_connair('allon')">Alle an</button></div>
                <div class="ui-block-b"><button data-theme="r" data-mini="true" data-rel="close" onclick="send_connair('alloff')">Alle aus</button></div>     
            </div>
            <br />
            <a href="#configurations" data-role="button" data-mini="true" data-theme="a" data-rel="close">Schliessen</a>
        </center>
    </div><!-- /panel -->

    <div data-role="header" data-position="fixed" data-tap-toggle="false">
        <a href="#mypanel">Menu</a>
        <h1>Einstellungen</h1>
    </div><!-- /header -->

   
    <div data-role="content">  
        
        
<form id="editconfigform" method="post" data-ajax="false">
<input type="hidden" name="action" id="action" value="edit" />
    <ul data-role="listview" data-inset="false">
        <li data-role="list-divider" data-theme="e">
        Global
        <li data-role="fieldcontain">
            <label for="debug">Debug Modus:</label>
            <select name="debug" id="debug" data-role="slider">
                <option value="false" <?php if($xml["debug"] == "false") { echo "selected"; } ?>>Off</option>
                <option value="true" <?php if($xml["debug"] == "true") { echo "selected"; } ?>>On</option>
            </select>
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
        <li data-role="list-divider" data-theme="e">
        GUI
        </li>
        <li data-role="fieldcontain">
            <label for="showDeviceStatus">Zeige Geräte Status:</label>
            <select name="showDeviceStatus" id="showDeviceStatus">
                <option value="OFF" <?php if($xml->gui->showDeviceStatus == "OFF") { echo "selected"; } ?>>OFF</option>
                <option value="ROW_COLOR" <?php if($xml->gui->showDeviceStatus == "ROW_COLOR") { echo "selected"; } ?>>ROW_COLOR</option>
                <option value="BUTTON_COLOR" <?php if($xml->gui->showDeviceStatus == "BUTTON_COLOR") { echo "selected"; } ?>>BUTTON_COLOR</option>
                <option value="BUTTON_ICON" <?php if($xml->gui->showDeviceStatus == "BUTTON_ICON") { echo "selected"; } ?>>BUTTON_ICON</option>
                <option value="BUTTON_SLIDER" <?php if($xml->gui->showDeviceStatus == "BUTTON_SLIDER") { echo "selected"; } ?>>BUTTON_SLIDER (Test)</option>
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
        <li class="ui-body ui-body-b">
            <fieldset class="ui-grid-a">
                    <div class="ui-block-a"><input type="reset" value="Abbrechen"  data-theme="r"/></div>
                    <div class="ui-block-b"><a href="#" id="editconfigsubmit" data-role="button" data-theme="g">Speichern</a></div>
            </fieldset>
        </li>
        <li data-role="list-divider" data-theme="e">
        System Informationen
        </li>
        <li data-role="fieldcontain">
            <label for="time">Server Zeit:</label>
            <input name="time" id="time" disabled="disabled" value="<?php echo date("Y-m-d H:i:s"); ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="timezone">Server Zeitzone:</label>
            <input name="timezone" id="timezone" disabled="disabled" value="<?php echo date_default_timezone_get(); ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="phpversion">PHP Version:</label>
            <input name="phpversion" id="phpversion" disabled="disabled" value="<?php echo phpversion(); ?>" type="text">
        </li>
        <li data-role="fieldcontain">
            <label for="serversoftware">Server Software:</label>
            <input name="serversoftware" id="serversoftware" disabled="disabled" value="<?php echo $_SERVER['SERVER_SOFTWARE']; ?>" type="text">
        </li>
    </ul>
</form>


    </div><!-- /content -->
</div><!-- /page -->










<div data-role="page" id="newdevice" data-theme="e" data-close-btn="none">

    <div data-role="header">
        <h1>Neues Gerät</h1>
    </div><!-- /header -->

    <div data-role="content">
        <form id="newdeviceform" method="post" data-ajax="false">
            <input type="hidden" name="action" id="action" value="add" />
            <div data-role="fieldcontain">
	            <label for="name">Name:</label>
	            <input type="text" name="name" id="name" value="" />
	            <br/>
	            <label for="name">Raum:</label>
	            <input type="text" name="room" id="room" value="" />
	            <br/>
	            <div data-role="fieldcontain">
                    <label for="vendor">Hersteller:</label>
                    <select name="vendor" id="vendor">
                        <option value="Brennenstuhl">Brennenstuhl</option>
                        <option value="Elro">Elro</option>
                        <option value="Intertechno">Intertechno</option>
                    </select>
                </div>
	            <br/>
	            
	            
	            
	            
	            
	            
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
	background: #ee0000;
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
	border-bottom: 15px solid #ee6666;
}

.off  {
	border-top: 15px solid #ee6666;
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
    $("#vendor").change(function() {
        if ($(this).val() == "Brennenstuhl" || $(this).val() == "Elro") {
            $("#dip_switch_box").removeClass().addClass('show');
        } else {
            $("#dip_switch_box").removeClass().addClass('hide');
        }
    });
    updateDIPTextField();
});
            


</script>



<div id="dip_switch_box" class="show">
	            <br/>

	            
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
	            <br/>
	            <br/>
	</div>            
	            
	            
	            
	            
	            
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
            <a href="#" id="newdevicesubmit" data-role="button" data-theme="g">Speichern</a>
            <a href="#" data-role="button" data-rel="back" data-theme="r">Abbrechen</a>
        </form>
    </div><!-- /content -->
</div><!-- /page -->
<script type="text/javascript">

</script>




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
});
</script>
<div data-role="page" id="newtimer" data-theme="e" data-close-btn="none">

    <div data-role="header">
        <h1>Neuer Timer</h1>
    </div><!-- /header -->

    <div data-role="content">
    <div><h1>Entwurf noch ohne Funktion</h1></div>
        <form id="newtimerform" method="post">
            <div data-role="fieldcontain">
              
              
            <div data-role="fieldcontain">
                <fieldset data-role="controlgroup" data-mini="true" data-type="horizontal">
                   <legend>Typ:</legend>
                        <input type="radio" name="timertype" id="timertype_device" value="device" checked="checked" />
                        <label for="timertype_device">Gerät</label>
            
                        <input type="radio" name="timertype" id="timertype_group" value="group"  />
                        <label for="timertype_group">Gruppe</label>
            
                        <input type="radio" name="timertype" id="timertype_room" value="room"  />
                        <label for="timertype_room">Raum</label>
            
                </fieldset>
            </div>

              
            <div data-role="fieldcontain" id="typeiddevice_box" class="show">
                <label for="typeiddevice">Gerät:</label>
                <select name="typeiddevice" id="typeiddevice" data-mini="true">
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
                <select name="typeidgroup" id="typeidgroup" data-mini="true">
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
                <select name="typeidroom" id="typeidroom" data-mini="true">
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
                        
               <div data-role="fieldcontain">
                <fieldset data-role="controlgroup" data-mini="true" data-type="horizontal">
                   <legend>Tage:</legend>
                        <input type="checkbox" name="timertype" id="radio-choice-1" value="0" />
                        <label for="radio-choice-1">M</label>
            
                        <input type="checkbox" name="timertype" id="radio-choice-2" value="1" />
                        <label for="radio-choice-2">D</label>
            
                        <input type="checkbox" name="timertype" id="radio-choice-3" value="2" />
                        <label for="radio-choice-3">M</label>
            
                        <input type="checkbox" name="timertype" id="radio-choice-4" value="3" />
                        <label for="radio-choice-4">D</label>
            
                        <input type="checkbox" name="timertype" id="radio-choice-5" value="4" />
                        <label for="radio-choice-5">F</label>
            
                        <input type="checkbox" name="timertype" id="radio-choice-6" value="5" />
                        <label for="radio-choice-6">S</label>
            
                        <input type="checkbox" name="timertype" id="radio-choice-7" value="6" />
                        <label for="radio-choice-7">S</label>
            
                </fieldset>
            </div>
              
           <div data-role="fieldcontain">
                <label for="OnTimerSun">An:</label>
                <select name="OnTimerSun" id="OnTimerSun" data-mini="true">
                    <option>Automatik</option>
                    <option>Sonnenaufgang</option>
                    <option>Sonnenuntergang</option>
                </select>
                                   
                <fieldset id="timer-an-zeit" data-role="controlgroup" data-type="horizontal">
                    <legend> </legend>
               
                    <label for="OnTimerHH">Stunden</label>
                    <select name="OnTimerHH" id="OnTimerHH" data-mini="true">
                        <option>Stunden</option>
                        <?php
                        for ($i = 0; $i <= 23; $i++) {
                         echo "<option value='".sprintf ("%02d", $i)."'>".sprintf ("%02d", $i)."</option>";
                     }
                     ?>
                    </select>
               
                    <label for="OnTimerMM">Minuten</label>
                    <select name="OnTimerMM" id="OnTimerMM" data-mini="true">
                        <option>Minuten</option>
                        <?php
                        for ($i = 0; $i <= 59; $i++) {
                         echo "<option value='".sprintf ("%02d", $i)."'>".sprintf ("%02d", $i)."</option>";
                     }
                     ?>
                    </select>

                </fieldset>
            </div>

            <div data-role="fieldcontain">
                <label for="OffTimerSun">Aus:</label>
                <select name="OffTimerSun" id="OffTimerSun" data-mini="true">
                    <option>Automatik</option>
                    <option>Sonnenaufgang</option>
                    <option>Sonnenuntergang</option>
                </select>
 
                 <fieldset id="timer-aus-zeit" data-role="controlgroup" data-type="horizontal">
                    <legend> </legend>
               
                    <label for="OffTimerHH">Stunden</label>
                    <select name="OffTimerHH" id="OffTimerHH" data-mini="true">
                        <option>Stunden</option>
                        <?php
                        for ($i = 0; $i <= 23; $i++) {
                         echo "<option value='".sprintf ("%02d", $i)."'>".sprintf ("%02d", $i)."</option>";
                     }
                     ?>
                    </select>
               
                    <label for="OffTimerMM">Minuten</label>
                    <select name="OffTimerMM" id="OffTimerMM" data-mini="true">
                        <option>Minuten</option>
                        <?php
                        for ($i = 0; $i <= 59; $i++) {
                         echo "<option value='".sprintf ("%02d", $i)."'>".sprintf ("%02d", $i)."</option>";
                     }
                     ?>
                    </select>
                  
                </fieldset>
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
    //debug("END");  
?> 
