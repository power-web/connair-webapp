<?php

$directaccess = true;

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

require("config.php");

/*
  <timers debug="false">
    <timer>
      <active>on</active>
      <id>1</id>
      <type>device</type>
      <typeid>1</typeid>
      <day>MDMDF__</day>
      <timerOn>05:30</timerOn>
      <timerOff>09:00</timerOff>
    </timer>
    
    
response:Array
(
    [active] => off
    [timertype] => device
    [typeiddevice] => 8
    [typeidgroup] => 4
    [typeidroom] => Badezimmer
    [timerday] => Array
        (
            [0] => 0
            [1] => 1
            [2] => 2
            [3] => 3
        )
    [OnTimerType] => M
    [OnTimerHH] => Stunden
    [OnTimerMM] => Minuten
    [timeronoffset] => 0
    [OffTimerType] => M
    [OffTimerHH] => Stunden
    [OffTimerMM] => Minuten
    [timeroffoffset] => 0
)
print_r($_POST);
exit;
*/
$r_action = (string)$_POST['action'];
$r_id = (string)$_POST['id'];
$r_active = (string)$_POST['active'];
$r_type = (string)$_POST['timertype'];
switch($_POST['timertype']) {
    case "device":
        $typeid = (string)$_POST['typeiddevice'];
        break;
        
    case "group":
        $typeid = (string)$_POST['typeidgroup'];
        break;
        
    case "room":
        $typeid = (string)$_POST['typeidroom'];
        break;
        
    default:
        echo "Ungültiger Typer Typ!";
        exit;
}
$day = '_______';
foreach ($_POST['timerday'] as $keyday) {
    switch($keyday) {
        case 0:
            $day[$keyday]='M';
            break;
        case 1:
            $day[$keyday]='D';
            break;
        case 2:
            $day[$keyday]='M';
            break;
        case 3:
            $day[$keyday]='D';
            break;
        case 4:
            $day[$keyday]='F';
            break;
        case 5:
            $day[$keyday]='S';
            break;
        case 6:
            $day[$keyday]='S';
            break;
    }
}
switch($_POST['OnTimerType']) {
    case "A":
        $onHH=$_POST['OnTimerHH'];
        if($onHH<0 && $onHH>23) {
            echo "Falsche Stunden";
            exit;
        }
        $onMM=$_POST['OnTimerMM'];
        if($onHH<0 && $onHH>59) {
            echo "Falsche Minuten";
            exit;
        }
        $timerOn = $onHH.':'.$onMM;
        break;
        
    case "SU":
    case "SD":
        $timerOn = (string)$_POST['OnTimerType'];
        break;
        
    default:
    case "M":
        $timerOn = "";
        break;   
}
$r_timeronoffset=intval($_POST['timeronoffset']);
switch($_POST['OffTimerType']) {
    case "A":
        $offHH=$_POST['OffTimerHH'];
        if($offHH<0 && $offHH>23) {
            echo "Falsche Stunden";
            exit;
        }
        $offMM=$_POST['OffTimerMM'];
        if($offHH<0 && $offHH>59) {
            echo "Falsche Minuten";
            exit;
        }
        $timerOff = $offHH.':'.$offMM;
        break;
        
    case "SU":
    case "SD":
        $timerOff = (string)$_POST['OffTimerType'];
        break;
        
    default:
    case "M":
        $timerOff = "";
        break;   
}
$r_timeroffoffset=intval($_POST['timeroffoffset']);

switch ($r_action) {

    case "add":
        $newid=1;
        foreach($xml->timers->timer as $timer) {
            $oldid=(integer)$timer->id;
            if($oldid >= $newid) {
                $newid = $oldid + 1;
            }
        }
        $newtimer = $xml->timers->addChild('timer');
        $newtimer->addChild('id', $newid);
        $newtimer->addChild('active', $r_active);
        $newtimer->addChild('type', $r_type);
        $newtimer->addChild('typeid', $typeid);
        $newtimer->addChild('day', $day);
        $timerOnXml=$newtimer->addChild('timerOn', $timerOn);
        if(!empty($r_timeronoffset)) {
            $timerOnXml->addAttribute('offset', $r_timeronoffset);
        }
        $timerOffXml=$newtimer->addChild('timerOff', $timerOff);
        if(!empty($r_timeroffoffset)) {
            $timerOffXml->addAttribute('offset', $r_timeroffoffset);
        }
    
        if(check_timer($newtimer)) {
            echo "ok";
            config_save();
        }
    
        break;
    
    case "edit":
      //  break;
    
    case "delete":
      //  break;    
    
    default:
        echo "unsupported";
        break;
}


?>

