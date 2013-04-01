<?php

if((!isset($directaccess)) OR (!$directaccess)) die();

$CONFIG_FILENAME="config.xml";

//config.xml dateisystem rechte überprüfen
if(!file_exists($CONFIG_FILENAME)) {
    echo "Kann die Konfiguration (".$CONFIG_FILENAME.") nicht finden!\n";
    exit(1);
}
if(!is_readable($CONFIG_FILENAME)) {
    echo "Kann die Konfiguration (".$CONFIG_FILENAME.") nicht lesen!\n";
    exit(2);
}
if(!is_writable($CONFIG_FILENAME)) {
    echo "Kann die Konfiguration (".$CONFIG_FILENAME.") nicht schreiben!\n";
    exit(3);
}

//config.xml einlesen
libxml_use_internal_errors(true);
$xml = simplexml_load_file($CONFIG_FILENAME);
if (!$xml) {
    echo "Kann die Konfiguration (".$CONFIG_FILENAME.") nicht laden!\n";
    foreach(libxml_get_errors() as $error) {
        echo "\t", $error->message;
    }
    exit(4);
}

//globale variabeln
$debug=empty($xml["debug"]) ? "false" : $xml["debug"];

// Suppress DateTime warnings
date_default_timezone_set(@date_default_timezone_get());

//zeitzone geradeziehen
if(!empty($xml->global->timezone)) {
    date_default_timezone_set($xml->global->timezone);
}

$latitude=(float)$xml->global->latitude;
if(empty($latitude)) {
    $latitude=ini_get("date.default_latitude");
    if(empty($latitude)) {
        $latitude=(float)48.64727;
    }
}
$longitude=(float)$xml->global->longitude;
if(empty($longitude)) {
    $longitude=ini_get("date.default_longitude");
    if(empty($longitude)) {
        $longitude=(float)9.44858;
    }
}

// Sonnenauf- und -untergang für den Timer
$sunrise = date_sunrise(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+5/6, date("O")/100);
$sunset = date_sunset(time(), SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, 90+5/6, date("O")/100);


if(empty($xml->global->multiDeviceSleep) || $xml->global->multiDeviceSleep<200) {
    if(!isset($xml->global->multiDeviceSleep)) {
        $xml->global->addChild('multiDeviceSleep',500);
    } else {
        $xml->global->multiDeviceSleep=500;
    }
    $multiDeviceSleep = 500000;
} else {
    $multiDeviceSleep = intval($xml->global->multiDeviceSleep)*1000;
}


switch ($xml->gui->theme) {
    case "DARK":
        $theme_page = "a";
        $theme_divider = "a";
        $theme_row = "a";
        break;
    default:
        if(!isset($xml->gui->theme)) {
            $xml->gui->addChild('theme',"LIGHT");
        } else {
            $xml->gui->theme="LIGHT";
        }
    case "LIGHT":
        $theme_divider = "c";
        $theme_divider = "e";
        $theme_row = "c";
        break;
}



function config_save() {
    global $xml;
    global $CONFIG_FILENAME;
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    $dom->save($CONFIG_FILENAME);
}

function check_config_global() {
    return true;
}

function check_device($device) {
    if(empty($device->id)) {
        echo "Device-ID darf nicht leer sein!";
        return false;
    }
    if(empty($device->name)) {
        echo "Device-Name darf nicht leer sein!";
        return false;
    }
    if(empty($device->room)) {
        echo "Device-Room darf nicht leer sein!";
        return false;
    }
    if(empty($device->vendor)) {
        echo "Device-Vendor darf nicht leer sein!";
        return false;
    }
    $vendor=strtolower($device->vendor);
    $masterdip=$device->address->masterdip;
    $slavedip=$device->address->slavedip;
    switch($vendor) {
        case "intertechno":
            if(empty($masterdip)) {
                echo "Device-masterdip darf nicht leer sein!";
                return false;
            }        
            if(empty($slavedip)) {
                echo "Device-slavedip darf nicht leer sein!";
                return false;
            }        
            if((strlen($masterdip)!=1) || !(preg_match('/^[A-P]+$/',$masterdip))) {
                echo "Device-masterdip muss ein Buchstabe von A bis P sein!";
                return false;
            }        
            if(!preg_match('/^[0-9]+$/',$slavedip) || ($slavedip<1) || ($slavedip>16)) {
                echo "Device-slavedip darf nur eine Zahl zwischen 1 und 16 sein!";
                return false;
            }        
            break;
        case "raw":
            break;
        case "brennenstuhl":
        case "elro":
        default:
            if(empty($masterdip)) {
                echo "Device-masterdip darf nicht leer sein!";
                return false;
            }        
            if(empty($slavedip)) {
                echo "Device-slavedip darf nicht leer sein!";
                return false;
            }        
            if(!preg_match('/^[0-1]+$/',$masterdip) || (strlen($masterdip)!=5)) {
                echo "Device-masterdip darf nur aus 1 und 0 bestehen und muss 5 Stellen haben!";
                return false;
            }        
            if(!preg_match('/^[0-1]+$/',$slavedip) || (strlen($slavedip)!=5)) {
                echo "Device-slavedip darf nur aus 1 und 0 bestehen und muss 5 Stellen haben!";
                return false;
            }        
            break;
    }
    return true;
}

function check_timer($timer) {
    return true;
}

?>
