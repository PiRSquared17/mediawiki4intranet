#!/usr/bin/perl

unlink("extensions/Wikilog/WikilogCalendar.php");
unlink("extensions/CategoryTree/SubcatCat.i18n.php");
system("svn revert -R includes extensions/Wikilog extensions/CategoryTree languages extensions/AnyWikiDraw extensions/MediaFunctions extensions/Cite skins/common");
for my $i (glob "custisinstall/patches/Y-*")
{
    system("patch -p0 -t --no-backup-if-mismatch < $i");
}
