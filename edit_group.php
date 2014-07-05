<?php

$directaccess = true;

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

require("config.php");

$r_action = (string)$_POST['action'];
$r_id = (string)$_POST['id'];

switch ($r_action) {

    case "add":
        break;
    
    case "edit":
        break;
    
    case "delete":
        $xpath='//group/id[.="'.$r_id.'"]/parent::*';
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

