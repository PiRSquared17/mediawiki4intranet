#!/usr/bin/php
<?php
# Multipart-"архиватор" для заливки на сервер множества изображений разом через импорт
# USAGE: ./multiparc.py file1 file2 file3 > outfile

if (count($argv) == 1)
{
    print "USAGE: php multiparc.php FILE1 FILE2 FILE3 > OUTFILE\n";
    exit;
}

$boundary = '--' . time();
print "Content-Type: multipart/related; boundary=$boundary
$boundary
Content-Type: text/xml
Content-ID: Revisions

".'<?xml version="1.0" encoding="UTF-8" ?>
<mediawiki xmlns="http://www.mediawiki.org/xml/export-0.3/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.mediawiki.org/xml/export-0.3/ http://www.mediawiki.org/xml/export-0.3.xsd"
    version="0.3" xml:lang="ru">
';

$sizes = array();
for ($i = 1; $i < count($argv); $i++)
{
    $filename = $argv[$i];
    $stat = stat($filename);
    if (!$stat)
    {
        print "File not found: $filename\n";
        continue;
    }
    $sha1 = sha1_file($filename);
    $wikiname = htmlspecialchars(str_replace(':', '-', preg_replace('#^.*/#is', '', $filename)));
    $files[$wikiname] = $filename;
    $timestamp = strftime('%Y-%m-%dT%H:%M:%SZ', $stat['mtime']);
    print "<page>
 <title>File:$wikiname</title>
 <upload>
  <timestamp>$timestamp</timestamp>
  <filename>$wikiname</filename>
  <src sha1=\"$sha1\">multipart://$wikiname</src>
  <size>$stat[size]</size>
 </upload>
</page>";
}
print "</mediawiki>\n";

foreach ($files as $wikiname => $filename)
{
    $data = file_get_contents($filename);
    print "$boundary
Content-Type: application/binary
Content-Transfer-Encoding: Little-Endian
Content-ID: $wikiname
Content-Length: ".strlen($data)."

$data";
}
