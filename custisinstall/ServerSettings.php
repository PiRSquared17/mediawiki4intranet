<?php

require_once('BaseSettings.php');
require_once($IP.'/extensions/WhoIsWatching/SpecialWhoIsWatching.php');

$wgPageShowWatchingUsers = true;

require_once($IP.'/extensions/PreferencesExtension/PreferencesExtension.php');
require_once($IP.'/extensions/EnotifDiff/EnotifDiff.php');
require_once($IP.'/extensions/AnyWikiDraw/AnyWikiDraw.php');
require_once($IP.'/extensions/Polls/poll.php');
require_once($IP.'/extensions/mediawikiquizzer/mediawikiquizzer.php');
require_once($IP.'/extensions/Drafts/Drafts.php');

# Wikilog
require_once($IP.'/extensions/Wikilog/Wikilog.php');
define('NS_BLOG', 100);
Wikilog::setupNamespace(NS_BLOG, 'Блог', 'Обсуждение_блога');
$wgNamespacesToBeSearchedDefault[NS_BLOG] = 1;
$wgWikilogMaxCommentSize = 0x7FFFFFFF;
$wgWikilogDefaultNotCategory = 'Скрытые';

$egDraftsAutoSaveWait = 60;   // 1 minute

# Extension:FlvHandler
$wgFlowPlayer = 'extensions/FlvHandler/flowplayer/flowplayer-3.1.3.swf';
$wgFileExtensions[] = 'flv';
require_once($IP.'/extensions/FlvHandler/FlvHandler.php');

$wgEnableEmail         = true;
$wgEnableUserEmail     = true;
$wgEnotifUserTalk      = true; // UPO
$wgEnotifWatchlist     = true; // UPO
$wgEmailAuthentication = true;
$wgEnotifMinorEdits    = true;
$wgCookieHttpOnly      = false;

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

// Don't purge recent changes... (keep them for 50 years)
$wgRCMaxAge = 50 * 365 * 86400;

$wgGroupPermissions['*']['delete'] = true;
$wgGroupPermissions['*']['undelete'] = true;
$wgGroupPermissions['sysop']['deletebatch'] = true;

$wgSphinxSearch_weights = array('page_title' => 2, 'old_text' => 1);
$wgSphinxSearch_matches = 20;
$wgSphinxMatchAll = 1;

// Bug 57350 - PDF and Djvu (UNIX only)
require_once($IP.'/extensions/PdfHandler/PdfHandler.php');

$wgDjvuDump = "djvudump";
$wgDjvuRenderer = "ddjvu";
$wgDjvuTxt = "djvutxt";
$wgDjvuPostProcessor = "ppmtojpeg";
$wgDjvuOutputExtension = 'jpg';

$wgPdfProcessor = 'gs';
$wgPdfPostProcessor = $wgImageMagickConvertCommand;
$wgPdfInfo = 'pdfinfo';

?>
