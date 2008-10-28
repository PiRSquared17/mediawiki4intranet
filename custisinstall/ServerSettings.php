<?php

require_once($IP.'/custisinstall/BaseSettings.php');
require_once($IP.'/extensions/WhoIsWatching/SpecialWhoIsWatching.php');
$wgPageShowWatchingUsers = true;

require_once($IP.'/extensions/Polls/poll.php');

$wgEnableEmail      = true;
$wgEnableUserEmail     = true;
$wgEnotifUserTalk      = true; # UPO
$wgEnotifWatchlist     = true; # UPO
$wgEmailAuthentication = true;
$wgEnotifMinorEdits    = true; 

$wgEmergencyContact = "stas@custis.ru";
$wgPasswordSender   = "stas@custis.ru";

// Bug (Bug 43343), because detect VSD-files as application/msword
$wgVerifyMimeType = false;

$wgAllowExternalImages     = true;
$wgAllowExternalImagesFrom = array( 'http://penguin.office.custis.ru/',
                                    'http://svn.office.custis.ru/',
                                    'http://plantime.office.custis.ru/');


$wgSMTP = array("host" => 'localhost',
           "IDHost" => 'custis.ru',
           "port" => "25",
           "auth" => false);

?>
