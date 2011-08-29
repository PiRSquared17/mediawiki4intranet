<?php

// MediaWiki4Intranet configuration base for all MW installations (UNIX, Windows)
// (c) Stas Fomin, Vitaliy Filippov 2008-2011

setlocale(LC_ALL, 'ru_RU.UTF-8');
setlocale(LC_NUMERIC, 'C');

if (defined('MW_INSTALL_PATH'))
    $IP = MW_INSTALL_PATH;
else
{
    foreach (debug_backtrace() as $frame)
        if (strtolower(substr($frame['file'], -strlen('LocalSettings.php'))) == 'localsettings.php')
            $IP = realpath(dirname($frame['file']));
    if (!$IP)
        $IP = realpath(dirname(__FILE__) . '/..');
}

$path = array($IP, "$IP/includes", "$IP/includes/specials","$IP/languages");
set_include_path(implode(PATH_SEPARATOR, $path) . PATH_SEPARATOR . get_include_path());

require_once($IP . '/includes/DefaultSettings.php');
$wgSitename         = "CustisWiki";

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
$wgScriptPath       = "/wiki";
$wgScriptExtension  = ".php";

$wgEnableEmail      = false;
$wgEnableUserEmail  = false;

$wgDBtype           = "mysql";
$wgDBserver         = "localhost";

$wgDBname           = "wiki";
$wgDBuser           = "wiki";
$wgDBpassword       = "wiki";
$wgDBadminuser      = "wiki";
$wgDBadminpassword  = "wiki";

$wgDBprefix         = "";

$wgDBTableOptions   = "ENGINE=InnoDB, DEFAULT CHARSET=utf8";
$wgDBmysql5         = true;

$wgEnableUploads    = true;

$wgLocalInterwiki   = $wgSitename;
$wgLocaltimezone    = "Europe/Moscow";

$wgRightsPage = "";
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";
$wgRightsCode = "";

$wgDiff3 = "diff3";
$wgImageMagickConvertCommand = "convert";

# When you make changes to this configuration file, this will make
# sure that cached pages are cleared.
$wgCacheEpoch = max( $wgCacheEpoch, gmdate( 'YmdHis', @filemtime( __FILE__ ) ) );
$wgMainCacheType = CACHE_ACCEL;
$wgMemCachedServers = array();

$wgRawHtml              = true;
$wgAllowUserJs          = true;
$wgNamespacesWithSubpages[NS_MAIN] = true;
$wgNamespacesWithSubpages[NS_USER] = true;
$wgNamespacesWithSubpages[NS_TALK] = true;
$wgNamespacesWithSubpages[NS_USER_TALK] = true;
$wgUseAjax = true;

$wgFileExtensions       = array(
    'png', 'gif', 'jpg', 'jpeg', 'svg',
    'zip', 'rar', '7z', 'gz', 'bz2', 'xpi',
    'doc', 'docx', 'ppt', 'pptx', 'pps', 'ppsx', 'xls', 'xlsx', 'vsd',
    'djvu', 'pdf', 'xml', 'mm'
);

$wgAllowCopyUploads     = true;
$wgStrictFileExtensions = false;

array_push($wgUrlProtocols,"file://");
$wgLanguageCode = "ru";

$wgSMTP = false;
$wgShowExceptionDetails = true;

require_once($IP.'/extensions/ParserFunctions/ParserFunctions.php');
$wgPFEnableStringFunctions = true;
require_once($IP.'/extensions/RegexParserFunctions/RegexParserFunctions.php');
require_once($IP.'/extensions/CharInsert/CharInsert.php');
require_once($IP.'/extensions/CharInsertList/CharInsertList.php');
require_once($IP.'/extensions/Cite/Cite.php');
require_once($IP.'/extensions/SyntaxHighlight_GeSHi/SyntaxHighlight_GeSHi.php');
require_once($IP.'/extensions/CategoryTree/CategoryTree.php');

$wgSubcategorizedAlwaysExclude = array('CustisWikiToLib',
    'CustisWikiToSMWiki', 'CustisWikiToSBWiki', 'CustisWikiToRDWiki',
    'CustisWikiToGZWiki', 'CustisWikiToHRWiki', 'CustisWikiToDPWiki',
    'CustisWikiToORWiki', 'CustisWikiToCBWiki');

$wgGroupPermissions['*']['interwiki'] = false;
$wgGroupPermissions['sysop']['interwiki'] = true;

require_once($IP.'/extensions/Interwiki/Interwiki.php');
require_once($IP.'/extensions/WikiCategoryTagCloud/WikiCategoryTagCloud.php');

require_once($IP.'/extensions/DocExport/DocExport.php');
require_once($IP.'/extensions/CustisScripts/CustisScripts.php');
require_once($IP.'/extensions/BatchEditor/BatchEditor.php');
require_once($IP.'/extensions/MarkupBabel/MarkupBabel.php');
require_once($IP.'/extensions/AnyWikiDraw/AnyWikiDraw.php');
require_once($IP.'/extensions/CategoryTemplate/CategoryTemplate.php');
require_once($IP.'/extensions/DeleteBatch/DeleteBatch.php');
require_once($IP.'/extensions/FullLocalImage.php');

require_once($IP.'/extensions/SVGEdit/SVGEdit.php');

$wgGroupPermissions['bureaucrat']['usermerge'] = true;
require_once($IP.'/extensions/UserMerge/UserMerge.php');
require_once($IP.'/extensions/Renameuser/Renameuser.php');

require_once($IP.'/extensions/MMHandler/MMHandler.php');
/* for mindmap uploads */
$wgForbiddenTagsInUploads = array('<object', '<param', '<embed', '<script');

require_once($IP.'/extensions/PagedTiffHandler/PagedTiffHandler.php');
unset($wgAutoloadClasses['PagedTiffHandlerSeleniumTestSuite']);

require_once($IP.'/extensions/Dia/Dia.php');

$wgAllowCategorizedRecentChanges = true;

require_once($IP.'/extensions/MergeConflicts/MergeConflicts.php');
require_once($IP.'/extensions/AllNsSuggest/AllNsSuggest.php');
require_once($IP.'/extensions/NewPagesEx/NewPagesEx.php');
require_once($IP.'/extensions/Calendar/Calendar.php');
require_once($IP.'/extensions/SimpleTable/SimpleTable.php');
require_once($IP.'/extensions/MagicNumberedHeadings/MagicNumberedHeadings.php');
require_once($IP.'/extensions/MediaFunctions/MediaFunctions.php');
require_once($IP.'/extensions/AllowGetParamsInWikilinks/AllowGetParamsInWikilinks.php');
require_once($IP.'/extensions/WikiBookmarks/WikiBookmarks.php');
require_once($IP.'/extensions/SWFUpload/SWFUpload.php');
require_once($IP.'/extensions/UserMagic/UserMagic.php');
require_once($IP.'/extensions/S5SlideShow/S5SlideShow.php');
require_once($IP.'/extensions/UserMessage/UserMessage.php');
require_once($IP.'/extensions/PlantUML/PlantUML.php');
require_once($IP.'/extensions/HttpAuth/HttpAuth.php');
require_once($IP.'/extensions/SimpleForms/SimpleForms.php'); /* useful at least for {{#request:...}} */
require_once($IP.'/extensions/WhoIsWatching/SpecialWhoIsWatching.php');
require_once($IP.'/extensions/Polls/poll.php');
require_once($IP.'/extensions/Shortcuts/Shortcuts.php');

# Drafts
require_once($IP.'/extensions/Drafts/Drafts.php');
$egDraftsAutoSaveWait = 60;   // 1 minute

# FlvHandler
$wgFlowPlayer = 'extensions/FlvHandler/flowplayer/flowplayer-3.1.3.swf';
$wgFileExtensions[] = 'flv';
require_once($IP.'/extensions/FlvHandler/FlvHandler.php');

# IntraACL
if (!isset($egDisableIntraACL))
{
    require_once('extensions/IntraACL/includes/HACL_Initialize.php');
    enableIntraACL();
    $haclgInclusionDeniedMessage = '';
    $haclgEnableTitleCheck = true;
}

# MWQuizzer
$egMWQuizzerIntraACLAdminGroup = 'Group/QuizAdmin';
require_once($IP.'/extensions/mediawikiquizzer/mediawikiquizzer.php');
MediawikiQuizzer::setupNamespace(104);

# Wikilog
require_once($IP.'/extensions/Wikilog/Wikilog.php');
define('NS_BLOG', 100);
Wikilog::setupNamespace(NS_BLOG, 'Блог', 'Обсуждение_блога');
$wgWikilogPagerDateFormat = 'ymd hms';
$wgNamespacesToBeSearchedDefault[NS_BLOG] = 1;
$wgWikilogMaxCommentSize = 0x7FFFFFFF;
$wgWikilogDefaultNotCategory = 'Скрытые';
$wgWikilogSearchDropdowns = true;
$wgWikilogCommentsOnItemPage = true;

# TemplatedPageList
require_once($IP.'/extensions/TemplatedPageList/TemplatedPageList.php');
$egSubpagelistAjaxNamespaces = array_flip(array(NS_MAIN, NS_USER, NS_PROJECT, NS_FILE, NS_MEDIAWIKI, NS_TEMPLATE, NS_HELP, NS_BLOG, NS_QUIZ));
$egSubpagelistAjaxDisableRE = '#^Блог:[^/]*$#s';

$wgMaxFilenameLength = 50;

$wgSVGConverter = "inkscape";
$wgUseImageMagick = false;
$wgGDAlwaysResample = true;

require_once($IP . '/includes/GlobalFunctions.php');
if (wfIsWindows())
{
    $wgSVGConverterPath = realpath($IP."/../../app/inkscape/");
    $wgDIAConverterPath = realpath($IP."/../../app/dia/bin/");
    //$wgImageMagickConvertCommand = realpath($IP."/../../app/imagemagick")."/convert.exe";
    # Bug 48216
    $wgTransliterateUploadFilenames = true;
    $wgSphinxQL_host = '127.0.0.1';
    $wgSphinxQL_port = '9306';
}

$wgCookieExpiration = 3650 * 86400;

$wgLogo    = "$wgScriptPath/custisinstall/logos/wiki4intranet-logo.png";
$wgFavicon = "$wgScriptPath/custisinstall/favicons/wiki4intranet.ico";

$wgDebugLogFile = false;

$wgDefaultSkin = 'monobook';

$wgGroupPermissions['*']['edit'] = false;

$wgSphinxTopSearchableCategory = "Root";

// Bug (Bug 43343), because detect VSD-files as application/msword, 
// or incorrect define of .mm files
$wgVerifyMimeType = false;

$wgNamespacesToBeSearchedDefault = array(
    NS_MAIN => 1,
    NS_USER => 1,
    NS_FILE => 1,
    NS_HELP => 1,
    NS_CATEGORY => 1,
);

$wgShellLocale = 'ru_RU.UTF-8';

$wgNoCopyrightWarnings = true;

$wgCookieHttpOnly      = false;
$wgEnableMWSuggest     = true;
$wgOpenSearchTemplate  = true;

// Don't purge recent changes... (keep them for 50 years)
$wgRCMaxAge = 50 * 365 * 86400;

$wgGroupPermissions['*']['delete'] = true;
$wgGroupPermissions['*']['undelete'] = true;
$wgGroupPermissions['*']['upload_by_url'] = true;
$wgGroupPermissions['sysop']['deletebatch'] = true;

// Default settings for Sphinx search
$wgSphinxSearch_weights = array('page_title' => 2, 'old_text' => 1);
$wgSphinxSearch_matches = 20;
$wgSphinxMatchAll = 1;
$wgSphinxSuggestMode = true;

$wgMaxImageArea = 5000*5000;

// Allow all ?action=raw content types
$wgAllowedRawCTypes = true;
