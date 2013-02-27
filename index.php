<?php

//voraussetzungen
if(phpversion() < '5.3.0') {
    echo "please use php >= 5.3.x";
    exit;
}


// Suppress DateTime warnings
//date_default_timezone_set(@date_default_timezone_get());
date_default_timezone_set('UTC');


/*
Hilfreiche Links:

Dev:
http://developer.apple.com/library/safari/#documentation/iPhone/Conceptual/SafariJSDatabaseGuide/OfflineApplicationCache/OfflineApplicationCache.html
http://jquerymobile.com/demos/1.0rc2/docs/pages/page-cache.html

Config:
http://www.fhemwiki.de/wiki/Intertechno_Code_Berechnung
http://isn-systems.com/tools/it2elro/
*/


//config.xml einlesen
libxml_use_internal_errors(true);
$xml = simplexml_load_file('config.xml');
if (!$xml) {
    echo "Laden des XML fehlgeschlagen\n";
    foreach(libxml_get_errors() as $error) {
        echo "\t", $error->message;
    }
    exit(1);
}


//globale variabeln
$debug=empty($xml["debug"]) ? "false" : $xml["debug"];
$authentificated=false;
$errormessage="";


//funktion um in das debug log zu schreiben
function debug($msg) {
    global $debug;
    if($debug == "true") {
		date_default_timezone_set("UTC");
        $file = 'debug.log';
        $handle = fopen ($file, 'a+');
        fwrite($handle, date("Y-m-d H:i:s",time())." ".$_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_TIME']."   ".$msg."\r\n");
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


function cul_send($msg) {
    debug("Sending Message to CUL");
    global $debug;
    global $xml;
    global $errormessage;
    $len = strlen($msg);
    foreach($xml->culs->cul as $cul) {
        debug("Sending Message '".$msg."' to CUL ".(string)$cul->device);
        $handle = fopen((string)$cul->device, "w");
        if(!$handle) {
            echo "error opening CUL ".(string)$cul->device."\n";
            $errormessage="error opening CUL ".(string)$cul->device."\n";
            return;
        }
        fwrite($handle, $msg, $len);
        fclose($handle);
        $errormessage="Befehl an CUL gesendet \n";
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
    if($xml->connairs->count() > 0) {
        $msg="";
        if ($action=="ON" && !empty($device->address->rawCodeOn)) {
            $msg = $device->address->rawCodeOn;
        } else if ($action=="OFF" && !empty($device->address->rawCodeOff)) {
            $msg = $device->address->rawCodeOff;
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
    if($xml->culs->count() > 0) {
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
    global $xml;
    if($xml->timers->count() > 0 ) {
        // Sonnenauf- und -untergangskonfiguration (sunrise = Sonnenaufgang = SU (Sun Up) // sunset = Sonnenuntergang = SD (Sun Down)
        $latitude=empty($xml->global->latitude) ? 48.64727 : ($xml->global->latitude)*1;
        $longitude=empty($xml->global->longitude) ? 9.44858 : ($xml->global->longitude)*1;
        $sunrise = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+5/6, date("O")/100);
        $sunset = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+5/6, date("O")/100);
        //Aktuelle Zeit ermitteln und Puffer definieren, die beim Timer berücksichtigt werden sollen
        $now = strtotime(date('G:i'));
        $timepuffer = 5.5; // Zeitpuffer in Minuten
        $timeWindowStart = $now - (60 * $timepuffer);
        $timeWindowStop = $now;
        //Wochentag ermitteln
        $nowday = date("N") -1;
        $preday = date ("N", time() - ( 24 * 60 * 60)) -1; //Vortag
        // Timer auslesen und bei gefunden Timern Aktionen ausführen
        foreach($xml->timers->timer as $timer) {
            $timerday=(string)$timer->day;
            ###### Timer ermitteln ################
            // On Timer
            switch ($timer->timerOn) {
                case "SU":
                debug();
                    $OnTimer = $sunrise;
                    break;
                case "SD":
                    $OnTimer = $sunset;
                    break;
                default:
                    $OnTimer = strtotime($timer->timerOn);
            }
            // Off Timer
            switch ($timer->timerOff) {
                case "SU":
                    $OffTimer = $sunrise;
                    break;
                case "SD":
                    $OffTimer = $sunset;
                    break;
                default:
                    $OffTimer = strtotime($timer->timerOff);
            }
            ###### Timer On bearbeiten ############
            // Prüfen, ob aktueller Tag mit dem OnTimer Tag zulässig ist
            $checkDayOn = strpos("MDTWFSS",$timerday[$nowday]);
            if (is_numeric($checkDayOn)) {
                //debug("TimerID:".$timer->id." OnTimer ".date('h:i', $OnTimer)." Von ".date('h:i', $timeWindowStart)." - ".date('h:i', $timeWindowStop));
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
                //debug("TimerID:".$timer->id." OffTimer ".date('h:i', $OffTimer)." Von ".date('h:i', $timeWindowStart)." - ".date('h:i', $timeWindowStop));
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

    if (($r_action)=="alloff") {
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
    $xml->asXML("config.xml"); 
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
        $.event.special.swipe.horizontalDistanceThreshold=180;
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
/*
                setTimeout(function() {
                    $.mobile.activePage.find('#mypanel').panel( "close" );
                }, 1000);
*/
            }, 1000);
<?php 
	}
?>
    });
</script>
<script type="text/javascript" charset="utf-8" src="jquery.mobile-1.3.0.min.js"></script>
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
            $('#deviceRow'+id).removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
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
                $('#btnOn'+id).attr('data-theme', newTheme).parent('.ui-btn').removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
                newTheme = offColor;
                $('#btnOff'+id).attr('data-theme', newTheme).parent('.ui-btn').removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
            } else if(action == "off") {
                newTheme = onColor;
                $('#btnOn'+id).attr('data-theme', newTheme).parent('.ui-btn').removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
                newTheme = curColor;
                $('#btnOff'+id).attr('data-theme', newTheme).parent('.ui-btn').removeClass(rmbtnClasses).addClass('ui-btn-up-' + newTheme).attr('data-theme', newTheme);
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
                $('#btnOn'+id).buttonMarkup({ icon: onIcon });
            } else if(action == "off") {
                $('#btnOn'+id).buttonMarkup({ icon: offIcon });
            }
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
    ksort($roomDevices);
    foreach($roomDevices as $room => $devices) {
        sort($devices);
?>

            <li data-role="list-divider" role="heading" data-theme="a">
                    <div class="ui-grid-a">
	                    <div class="ui-block-a" style="text-align:left"><?php echo $room; ?></div>
	                    <div class="ui-block-b" style="text-align:right">
	                        <button data-theme="a"  data-mini="true" data-inline="true" onclick="send_connair('on','room','<?php echo $room; ?>')">Ein</button>
	                        <button data-theme="a"  data-mini="true" data-inline="true" onclick="send_connair('off','room','<?php echo $room; ?>')">Aus</button>
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
	                    	if($debug) {
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
        <div data-role="controlgroup" data-type="horizontal">
            <a href="index.html" data-role="button" data-theme="g">I</a>
            <a href="index.html" data-role="button" data-theme="r">0</a>
        </div>
        <div data-role="controlgroup" data-mini="true" data-type="horizontal">
            <a href="index.html" data-role="button" data-theme="g">I</a>
            <a href="index.html" data-role="button" data-theme="a">T</a>
            <a href="index.html" data-role="button" data-theme="r">0</a>
        </div>
        <div data-role="controlgroup" data-mini="true" data-type="horizontal">
            <a href="index.html" data-role="button" data-theme="g">An</a>
            <a href="index.html" data-role="button" data-theme="e" data-icon="check" data-iconpos="notext"></a>
            <a href="index.html" data-role="button" data-theme="r">Aus</a>
        </div>
        <div data-role="controlgroup" data-mini="true" data-type="horizontal">
            <a href="index.html" data-role="button" data-theme="g">An</a>
            <a href="index.html" data-role="button" data-theme="c" data-icon="minus" data-iconpos="notext"></a>
            <a href="index.html" data-role="button" data-theme="r">Aus</a>
        </div>
        <button data-theme="g" data-inline="true" onclick="send_connair('on',1)">Ein</button>
        <button data-theme="r" data-inline="true" onclick="send_connair('off',1)">Aus</button>
        <br>
        <button data-theme="e" data-mini="true" data-inline="true" data-icon="check" data-iconpos="notext"></button>
        <button data-theme="g" data-mini="true" data-inline="true" onclick="send_connair('on',1)">Ein</button>
        <button data-theme="r" data-mini="true" data-inline="true" onclick="send_connair('off',1)">Aus</button>
        <br>
        <button data-theme="c" data-mini="true" data-inline="true" data-icon="minus" data-iconpos="notext"></button>
        <button data-theme="g" data-mini="true" data-inline="true" onclick="send_connair('on',1)">Ein</button>
        <button data-theme="r" data-mini="true" data-inline="true" onclick="send_connair('off',1)">Aus</button>
        <br>
        <button data-theme="g"   data-inline="true" onclick="send_connair('on',1)">I</button>
        <button data-theme="r"   data-inline="true" onclick="send_connair('off',1)">O</button>
        <br>
        <button data-theme="g"  data-mini="true" data-inline="true" onclick="send_connair('on',1)">I</button>
        <button data-theme="r"  data-mini="true" data-inline="true" onclick="send_connair('off',1)">O</button>

    </div><!-- /content -->
</div><!-- /page -->










<div data-role="page" id="newdevice" data-theme="e" data-close-btn="none">

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
    //debug("END");  
?> 
