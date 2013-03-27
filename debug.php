<?php

if((!isset($directaccess)) OR (!$directaccess)) die();

$DEBUG_FILENAME="debug.log";

//debug.log dateisystem rechte überprüfen
if($debug == "true" && !is_writable($DEBUG_FILENAME)) {
    echo "Kann die Log (".$DEBUG_FILENAME.") nicht schreiben!\n";
    exit(5);
}

//funktion um in das debug log zu schreiben
function debug($msg) {
    global $debug;
    global $DEBUG_FILENAME;
    if($debug == "true") {
        $handle = fopen ($DEBUG_FILENAME, 'a');
        fwrite($handle, date("Y-m-d H:i:s")." ".$_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_TIME']."   ".$msg."\r\n");
        fclose($handle);
    }
}

?>
