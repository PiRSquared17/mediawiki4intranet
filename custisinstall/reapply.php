#!/usr/bin/php
<?php

# Revert all MediaWiki4Intranet patches and reapply them again
# Useful for manually updating Wiki installations
# (c) Vitaliy Filippov, 2011

$PATCHED_DIRS = explode(' ', 'includes languages skins/common');
$PATCHED_EXTENSIONS = explode(' ', 'CategoryTree AnyWikiDraw MediaFunctions Cite DeleteBatch Interwiki PdfHandler');
$CREATED_FILES = array('extensions/CategoryTree/SubcatCat.i18n.php');

$SELFDIR = realpath(dirname(__FILE__));
$DIR = dirname($SELFDIR); // installation directory

$d = $PATCHED_DIRS;
foreach($PATCHED_EXTENSIONS as $e)
    $d[] = 'extensions/'.$e;
foreach($d as &$e)
    $e = "'$DIR/$e'";

foreach($CREATED_FILES as $f)
    @unlink("$DIR/$f");
system("svn revert -R ".implode(' ', $d));
$patches = glob("$SELFDIR/patches/Y-*");
sort($patches);
foreach($patches as $patch)
    system("patch -d '$DIR' -p0 -t --no-backup-if-mismatch < $patch");
