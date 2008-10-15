<?php

require_once($IP.'/custisinstall/BaseSettings.php');
require_once($IP.'/extensions/WhoIsWatching/SpecialWhoIsWatching.php');
$wgPageShowWatchingUsers = true;

$wgEnableEmail      = true;
$wgEnableUserEmail  = true;
$wgEnotifUserTalk = true; # UPO
$wgEnotifWatchlist = true; # UPO
$wgEmailAuthentication = true;
$wgEnotifMinorEdits =true; 

$wgEmergencyContact = "stas@custis.ru";
$wgPasswordSender = "stas@custis.ru";


?>
