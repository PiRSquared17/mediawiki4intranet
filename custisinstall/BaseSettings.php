<?php
if( defined( 'MW_INSTALL_PATH' ) ) {
	$IP = MW_INSTALL_PATH;
} else {
	$IP = realpath(dirname( __FILE__ ) . "/..");
}

$path = array( $IP, "$IP/includes", "$IP/includes/specials","$IP/languages" );
set_include_path( implode( PATH_SEPARATOR, $path ) . PATH_SEPARATOR . get_include_path() );

require_once($IP . '/includes/DefaultSettings.php');
$wgSitename         = "CustisWiki";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
$wgScriptPath       = "/wiki12";
$wgScriptExtension  = ".php";

$wgEnableEmail      = false;
$wgEnableUserEmail  = false;

$wgDBtype           = "mysql";
$wgDBserver         = "localhost";

$wgDBname           = "wiki12";
$wgDBuser           = "wiki";
$wgDBpassword       = "wiki";

$wgDBprefix         = "";

$wgDBTableOptions   = "TYPE=MyISAM";
$wgDBmysql5         = true;

$wgEnableUploads    = true;

$wgLocalInterwiki   = $wgSitename;
$wgDefaultSkin      = 'custis';

$wgRightsPage = ""; # 
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";
$wgRightsCode = ""; # 

$wgDiff3 = "diff3";
$wgImageMagickConvertCommand = "convert";


# When you make changes to this configuration file, this will make
# sure that cached pages are cleared.
$wgCacheEpoch = max( $wgCacheEpoch, gmdate( 'YmdHis', @filemtime( __FILE__ ) ) );
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = array();

$wgInterwikiMagic = false;

$wgRawHtml              = true;
$wgAllowUserJs          = true;
$wgNamespacesWithSubpages[NS_MAIN] = true;
$wgNamespacesWithSubpages[NS_USER] = true;
$wgNamespacesWithSubpages[NS_TALK] = true;
$wgNamespacesWithSubpages[NS_USER_TALK] = true;
$wgUseAjax = true;



$wgLogo                 = "$wgScriptPath/skins/custis/cis-logo.png";
$wgFileExtensions       = array( 'mm', 'png', 'gif', 'jpg', 'jpeg', 'doc', 'xpi', 'zip', 'rar', 'ppt', 'pps', 'xls','vsd', 'djvu', 'svg');
$wgAllowCopyUploads     = true;
$wgStrictFileExtensions = false;

array_push($wgUrlProtocols,"file://");
$wgLanguageCode = "ru";

$wgSMTP = false;
$wgShowExceptionDetails = true;


require_once($IP.'/extensions/ParserFunctions/ParserFunctions.php' );
define( 'MW_PARSER_VERSION', '1.6.1' ); 
require_once($IP.'/extensions/StringFunctions/StringFunctions.php' );
require_once($IP.'/extensions/CharInsert/CharInsert.php' );
require_once($IP.'/extensions/Cite/Cite.php');
require_once($IP.'/extensions/SyntaxHighlight_GeSHi/SyntaxHighlight_GeSHi.php');
require_once($IP.'/extensions/CategoryTree/CategoryTree.php' );


$wgGroupPermissions['*']['interwiki'] = false;
$wgGroupPermissions['sysop']['interwiki'] = true;

require_once($IP.'/extensions/Interwiki/SpecialInterwiki.php');
require_once($IP.'/extensions/WikiCategoryTagCloud/WikiCategoryTagCloud.php'); 

require_once($IP.'/extensions/DocExport/DocExport.php');
require_once($IP.'/extensions/CustisScripts/CustisScripts.php');
require_once($IP.'/extensions/BatchEditor/BatchEditor.php');
require_once($IP.'/extensions/MarkupBabel/MarkupBabel.php');
require_once($IP.'/extensions/FreeMind/FreeMind.php');
require_once($IP.'/extensions/AnyWikiDraw/AnyWikiDraw.php');

require_once($IP. '/extensions/Calendar/Calendar.php');
require_once($IP. '/extensions/SimpleTable/SimpleTable.php');


$wgSVGConverter="inkscape";

$wgUseImageMagick   = true;

require_once($IP . '/includes/GlobalFunctions.php');
if (wfIsWindows()){
  $wgSVGConverterPath=realpath($IP."/../../app/inkscape/");
  $wgImageMagickConvertCommand=realpath($IP."/../../app/imagemagick")."/convert.exe";
  $wgUseImageMagick=false;
}

$wgLogo             = "$wgScriptPath/custisinstall/logos/custiswiki-logo.png";
$wgFavicon			    = "$wgScriptPath/custisinstall/favicons/custiswiki.ico";

$wgDebugLogFile=false;
$wgDefaultSkin = 'monobook';

$wgGroupPermissions['*']['edit'] = false;

$wgSphinxTopSearchableCategory="Root";

// Bug (Bug 43343), because detect VSD-files as application/msword, 
// or incorrect define of .mm files 
$wgVerifyMimeType = false;
?>
