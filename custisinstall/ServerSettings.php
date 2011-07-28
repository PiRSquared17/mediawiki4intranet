<?php

// MediaWiki4Intranet configuration base for internal UNIX installations
// (c) Stas Fomin, Vitaliy Filippov 2008-2011

require_once('BaseSettings.php');

$wgPageShowWatchingUsers = true;

require_once($IP.'/extensions/EnotifDiff/EnotifDiff.php');

$wgEnableEmail         = true;
$wgEnableUserEmail     = true;
$wgEnotifUserTalk      = true; // UPO
$wgEnotifWatchlist     = true; // UPO
$wgEmailAuthentication = true;
$wgEnotifMinorEdits    = true;

$wgEmergencyContact    = "stas@custis.ru";
$wgPasswordSender      = "wiki-daemon@custis.ru";

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

$wgSphinxSearch_host = 'localhost';
$wgSphinxSearch_port = 3112;

// Bug 57350 - PDF and Djvu (UNIX only)
require_once($IP.'/extensions/PdfHandler/PdfHandler.php');

$wgDjvuDump = "djvudump";
$wgDjvuRenderer = "ddjvu";
$wgDjvuTxt = "djvutxt";
$wgDjvuPostProcessor = "ppmtojpeg";
$wgDjvuOutputExtension = 'jpg';

$wgPdfProcessor = 'nice -n 20 gs';
$wgPdfPostProcessor = $wgImageMagickConvertCommand;
$wgPdfInfo = 'pdfinfo';

$wgDiff3 = '/usr/bin/diff3';

// Bug 82496 - enable scary (cross-wiki) transclusions
$wgEnableScaryTranscluding = true;
