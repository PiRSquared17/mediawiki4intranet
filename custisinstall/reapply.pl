#!/usr/bin/perl
# TODO переписать на PHP

unlink("extensions/CategoryTree/SubcatCat.i18n.php");
system("svn revert -R includes extensions/CategoryTree languages extensions/AnyWikiDraw extensions/MediaFunctions extensions/Cite skins/common extensions/DeleteBatch extensions/Interwiki");
for my $i (glob "custisinstall/patches/Y-*")
{
    system("patch -p0 -t --no-backup-if-mismatch < $i");
}
