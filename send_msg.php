<?php

if((!isset($directaccess)) OR (!$directaccess)) die();


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


?>
