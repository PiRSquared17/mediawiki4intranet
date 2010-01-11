<?php
/**
 * @file
 * @ingroup Maintenance
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
				$nfn = $ts.substr($fn, $p);
				if ($fn != $nfn)
				{
					if (!$file || $lastfilename != $oi['oi_name'])
					{
						$lastfilename = $oi['oi_name'];
						$file = wfLocalFile($oi['oi_name']);
						$path = $file->repo->getZonePath('public') . '/archive/' . $file->getHashPath();
					}
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
		$dbr->commit();
	}
	
	function help() {
		echo <<<END
By default, old files in MediaWiki are stored containing timestamp of **next**
revision in file names. This script renames them to contain **their** timestamp
in file names.

Usage:
php archive-image-renamer.php

END;
	}
}

print "Going to check oldimage archive names for ".wfWikiID()."\n";

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
