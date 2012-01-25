#!/usr/bin/php
<?php

/* Help text */

$HELP = 'MediaWiki4Intranet installation script
(c) 2010-2011, Vitaliy Filippov, Stas Fomin

Instructions:
- create a directory for MediaWiki
- copy "custisinstall" directory inside there
- run "php WHATEVER/custisinstall/install.php"

To upgrade an existing MediaWiki4Intranet installation:
- run "php WHATEVER/custisinstall/install.php"

To look at the commands which would be run, but do not run them:
- run "php WHATEVER/custisinstall/install.php --dry-run"
';

/* URL config */

$SVN_WIKIMEDIA = 'http://svn.wikimedia.org/svnroot/mediawiki/tags/REL1_16_2';
$SVN_WIKIMEDIA_17 = 'http://svn.wikimedia.org/svnroot/mediawiki/tags/REL1_17_0';
$SVN_WIKIMEDIA_TRUNK = 'http://svn.wikimedia.org/svnroot/mediawiki/trunk';
$SVN_OUR = 'svn://svn.office.custis.ru/mediawiki';

/* File set config */

$DIRS_WIKIMEDIA = explode(' ',
    'config includes languages maintenance skins');
$FILES_WIKIMEDIA = explode(' ',
    'api.php index.php COPYING thumb.php trackback.php'.
    ' opensearch_desc.php img_auth.php redirect.php');
$EXT_WIKIMEDIA = explode(' ',
    'ParserFunctions CharInsert SyntaxHighlight_GeSHi Cite WhoIsWatching CategoryTree DeleteBatch');
$EXT_WIKIMEDIA_17 = explode(' ', "Interwiki");
$EXT_WIKIMEDIA_TRUNK = explode(' ',
    'googleAnalytics Renameuser UserMerge PagedTiffHandler MediaFunctions WikiCategoryTagCloud ConfirmEdit');
$EXT_OUR = array_map('trim', explode("\n",
    "AllNsSuggest
    AllowGetParamsInWikilinks
    AnyWikiDraw
    BatchEditor
    BugzillaBuglist
    Calendar
    CategoryTemplate
    CharInsertList
    CustisScripts
    CustomToolbox
    Dia
    DocExport
    Drafts
    EnotifDiff
    FavRate
    FlvHandler
    GlobalAuth
    HttpAuth
    IntraACL
    ListFeed
    MagicNumberedHeadings
    MarkupBabel
    MatchByPrefix
    MergeConflicts
    MMHandler
    Mp3Handler
    NewPagesEx
    OpenID
    PdfHandler
    PlantUML
    Polls
    RegexParserFunctions
    RemoveConfidential
    S5SlideShow
    Shortcuts
    SimpleForms
    SimpleTable
    SiteExport
    SpecialForm
    SphinxSearch
    SphinxSearchEngine
    SupaMW
    SVGEdit
    SVNIntegration
    SWFUpload
    TemplatedPageList
    UserMagic
    UserMessage
    WikiBookmarks
    Wikilog
    Workflow
    mediawikiquizzer"
));
$SKINS_OUR = explode(' ', 'custis custisru dumphtml ichick');

/* The install script */

$SELFDIR = realpath(dirname(__FILE__));
$DIR = dirname($SELFDIR); // installation directory
$UPGRADE = file_exists("$DIR/LocalSettings.php");
$WINDOWS = substr(php_uname(), 0, 7) == 'Windows';
$SCRIPT = '';
$DRY_RUN = false;

for ($i = 1; $i < count($argv); $i++)
{
    $a = strtolower($argv[$i]);
    if ($a == '--dry-run')
    {
        $DRY_RUN = true;
    }
    elseif ($a == '/?' || $a == '/h' || $a == '/help' || $a == '-h' || $a == '--help')
    {
        print $HELP;
        exit;
    }
}

if ($UPGRADE)
{
    cmd("echo Upgrading into $DIR");
    switch_dirs("$SVN_WIKIMEDIA/phase3", $DIR, $DIRS_WIKIMEDIA);
    switch_dirs("$SVN_WIKIMEDIA/extensions", "$DIR/extensions", $EXT_WIKIMEDIA);
    switch_dirs("$SVN_WIKIMEDIA_17/extensions", "$DIR/extensions", $EXT_WIKIMEDIA_17);
    switch_dirs("$SVN_WIKIMEDIA_TRUNK/extensions", "$DIR/extensions", $EXT_WIKIMEDIA_TRUNK);
}
else
{
    cmd("echo Installing into $DIR");
    get_dirs("$SVN_WIKIMEDIA/phase3", $DIR, $DIRS_WIKIMEDIA);
    get_dirs("$SVN_WIKIMEDIA/extensions", "$DIR/extensions", $EXT_WIKIMEDIA);
    get_dirs("$SVN_WIKIMEDIA_17/extensions", "$DIR/extensions", $EXT_WIKIMEDIA_17);
    get_dirs("$SVN_WIKIMEDIA_TRUNK/extensions", "$DIR/extensions", $EXT_WIKIMEDIA_TRUNK);
}

get_files("$SVN_WIKIMEDIA/phase3", "$DIR", $FILES_WIKIMEDIA);
get_dirs("$SVN_OUR/extensions", "$DIR/extensions", $EXT_OUR);
get_dirs("$SVN_OUR/skins", "$DIR/skins", $SKINS_OUR);
foreach ($SKINS_OUR as $s)
    cmd("svn export $SVN_OUR/skins/$s.php $DIR/skins/$s.php");

cmd("svn export $SVN_OUR/extensions/FullLocalImage.php $DIR/extensions/FullLocalImage.php");
cmd("svn co --force http://geshi.svn.sourceforge.net/svnroot/geshi/trunk/geshi-1.0.X/src/ $DIR/extensions/geshi/");
cmd("svn revert -R $DIR/extensions/geshi");

cmd(($WINDOWS ? "del" : "rm")." $DIR/extensions/CategoryTree/SubcatCat.i18n.php");

foreach (glob("$SELFDIR/patches/Y-*") as $p)
    cmd("echo Applying $p\npatch -d $DIR -p0 < $p");

if ($UPGRADE)
    cmd("php maintenance/update.php");

if (!$WINDOWS)
{
    cmd("sudo chown -R www-data:www-data $DIR");
    cmd("sudo chmod 000 $DIR/config");
}

run_commands();
exit;

function get_dirs($src, $dst, $ls)
{
    foreach ($ls as $d)
    {
        if (file_exists("$dst/$d"))
            cmd("svn revert -R $dst/$d");
        cmd("svn co --force $src/$d $dst/$d");
    }
}

function switch_dirs($src, $dst, $ls)
{
    global $DIR;
    foreach ($ls as $d)
    {
        if (file_exists("$dst/$d"))
            cmd("cd $dst/$d\nsvn revert -R .\nsvn sw $src/$d\ncd $DIR");
        else
            cmd("svn co --force $src/$d $dst/$d");
    }
}

function get_files($src, $dst, $ls)
{
    foreach ($ls as $f)
        cmd("svn export $src/$f $dst/$f");
}

function cmd($cmd)
{
    global $SCRIPT;
    $SCRIPT .= trim($cmd) . "\n";
}

function run_commands()
{
    global $SCRIPT, $DRY_RUN, $WINDOWS, $EXEC, $DIR;
    if ($DRY_RUN)
        print $SCRIPT;
    else
    {
        $filename = "install.tmp.".($WINDOWS ? "bat" : "sh");
        $filename = "$DIR/$filename";
        if (file_put_contents($filename, $SCRIPT))
        {
            system($WINDOWS ? $filename : "sh $filename");
            unlink($filename);
        }
        else
        {
            print("Can not write into $filename\n");
            exit;
        }
    }
}
