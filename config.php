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

?>
