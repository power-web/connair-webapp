<?php

header("Content-Type: application/json; charset=utf-8");

$broadcast_string="SEARCH HCGW";
$port = 49880;
$timeout = array('sec'=>1,'usec'=>500000);

if(!($socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))) {
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
     
    die('{"ERROR":{"ERRORCODE":'.json_encode($errorcode).',"ERRORMSG":'.json_encode($errormsg).'}}');
}

socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1); 
socket_sendto($socket, $broadcast_string, strlen($broadcast_string), 0, '255.255.255.255', $port);

socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,$timeout);

$from_recv = '';
$port_recv = 0;
$hcgw = array();

while($len = @socket_recvfrom($socket, $buf, 255, 0, $from_recv, $port_recv)) {
    if("HCGW" == substr($buf, 0 , 4)) {
        $dataString = substr($buf, 5);
        $data = array();
        foreach (explode(";", $dataString) as $keyValuePair) {
            if(!$keyValuePair) continue;
            list ($key, $value) = explode(':', $keyValuePair, 2);
            $data[$key] = $value;
        }
        $hcgw[$data['IP']] = $data;
    }
}

echo json_encode($hcgw);

socket_close($socket); 

?>

