<?php

// Idee und teile des Codes von http://www.ip-symcon.de/forum/threads/20752-Fritz!DECT-200-Einbindung-in-IPS?p=190131#post190131

if((!isset($directaccess)) OR (!$directaccess)) die();

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));



function Fritzbox_DECT200_Devices() {
    global $xml;
    $fritzbox_address = $xml->fritzbox->address;
    $devices="";
    $SID=Fritzbox_login();
    if ($SID <> "Fehler: Login fehlgeschlagen") {
        $string = file_get_contents("http://".$fritzbox_address."/myfritz/areas/homeauto.lua?sid=". $SID. "&startpos=0&cmd=getData&ajax_id=0");
        $json = json_decode($string);
        $devices=$json_status->devices;
    }
    return($devices);
}



function Fritzbox_DECT200_Device($deviceid) {
    global $xml;
    $fritzbox_address = $xml->fritzbox->address;
    $device="";
    $SID=Fritzbox_login();
    if ($SID <> "Fehler: Login fehlgeschlagen") {
        $string = file_get_contents("http://".$fritzbox_address."/myfritz/areas/homeauto.lua?sid=". $SID. "&startpos=0&cmd=getData&ajax_id=0");
        $json = json_decode($string);
        $devices=$json->devices;
        for ($x=0, $c=count($devices); $x<$c; $x++) {        
            if($devices[$x]->ID == $deviceid) {
                $device=$devices[$x];
            }
        }
    }
    return($device);
}



function Fritzbox_DECT200_DeviceStatus($deviceid) {
    $value_status=-1;
    $deviceafter=Fritzbox_DECT200_Device($deviceid);
    if($deviceafter) {
        $value_status=$deviceafter->switch->SwitchOn;
    }
    return($value_status);
}



function Fritzbox_DECT200_Switch($deviceid, $wert) {
    global $xml;
    $fritzbox_address = $xml->fritzbox->address;
    $SID=Fritzbox_login();
    $Value="-1";
    $ValueToSet="0";
    debug("Fritzbox_DECT200: ".$SID);
    if($SID <> "Fehler: Login fehlgeschlagen") {
        $ch = curl_init('http://'.$fritzbox_address.'/net/home_auto_query.lua');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "sid={$SID}&command=SwitchOnOff&value_to_set=". $wert. "&id=". $deviceid. "&xhr=1");
        $Result=curl_exec($ch);
        curl_close($ch);
        $json_switch = json_decode($Result);
        // evtl nicht nötig da in antwort vom schalten auch der wert steht!?
        // Abfrage, ob Schaltbefehl tatsächlich ausgeführt wurde
        $Value=Fritzbox_DECT200_DeviceStatus($deviceid);
    } else {
        $Value=$SID;
    }
    return($Value);
}



function Fritzbox_DECT200_Energie($deviceid, $Zeit) {
    global $xml;
    $fritzbox_address = $xml->fritzbox->address;
    $Daten="";
    $SID=Fritzbox_login();
    if ($SID <> "Fehler: Login fehlgeschlagen") {
        switch($Zeit) {
            case 1:      // Abfrage der Messwerte der letzten 10 min
                $Daten= file("http://".$fritzbox_address."/net/home_auto_query.lua?sid=". $SID. "&command=EnergyStats_10&id=". $deviceid. "&xhr=1");
                break;
            case 2:      // Abfrage der Messwerte der letzten 24h
                $Daten= file("http://".$fritzbox_address."/net/home_auto_query.lua?sid=". $SID. "&command=EnergyStats_24h&id=". $deviceid. "&xhr=1");
                break;
        }
        if($Daten <>"") {
            $Daten=explode('" , "', $Daten[1]);
            $x=count($Daten)-1;
            $temp=explode('" ,"', $Daten[$x]);
            foreach ($temp as $tem) {
                $Daten[$x]=$tem;
                $x++;
            }
        } else {
            $Daten[0]="Keine Werte vorhanden";
        }
        return ($Daten);
    }
}
/*
        Ergebnis bei EnergyStats_10:

        Array[0] = Anzahl der Leistungsmessungen (hier 60)
        Array[1] = Timer der Leistungsmessung (hier 10= alle 10s)
        Array[2]-Array[61] = Messwerte
        Array[62] = minimaler Messwert in Zeitspanne (60x10s = 10 min)
        Array[63] = maximaler Messwert in Zeitspanne (60x10s = 10 min)
        Array[64] = Durchschnitts Messwert in Zeitspanne
        Array[65] = Anzahl der Spannungsmessungen
        Array[66] = Timer der Spannungsmessung (hier 10 = alle 10s)
        Array[67]-Array[126] = Messwerte
        Array[127] = Status (an / aus)
        Array[128] = ID
        Array[129] = Verbinddungsstatus (hier 2= OK)
        Array [130]  RequestResult ????


        Ergebninis bei EnergyStats_24h:

        Array[0] = Anzahl der Leistungsmessungen (hier 96 = alle 15 min = 4*15*24)
        Array[1] = Timer der Leistungsmessung (hier 900= alle 900s = 15min)
        Array[2]-Array[97] = Messwerte
        Array[98] = minimaler Messwert in Zeitspanne (60x10s = 10 min)
        Array[99] = maximaler Messwert in Zeitspanne (60x10s = 10 min)
        Array[100 = Durchschnitts Messwert in Zeitspanne
        Array[101] = Status (an / aus)
        Array[102] = ID
        Array[103] = Verbinddungsstatus (hier 2= OK)
        Arry [104]  RequestResult ????
*/



function Fritzbox_login() {
    global $xml;
    $fritzbox_address = $xml->fritzbox->address;
    $fritzbox_username = $xml->fritzbox->username;
    $fritzbox_password = $xml->fritzbox->password;
    $ch = curl_init('http://'.$fritzbox_address.'/login_sid.lua');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $login = curl_exec($ch);
    $session_status_simplexml = simplexml_load_string($login);
    if ($session_status_simplexml->SID != '0000000000000000') {
        $SID = $session_status_simplexml->SID;
    } else {
        $challenge = $session_status_simplexml->Challenge;
        $response = $challenge . '-' . md5(mb_convert_encoding($challenge . '-' . $fritzbox_password, "UCS-2LE", "UTF-8"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "response={$response}&page=/login_sid.lua&username={$fritzbox_username}");
        $sendlogin = curl_exec($ch);
        $session_status_simplexml = simplexml_load_string($sendlogin);

        if ($session_status_simplexml->SID != '0000000000000000') {
            $SID = $session_status_simplexml->SID;
        } else {
            $SID= "Fehler: Login fehlgeschlagen";
        }
    }
    curl_close($ch);
    return $SID;
}



/* 
{
"shares": {
}, 
"shares_url": "", 
"ajax_id": 0, 
"devices": [{
    "pv_max": 133.46, 
    "FunctionBitMask": 640, 
    "DeviceType": 9, 
    "GroupHash": "0", 
    "ID": 16, 
    "SubModel": "0x0002", 
    "Valid": 2, 
    "Identifyer": "08761 0105914", 
    "ProductName": "FRITZ!DECT 200", 
    "pv_now": 119.37, 
    "UpdatePresent": 0, 
    "Name": "Server", 
    "Model": "0x0007", 
    "pv_min": 114.72, 
    "switch": {
      "LEDState": 2, 
      "Options": 2, 
      "Devicetype": 9, 
      "ID": 16, 
      "SwitchOn": 1, 
      "SwitchLock": 0
    }, 
    "FWVersion": "03.20", 
    "Manufacturer": "AVM"
  }, {
    "pv_max": 303.84, 
    "FunctionBitMask": 640, 
    "DeviceType": 9, 
    "GroupHash": "0", 
    "ID": 17, 
    "SubModel": "0x0002", 
    "Valid": 2, 
    "Identifyer": "08761 0105901", 
    "ProductName": "FRITZ!DECT 200", 
    "pv_now": 301.55, 
    "UpdatePresent": 0, 
    "Name": "Pool", 
    "Model": "0x0007", 
    "pv_min": 301.55, 
    "switch": {
      "LEDState": 2, 
      "Options": 2, 
      "Devicetype": 9, 
      "ID": 17, 
      "SwitchOn": 1, 
      "SwitchLock": 0
    }, 
    "FWVersion": "03.20", 
    "Manufacturer": "AVM"
  }], 
"area": "homeautoArea"
}
*/
?> 
