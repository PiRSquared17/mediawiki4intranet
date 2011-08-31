<?php

/**
 * This tool is a part of MediaWiki4Intranet Import-Export patch.
 * http://wiki.4intra.net/MW_Import&Export
 * http://wiki.4intra.net/Mediawiki4Intranet
 * Copyright (c) 2010, Vitaliy Filippov
 *
 * Maintenance tool updating archived image revision filenames to:
 * 1) match the revision date, not the NEXT revision date as in the
 *    original MediaWiki.
 * 2) if $wgTransliterateUploadFilenames is true and the
 *    'translit-upload-filenames' patch is active, then change
 *    cyrillic uploaded file names into transliterated ones.
 *
 * USAGE: copy this file into maintenance/ subdirectory of MediaWiki
 * installation and run with command "php file-upload-renamer.php"
 *
 * DO NOT run this if you don't plan to use MW4Intranet Import-Export patch.
 */

$dir = dirname($_SERVER['PHP_SELF']);
require_once "$dir/../../maintenance/commandLine.inc";
require_once "$dir/../../maintenance/counter.php";

class OldImageRenamer
{
	function __construct( $args )
	{
		if ($args['quiet'])
			$this->quiet = true;
		if ($args['delunexisting'])
			$this->remove_unexisting = true;
		if ($args['bak'])
			$this->bak = true;
	}
	
	function out($s)
	{
		if (!$this->quiet)
			print $s;
	}
	
	function run()
	{
		$dbr = wfGetDB(DB_MASTER);
		$res = $dbr->select('oldimage', '*', '1', __METHOD__, array('FOR UPDATE', 'ORDER BY' => 'oi_name, oi_timestamp'));
		$file = NULL;
		$lastfilename = NULL;
		while ($oi = $dbr->fetchRow($res))
		{
			$row = array();
			foreach ($oi as $k => $v)
				if (!is_numeric($k))
					$row[$k] = $v;
			$ts = wfTimestamp(TS_MW, $oi['oi_timestamp']);
			$fn = $oi['oi_archive_name'];
			if (($p = strpos($fn, '!')) !== false)
			{
				if (!$file || $lastfilename != $oi['oi_name'])
				{
					$lastfilename = $oi['oi_name'];
					$file = wfLocalFile($oi['oi_name']);
					$path = $file->repo->getZonePath('public') . '/archive/' . $file->getHashPath();
				}
				$nfn = $ts.'!'.$file->getPhys();
				if ($fn != $nfn)
				{
					if ($this->remove_unexisting && !file_exists($path . $fn))
					{
						$dbr->delete('oldimage', $row, __METHOD__);
						print "Removed $fn from oldimage table\n";
						continue;
					}
					if (file_exists($path . $nfn))
					{
						if ($this->bak)
						{
							$i = 0;
							while (file_exists($path.$nfn.'.'.$i))
								$i++;
							rename($path.$nfn, $path.$nfn.'.'.$i);
							print "WARNING: moved $path$nfn into $path$nfn.$i\n";
						}
						else
						{
							print "Error moving $path$fn to $path$nfn: $path$nfn already exists\n";
							break;
						}
					}
					if (rename($path . $fn, $path . $nfn))
					{
						if ($dbr->update('oldimage', array('oi_archive_name' => $nfn), $row, __METHOD__))
							$this->out("Moved $path$fn to $path$nfn\n");
						else
						{
							rename($path . $nfn, $path . $fn);
							print "Error moving $path$fn to $path$nfn: can't update $fn to $nfn in the database\n";
							break;
						}
					}
					else
					{
						print "Error moving $path$fn to $path$nfn: can't rename()\n";
						break;
					}
				}
			}
		}
		$dbr->freeResult($res);
		/* Transliterate existing upload file names */
		global $wgTransliterateUploadFilenames;
		if ($wgTransliterateUploadFilenames)
		{
			$res = $dbr->select('image', 'img_name', '1', __METHOD__, array('FOR UPDATE'));
			while ($img = $dbr->fetchRow($res))
			{
				$file = wfLocalFile($img['img_name']);
				$path = $file->repo->getZonePath('public') . $file->getHashPath();
				$name = $file->getName();
				$phys = $file->getPhys();
				if ($name != $phys && file_exists($path.$name) && !file_exists($path.$phys))
				{
					if (rename($path.$name, $path.$phys))
						$this->out("Renamed $path$name to $path$phys\n");
					else
						print "Error moving $path$name to $path$phys\n";
				}
			}
			$dbr->freeResult($res);
		}
		$dbr->commit();
	}
	
	function help() {
		echo <<<END
This script does 2 things:

1) By default, old files in MediaWiki are stored containing timestamp of **next**
revision in file names. This script renames them to contain **their own** timestamp
in file names.

2) With CustIS patch, if \$wgTransliterateUploadFilenames is true, all upload file
names are transliterated during upload. So MediaWiki expects that filenames of
already uploaded files are also transliterated. This script automatically checks
them and transliterates if needed.

Usage:
php file-upload-renamer.php

END;
	}
}

print "Going to check upload filenames for ".wfWikiID()." (archive timestamps".($wgTransliterateUploadFilenames?" and transliteration":"").")\n";

if( !isset( $options['quick'] ) ) {
	print "Abort with control-c in the next five seconds... ";

	for ($i = 6; $i >= 1;) {
		print_c($i, --$i);
		sleep(1);
	}
	echo "\n";
}

$renamer = new OldImageRenamer( $options );
$renamer->run();
