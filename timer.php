<?php

if((!isset($directaccess)) OR (!$directaccess)) die();

$debug_timer=empty($xml->timers["debug"]) ? "false" : $xml->timers["debug"];
header("Content-Type: text/plain; charset=utf-8");

function debug_timer($msg) {
    global $debug_timer;
    echo $msg."\n";
    debug($msg);
}

function timer_check() {
    global $xml;
    global $debug_timer;
    if($debug_timer=="true") debug_timer("Timer Checking...");
    if(@count($xml->timers->children()) > 0 ) {
        // Sonnenauf- und -untergangskonfiguration (sunrise = Sonnenaufgang = SU (Sun Up) // sunset = Sonnenuntergang = SD (Sun Down)
        $latitude=empty($xml->global->latitude) ? 48.64727 : ($xml->global->latitude)*1;
        $longitude=empty($xml->global->longitude) ? 9.44858 : ($xml->global->longitude)*1;
        $sunrise = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+5/6, date("O")/100);
        $sunset = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+5/6, date("O")/100);
        if($debug_timer=="true") debug_timer("latitude: ".$latitude."  longitude: ".$longitude."  sunrise: ".$sunrise."  sunset: ".$sunset);
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
            if($debug_timer=="true") debug_timer("Timer: \n".$timer->asXML());
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
                if($debug_timer=="true") debug_timer("Timer Tag stimmt (ON) ".$timer->id);
                if($debug_timer=="true") debug_timer("++++TimerID:".$timer->id." OnTimer ".date('H:i', $OnTimer)." Von ".date('H:i', $timeWindowStart)." - ".date('H:i', $timeWindowStop));
                // Tag gültig -> Prüfen, ob On Timer innerhalb des Zeitfensters liegt
                if (($OnTimer >= $timeWindowStart) && ($OnTimer <= $timeWindowStop)) {
                    // Timer liegt innerhalb des Zeitfensters -> Schaltungen durchführen
                    debug_timer("Timer in action (ON) ".$timer->id);
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
                if($debug_timer=="true") debug_timer("Timer Tag stimmt (OFF) ".$timer->id);
                if($debug_timer=="true") debug_timer("TimerID:".$timer->id." OffTimer ".date('H:i', $OffTimer)." Von ".date('H:i', $timeWindowStart)." - ".date('H:i', $timeWindowStop));
                // Tag gültig -> Prüfen, ob On Timer innerhalb des Zeitfensters liegt
                if (($OffTimer >= $timeWindowStart) && ($OffTimer <= $timeWindowStop)) {
                    // Timer liegt innerhalb des Zeitfensters -> Schaltungen durchführen
                    debug_timer("Timer in action (OFF) ".$timer->id);
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





?>
