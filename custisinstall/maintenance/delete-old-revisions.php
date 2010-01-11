<?php

/**
 * Delete old (non-current) revisions from the database
 *
 * @file
 * @ingroup Maintenance
 * @author Vitaliy Filippov <vitalif@mail.ru>
 */

$dir = dirname($_SERVER['PHP_SELF']);
$options = array('delete', 'help', 'all');
require_once("$dir/../../maintenance/commandLine.inc");
require_once("$dir/../../maintenance/deleteOldRevisions.inc");

echo "Delete Old Revisions\n\n";

if (@$options['help'])
{
    ShowUsage();
    exit;
}

$pages = array();
foreach ($args as $page)
{
    if (!is_integer($page))
    {
        $page = Title::newFromText($page);
        if ($page)
            $page = $page->getArticleID();
    }
    if ($page)
        $pages[] = $page;
}
if (!count($pages) && !$options['all'])
{
    ShowUsage();
    echo "\nERROR: no pages selected and no --all option passed\n";
    exit;
}
DeleteOldRevisions(@$options['delete'], $pages);

function ShowUsage()
{
    echo "Deletes non-current revisions from the database.\n";
    echo "USAGE: php deleteOldRevisions.php [--delete|--help|--all] [<page_id>|'<page_title>' ...]\n";
    echo "--delete : Performs the deletion\n";
    echo "  --help : Show this usage information\n";
    echo "   --all : Allows deleting old revisions of all pages\n";
}
