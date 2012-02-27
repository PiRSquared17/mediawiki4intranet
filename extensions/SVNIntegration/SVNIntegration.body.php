<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Stefan Macke <development@stefan-macke.com>
 * @copyright Copyright (C) 2008 Stefan Macke 
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 *
 * An extension that adds SVN integration to MediaWiki.
 *
 */

/*
 $Id: SVNIntegration.body.php 86 2009-02-17 11:05:46Z Stefan $
*/

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the extension file directly.
if (!defined('MEDIAWIKI')) {
	echo <<<EOT
To install the SVNIntegration extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/SVNIntegration/SVNIntegration.setup.php" );
EOT;
	exit(1);
}

// names of SVN tags
$SVNIntegrationSettings['tags'] = array(
	"SVNPrintFile",
	"SVNFileInfo",
	"SVNFileHistory",
	"SVNTodo"
);

// some used constants
$SVNIntegrationSettings['urlRegex'] = "^(https?|ftp|svn\+ssh)\:\/\/([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*(\:[0-9]{2,5})?(\/([a-z0-9%+\$_-]\.?)+)*\/?(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?(#[a-z_.-][a-z0-9+\$_.-]*)?\$";
$SVNIntegrationSettings['markerPattern'] = 'svn-marker-XXX-svn-marker';

// check existence of GeSHi extension
$SVNIntegrationSettings['geshiExists'] = defined('GESHI_VERSION');

// array used for unformatted SVN output
$SVNIntegrationSettings['markerList'] = array();

// default filetype used for syntax highlighting
$SVNIntegrationSettings['filetype'] = null;

/**
 * Hook function used to print unformatted output after parsing of page is complete. 
 *
 * @param unknown_type $parser The MediaWiki parser.
 * @param String $text The page source.
 * @return String The new page source.
 */
function SVNIntegrationParserAfterTidy(&$parser, &$text) 
{
	// find markers in $text and replace them with actual output
	global $SVNIntegrationSettings;
	for ($i = 0; $i < count($SVNIntegrationSettings['markerList']); $i++)
	{
		$pattern = str_replace('XXX', $i, $SVNIntegrationSettings['markerPattern']);
		$text = str_replace($pattern, SVNIntegrationGetOutput($SVNIntegrationSettings['markerList'][$i]), $text);
	}
	return true;
}

/**
 * Hook function used to add content from SVN to the article's text so that it becomes searchable.
 *
 * @param Article $article The article (Article object) being saved.
 * @param User $user The user (User object) saving the article.
 * @param string $text The new article text.
 * @param string $summary The edit summary.
 * @param integer $minor Minor edit flag.
 * @param string $watch Not used.
 * @param string $sectionanchor Not used.
 * @param integer $flags Bitfield, see source code for details.
 * @return boolean Always returns true. 
 */ 
function SVNIntegrationArticleSave(&$article, &$user, &$text, &$summary, $minor, $watch, $sectionanchor, &$flags) 
{
	global $SVNIntegrationSettings, $wgParser;
	$tags = $SVNIntegrationSettings['tags'];

	if (!$SVNIntegrationSettings['appendOutputToText'])
		return true;	
	
	$parserOptions = ParserOptions::newFromUser($user);
	$appendComment = "";
	
	foreach ($tags as $tag)
	{
		$functionName = str_replace("SVN", "SVNIntegration", $tag);
		$matches = array();
		$pattern = '/<' . $tag . '>(.*)<\/' . $tag . '>/i';

		if (preg_match_all($pattern, $text, $matches))
		{
			foreach ($matches[0] as $match)
			{
				$result = $wgParser->parse($text, $article->title, $parserOptions);
				$appendComment .= "<!-- " . strip_tags($result->getText()) . "-->";
			}
		}
	
	}
	
	if ($appendComment != "")
	{
		$text .= "\n<!-- ###SVNCONTENT_START### -->\n";
		$text .= "\n<!-- content from SVN for full text search index follows -->\n\n";
		$text .= $appendComment;
		$text .= "\n<!-- ###SVNCONTENT_END### -->\n";
	}

	return true;
}


/**
 * Hook function used to remove content from SVN from the article's text before editing.
 *
 * @param EditPage $editpage The editpage (object) being called.
 * @return boolean Always returns true.
 */
function SVNIntegrationEditFormInitial(&$editpage)
{
	if (strpos($editpage->textbox1, "###SVNCONTENT_START###") === false)
		return true;
	$content = $editpage->textbox1;
	$lines = explode("\n", $content);
	
	$newContent = "";
	$removeLine = false;
	for ($i = 0; $i < count($lines); $i++)
	{
		$line = $lines[$i];
		if (strstr($line, "###SVNCONTENT_START###"))
			$removeLine = true;
		if (!$removeLine)
			$newContent .= $line . "\n";
		if (strstr($line, "###SVNCONTENT_END###"))
			$removeLine = false;
	}
	
	$editpage->textbox1 = $newContent;
	return true;
}

/**
 * Adds the hook function for every SVN tag.
 * E.g. tag SVNFileInfo will be handled by function SVNIntegrationFileInfo()
 */
function SVNIntegrationRegisterHooks()
{
	global $wgParser, $SVNIntegrationSettings;
	foreach ($SVNIntegrationSettings['tags'] as $svnTag)
	{
		$wgParser->setHook($svnTag, str_replace("SVN", "SVNIntegration", $svnTag));
	}
}

/**
 * Returns the wrapping start tags for SVN output.
 *
 * @return string The wrapping start tags for SVN output.
 */
function SVNIntegrationGetHeader()
{
	return '<div class="SVNIntegrationOutput">' . "\n";
}

/**
 * Returns the wrapping end tags for SVN output.
 *
 * @return string The wrapping end tags for SVN output.
 */
function SVNIntegrationGetFooter()
{
	return '</div><!-- SVNIntegrationOutput -->' . "\n";
}

/**
 * Returns the given text wrapped in tags for SVN output.
 *
 * @param string $str The text to wrap.
 * @param string $class The additional CSS class to wrap the output with. 
 * @return string The wrapped text.
 */
function SVNIntegrationGetOutput($str, $class = "")
{
	$output = SVNIntegrationGetHeader();
	if ($class != "")
		$output .= '<div class="' . $class . '">' . "\n";
	$output .= $str . "\n";
	if ($class != "")
		$output .= '</div><!-- ' . $class . "-->\n";
	$output .= SVNIntegrationGetFooter();
	return $output;
}

/**
 * Returns the given text wrapped as an error message.
 *
 * @param string $errMsg The error message.
 * @return string The error message wrapped in an error tag.
 */
function SVNIntegrationGetError($errMsg)
{
	return SVNIntegrationGetOutput('<div class="SVNIntegrationError">' . "\n" . $errMsg . "\n" . '</div><!-- SVNIntegrationError -->'. "\n");
}

/**
 * Handles the parameters given in the tags.
 *
 * @param array $params The parameters given as attributes of the tags.
 */
function SVNIntegrationHandleParams($params)
{
	global $SVNIntegrationDefaultSettings, $SVNIntegrationSettings;
	
	if (!is_array($params))
		return;

	// handle individual parameters
	if (isset($params['filetype']))
	{
		$SVNIntegrationSettings['filetype'] = $params['filetype'];
		unset($params['filetype']);
	}
	
	// remove empty username/password parameters
	if (isset($SVNIntegrationSettings['username']) && (trim($SVNIntegrationSettings['username']) == ""))
		unset($SVNIntegrationSettings['username']);
	if (isset($SVNIntegrationSettings['password']) && (trim($SVNIntegrationSettings['password']) == ""))
		unset($SVNIntegrationSettings['password']);
	
	// use all given parameters
	$SVNIntegrationSettings['svnParams'] = array_merge($SVNIntegrationDefaultSettings['svnParams'], $params);
}

/**
 * Handles any errors (simply prints them).
 *
 * @param array $errors The errors in an array.
 * @return string The wrapped error messages.
 */
function SVNIntegrationHandleErrors($errors)
{
	$errMsg = "";
	if (count($errors))
	{
		foreach ($errors as $e)
		{
			$errMsg .= $e['message'] . '<br />' . "\n";
		}
	}
	return SVNIntegrationGetError($errMsg);
}


/**
 * Returns a link to the given URL.
 *
 * @param string $url The URL to link to.
 * @return The link.
 */
function SVNIntegrationLinkToFile($url)
{
	wfLoadExtensionMessages('SVNIntegration');
	$info = pathinfo($url);
	return "<a href=\"" . $url . "\" title=\"" . wfMsg('svnintegration-filelinktext') . $url . "\">" . urldecode($info['basename']) . "</a>";
}

/**
 * Prints the contents of a file from SVN using GeSHi if possible.
 *
 * @see svnParserAfterTidy()
 * @param string $text The URL of the file to print.
 * @param array $params The parameters (e.g. username etc.).
 * @param Parser $parser The MediaWiki parser.
 * @return string The formatted content of the given file or a marker that will be replaced with the raw file contents after parsing is complete.
 */
function SVNIntegrationPrintFile($text, $params, $parser)
{
	global $SVNIntegrationSettings, $wgScriptPath;
	SVNIntegrationHandleParams($params);
	
	wfLoadExtensionMessages('SVNIntegration');
	$parser->disableCache();
	$parser->mOutput->addHeadItem(
		'svnintegration-style',
		"<link rel='stylesheet' type='text/css' href='$wgScriptPath/extensions/SVNIntegration/SVNIntegration.css' />"
	);
	
	if (!eregi($SVNIntegrationSettings['urlRegex'], $text))
	{
		return SVNIntegrationGetError(wfMsg('invalidurl') . $text);
	}
	
	// get an instance of VersionControl_SVN and configure it as needed
	$svnStack = &PEAR_ErrorStack::singleton('VersionControl_SVN');
	$options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_ASSOC, 'svn_path' => $SVNIntegrationSettings['svnPath']);
	$svn = VersionControl_SVN::factory(array('cat'), $options);
	$args = array($text);
	$svn->cat->passthru = false;
	if ($SVNIntegrationSettings['useUtf8'])
		$svn->cat->prepend_cmd = 'export LC_ALL=en_US.UTF8 && ';

	if ($output = $svn->cat->run($args, $SVNIntegrationSettings['svnParams']))
	{
		// display only selected lines
		$startLine = 0;
		$endLine = 0;
		if (isset($params['startline']) || isset($params['endline']))
		{
			$lines = explode("\n", $output);
			$numLines = count($lines);
			$startLine = isset($params['startline']) ? intval($params['startline']) : 1;
			$startLine = ($startLine < 1) ? 1 : $startLine;
			$endLine = isset($params['endline']) ? intval($params['endline']) : $numLines;
			$endLine = ($endLine > $numLines) ? $numLines : $endLine;
			if ($endLine < $startLine)
				$endLine = $numLines;
			$output = "";
			for ($i = $startLine - 1; $i < $endLine; $i++)
			{
				$output .= $lines[$i] . "\n";
			}
		}
		$output = htmlspecialchars($output);
		
		// try to find out file type for GeSHi and surround output with corresponding tags
		if ($SVNIntegrationSettings['geshiExists'])
		{
			$fileInfo = pathinfo($text);
			$fileExt = strtolower($fileInfo['extension']);
			$surroundTag = $fileExt;
			if (isset($SVNIntegrationSettings['fileExtensions'][$fileExt]))
				$surroundTag = $SVNIntegrationSettings['fileExtensions'][$fileExt];
			// overwrite filetype with user setting
			if (isset($SVNIntegrationSettings['filetype']))
				$surroundTag = $SVNIntegrationSettings['filetype'];
			$output = "<" . $surroundTag . ">" . utf8_encode($output) . "</" . $surroundTag . ">";
			
			$header = wfMsg('svnintegration-printfileheader') . SVNIntegrationLinkToFile($text);
			if ($startLine != 0)
			{
				$header .= " (" . wfMsg('svnintegration-fromline') . $startLine . " " . wfMsg('svnintegration-toline') . $endLine . ")";
			}
			
			return SVNIntegrationGetOutput("<div class=\"SVNIntegrationPrintFileHeader\">" . $header . "</div>\n" . $parser->recursiveTagParse($output), "SVNIntegrationPrintFile");
		}

		// return a marker if GeSHi is not available -> will be replaced after parsing of the page is complete
		$markercount = count($SVNIntegrationSettings['markerList']);
		$marker = str_replace('XXX', $markercount, $SVNIntegrationSettings['markerPattern']);
		$SVNIntegrationSettings['markerList'][$markercount] = "<pre>" . htmlspecialchars($output) . "</pre>\n";
		return "<div class=\"SVNIntegrationPrintFileHeader\">" . wfMsg('svnintegration-printfileheader') . SVNIntegrationLinkToFile($text) . "</div>\n" . $marker;
	}
	else
	{
		return SVNIntegrationHandleErrors($svnStack->getErrors());
	}
}

/**
 * Returns some information about the given file from SVN.
 *
 * @param string $text The URL of the file.
 * @param array $params The parameters (e.g. username etc.).
 * @param Parser $parser The MediaWiki parser.
 * @return string The formatted HTML information about the given file.
 */
function SVNIntegrationFileInfo($text, $params, $parser)
{
	global $SVNIntegrationSettings;
	
	SVNIntegrationHandleParams($params);
	
	wfLoadExtensionMessages('SVNIntegration');
	$parser->disableCache();
	$parser->mOutput->addHeadItem(
		'svnintegration-style',
		"<link rel='stylesheet' type='text/css' href='$wgScriptPath/extensions/SVNIntegration/SVNIntegration.css' />"
	);
	
	if (!eregi($SVNIntegrationSettings['urlRegex'], $text))
	{
		return SVNIntegrationGetError(wfMsg('svnintegration-invalidurl') . $text);
	}
	
	// get an instance of VersionControl_SVN and configure it as needed
	$svnStack = &PEAR_ErrorStack::singleton('VersionControl_SVN');
	// TODO: change fetch mode to ASSOC when available for 'svn info'
	$options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_RAW, 'svn_path' => $SVNIntegrationSettings['svnPath']);
	$svn = VersionControl_SVN::factory(array('info'), $options);
	$args = array($text);
	$svn->info->passthru = false;
	// TODO: VersionControl_SVN_Info does not support authentification
	$svn->info->valid_switches[] = 'username';
	$svn->info->valid_switches[] = 'password';
	if ($SVNIntegrationSettings['useUtf8'])
		$svn->info->prepend_cmd = 'export LC_ALL=en_US.UTF8 && ';
	
	if ($fileInfo = $svn->info->run($args, $SVNIntegrationSettings['svnParams']))
	{
		// TODO: handling needs to be updated when fetch mode ASSOC is available for 'svn info'
		$outputLines = explode("\n", $fileInfo);
		for ($i = 0; $i < count($outputLines); $i++)
			$outputLines[$i] = explode(": ", $outputLines[$i]);
		
		$info = array();
		$info['path']['value'] = $outputLines[0][1];
		$info['name']['value'] = $outputLines[1][1];
		$info['url']['value'] = $outputLines[2][1];
		$info['base']['value'] = $outputLines[3][1];
		$info['uuid']['value'] = $outputLines[4][1];
		$info['rev']['value'] = $outputLines[5][1];
		$info['node']['value'] = $outputLines[6][1];
		$info['author']['value'] = $outputLines[7][1];
		$info['revChanged']['value'] = $outputLines[8][1];
		// TODO: parsing of date needs to be updated when fetch mode ASSOC is available for 'svn info'
		$changedDate = strtotime(substr($outputLines[9][1], 0, 25));
		$info['dateChanged']['value'] = date($SVNIntegrationSettings['dateFormat'], $changedDate);
		
		$info['path']['text'] = wfMsg('svnintegration-fileinfopath');
		$info['name']['text'] = wfMsg('svnintegration-fileinfoname');
		$info['url']['text'] = wfMsg('svnintegration-fileinfourl');
		$info['base']['text'] = wfMsg('svnintegration-fileinfobase');
		$info['uuid']['text'] = wfMsg('svnintegration-fileinfouuid');
		$info['rev']['text'] = wfMsg('svnintegration-fileinforev');
		$info['node']['text'] = wfMsg('svnintegration-fileinfonode');
		$info['author']['text'] = wfMsg('svnintegration-fileinfoauthor');
		$info['revChanged']['text'] = wfMsg('svnintegration-fileinforevchanged');
		$info['dateChanged']['text'] = wfMsg('svnintegration-fileinfodatechanged');
		
		// get current revision's message
		$logSvn = VersionControl_SVN::factory(array('log'), array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_ASSOC, 'svn_path' => $SVNIntegrationSettings['svnPath']));
		$logSvn->log->passthru = false;
		if ($SVNIntegrationSettings['useUtf8'])
			$logSvn->log->prepend_cmd = 'export LC_ALL=en_US.UTF8 && ';
		$revNr = intval($info['revChanged']['value']);
		$logOptions = array_merge($SVNIntegrationSettings['svnParams'], array('revision' => $revNr));
		$revisionInfo = $logSvn->log->run(array($text), $logOptions);
		$info['message']['text'] = wfMsg('svnintegration-fileinfomessage', array($revNr));
		$info['message']['value'] = $parser->recursiveTagParse("\n" . trim($revisionInfo[0]['MSG']));
		
		// TODO: add description of current revision to the output
		$output = "<table>\n";
		$output .= "<tr><th colspan=\"2\">" . wfMsg('svnintegration-fileinfoheader') . SVNIntegrationLinkToFile($text) . "</th></tr>\n";
		$rowCounter = 1;
		foreach ($SVNIntegrationSettings['fileInfoFields'] as $f)
		{
			$rowClass = (bcmod($rowCounter++, 2) == 0) ? "even" : "odd";
			$output .= "<tr class=\"" . $rowClass . "\">\n";
			$output .= "<td class=\"description\">" . $info[$f]['text'] . "</td>\n";
			$output .= "<td class=\"value\">" . $info[$f]['value'] . "</td>\n";
			$output .= "</tr>\n";
		}
		$output .= "</table>\n";
		
		
		return SVNIntegrationGetOutput($output, "SVNIntegrationFileInfo");
	}
	else
	{
		return SVNIntegrationHandleErrors($svnStack->getErrors());
	}
}

/**
 * Returns the history of the given file.
 *
 * @param string $text The URL of the file.
 * @param array $params The parameters (e.g. username etc.).
 * @param Parser The MediaWiki parser.
 * @return string The formatted HTML history of the given file.
 */
function SVNIntegrationFileHistory($text, $params, $parser)
{
	global $SVNIntegrationSettings;
	
	SVNIntegrationHandleParams($params);
	
	wfLoadExtensionMessages('SVNIntegration');
	$parser->disableCache();
	$parser->mOutput->addHeadItem(
		'svnintegration-style',
		"<link rel='stylesheet' type='text/css' href='$wgScriptPath/extensions/SVNIntegration/SVNIntegration.css' />"
	);
	
	if (!eregi($SVNIntegrationSettings['urlRegex'], $text))
	{
		return SVNIntegrationGetError(wfMsg('svnintegration-invalidurl') . $text);
	}
	
	// get an instance of VersionControl_SVN and configure it as needed
	$svnStack = &PEAR_ErrorStack::singleton('VersionControl_SVN');
	$options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_ASSOC, 'svn_path' => $SVNIntegrationSettings['svnPath']);
	$svn = VersionControl_SVN::factory(array('log'), $options);
	$args = array($text);
	$svn->log->passthru = false;
	if ($SVNIntegrationSettings['useUtf8'])
		$svn->info->prepend_cmd = 'export LC_ALL=en_US.UTF8 && ';
	
	if ($history = $svn->log->run($args, $SVNIntegrationSettings['svnParams']))
	{
		$output = "<div class=\"SVNIntegrationHistoryHeader\">\n";
		$output .= wfMsg('svnintegration-historyheader') . SVNIntegrationLinkToFile($text) . "\n</div><!-- SVNIntegrationHistoryHeader -->";
		$output .= "<div class=\"SVNIntegrationHistoryEntries\">\n";
		foreach ($history as $k=>$v)
		{
			$changedDate = date($SVNIntegrationSettings['dateFormat'], strtotime($v['DATE']));
			$revDesc = wfMsg('svnintegration-historyrev', array($v['REVISION'], $v['AUTHOR'], $changedDate));
			$output .= "<div class=\"SVNIntegrationHistoryEntry\">\n";
			$output .= "<div class=\"SVNIntegrationHistoryEntryRevision\">" . $revDesc . "</div>\n";
			// the line break is necessary for the parser to correctly render the first line of the output
			$output .= "<div class=\"SVNIntegrationHistoryEntryMessage\">" . $parser->recursiveTagParse("\n" . trim($v['MSG'])) . "</div>\n";
			$output .= "</div><!-- SVNIntegrationHistoryEntry -->\n";
		}
		$output .= "</div><!-- SVNIntegrationHistoryEntries -->\n";
		
		return SVNIntegrationGetOutput($output, "SVNIntegrationFileHistory");
	}
	else
	{
		return SVNIntegrationHandleErrors($svnStack->getErrors());
	}
}

/**
 * Searches for TODO tags in the given file and displays them.
 * 
 * The following tags are processed: TODO, FIXME, XXX  
 *
 * @see svnParserAfterTidy()
 * @param string $text The URL of the file to search for TODO-Tags.
 * @param array $params The parameters (e.g. username etc.).
 * @param Parser The MediaWiki parser.
 * @return string A list of all found TODO-Tags.
 */
function SVNIntegrationTodo($text, $params, $parser)
{
	global $SVNIntegrationSettings;
	SVNIntegrationHandleParams($params);

	wfLoadExtensionMessages('SVNIntegration');
	$parser->disableCache();
	$parser->mOutput->addHeadItem(
		'svnintegration-style',
		"<link rel='stylesheet' type='text/css' href='$wgScriptPath/extensions/SVNIntegration/SVNIntegration.css' />"
	);
	
	if (!eregi($SVNIntegrationSettings['urlRegex'], $text))
	{
		return SVNIntegrationGetError(wfMsg('invalidurl') . $text);
	}
	
	// get an instance of VersionControl_SVN and configure it as needed
	$svnStack = &PEAR_ErrorStack::singleton('VersionControl_SVN');
	$options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_ASSOC, 'svn_path' => $SVNIntegrationSettings['svnPath']);
	$svn = VersionControl_SVN::factory(array('cat'), $options);
	$args = array($text);
	$svn->cat->passthru = false;
	if ($SVNIntegrationSettings['useUtf8'])
		$svn->cat->prepend_cmd = 'export LC_ALL=en_US.UTF8 && ';
	
	if ($output = $svn->cat->run($args, $SVNIntegrationSettings['svnParams']))
	{
		$output = utf8_encode($output);
		$todos = array();
		
		$numberContextLines = $SVNIntegrationSettings['todoContext'];
		
		$todoList = "<ul>\n";
		$lines = explode("\n", $output);
		for ($i = 0; $i < count($lines); $i++)
		{
			$line = htmlspecialchars($lines[$i]);
			$todo = array();
			if (preg_match('/[\s\*\/]*(TODO|FIXME|XXX):?\s*(.*)/i', $line, $todo))
			{
				$todoType = $todo[1];
				$todoText = $todo[2] . " (" . $todoType . " " . wfMsg('svnintegration-todoline') . ($i + 1) . ")";
				if ($numberContextLines > 0)
				{
					$todoContext = '<pre class="SVNIntegrationTodoContext">';
					for ($j = max(0,$i-intval(($numberContextLines-1)/2)); $j <= $i+($numberContextLines-1)/2; $j++)
						$todoContext .= ($j + 1) . "\t" . htmlspecialchars($lines[$j]) . "\n";
					$todoContext .= "</pre>\n";
				}
				
				$todoList .= "<li>" . $todoText . "\n" . $todoContext . "</li>\n";
			}
		}
		$todoList .= "</ul>\n";

		return SVNIntegrationGetOutput("<div class=\"SVNIntegrationTodoHeader\">" . wfMsg('svnintegration-todoheader') . SVNIntegrationLinkToFile($text) . "</div>\n" . $todoList, "SVNIntegrationTodo");
	}
	else
	{
		return SVNIntegrationHandleErrors($svnStack->getErrors());
	}
}
