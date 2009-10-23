<?php

require_once('BaseSettings.php');
require_once($IP.'/extensions/WhoIsWatching/SpecialWhoIsWatching.php');

$wgPageShowWatchingUsers = true;

require_once($IP.'/extensions/PreferencesExtension/PreferencesExtension.php');
require_once($IP.'/extensions/EnotifDiff/EnotifDiff.php');
require_once($IP.'/extensions/AnyWikiDraw/AnyWikiDraw.php');
require_once($IP.'/extensions/Polls/poll.php');
require_once($IP.'/extensions/mediawikiquizzer/mediawikiquizzer.php');
require_once($IP.'/extensions/CategoryTemplate/CategoryTemplate.php');
require_once($IP.'/extensions/Drafts/Drafts.php');
require_once($IP.'/extensions/SVNIntegration/SVNIntegration.setup.php');
require_once($IP.'/extensions/Wikilog/Wikilog.php');
require_once($IP.'/extensions/DeleteBatch/DeleteBatch.php');
require_once($IP.'/extensions/FullLocalImage.php');
#require_once($IP.'/extensions/LiquidThreads/LqtPages.php');
Wikilog::setupNamespace(100, 'Блог', 'Обсуждение_блога');

$egDraftsAutoSaveWait  = 60;   // 1 minute

# Extension:FlvHandler
$wgFlashPlayer = 'extensions/FlvHandler/flowplayer/flowplayer-3.1.3.swf';
$wgFileExtensions[] = 'flv';
require_once($IP.'/extensions/FlvHandler/FlvHandler.php');

$wgAllowCategorizedRecentChanges = true;

$egMarkupBabelMaxImageInch = 6.3;

$wgEnableEmail         = true;
$wgEnableUserEmail     = true;
$wgEnotifUserTalk      = true; // UPO
$wgEnotifWatchlist     = true; // UPO
$wgEmailAuthentication = true;
$wgEnotifMinorEdits    = true;
$wgCookieHttpOnly      = false;
$wgCookieExpiration    = 3000 * 86400;

$wgEnableMWSuggest     = true;
$wgOpenSearchTemplate  = true;

$wgEmergencyContact    = "stas@custis.ru";
$wgPasswordSender      = "stas@custis.ru";

$wgAllowExternalImages     = true;
$wgAllowExternalImagesFrom = array(
    'http://penguin.office.custis.ru/',
    'http://svn.office.custis.ru/',
    'http://plantime.office.custis.ru/'
);

$SVNIntegrationDefaultSettings['svnParams'] = array('config-dir' => '/var/www/.subversion');

$wgSMTP = array(
    "host"   => 'localhost',
    "IDHost" => 'custis.ru',
    "port"   => "25",
    "auth"   => false,
);

$wgGroupPermissions['*']['delete'] = true;
$wgGroupPermissions['*']['undelete'] = true;
$wgGroupPermissions['sysop']['deletebatch'] = true;

?>
