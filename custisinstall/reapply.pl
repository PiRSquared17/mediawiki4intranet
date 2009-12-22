#!/usr/bin/perl

system("svn revert -R includes extensions/Wikilog languages extensions/AnyWikiDraw extensions/MediaFunctions skins/common");
for my $i (glob "custisinstall/patches/Y-*")
{
    system("patch -p0 -t --no-backup-if-mismatch < $i");
}
