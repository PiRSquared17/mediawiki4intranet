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
 $Id: SVNIntegration.setup.php 89 2009-02-17 11:24:41Z Stefan $
*/

// comment in to display possible errors
ini_set('display_errors', 'on');

# Alert the user that this is not a valid entry point to MediaWiki if they try to access the extension file directly.
if (!defined('MEDIAWIKI')) {
	echo <<<EOT
To install the SVNIntegration extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/SVNIntegration/SVNIntegration.setup.php" );
EOT;
	exit(1);
}

$wgExtensionCredits['other'][] = array(
	'name'           => 'SVNIntegration',
	'author'         => 'Stefan Macke', 
	'url'            => 'http://www.mediawiki.org/wiki/Extension:SVNIntegration',
	'description'    => 'This extension adds SVN integration to MediaWiki.',
	'version'        => '1.1.3',
	'descriptionmsg' => 'svnintegration-desc',
);

// edit your options here  -----------------------------------------------

// PEAR path needed for inclusion
$SVNIntegrationSettings['pearPath'] = "/usr/share/php5/PEAR";

// path to SVN binary
$SVNIntegrationSettings['svnPath'] = "/usr/bin/svn";

// default username and password
// do not remove these lines! set the values to "" if you do not need a user/password
$SVNIntegrationSettings['svnParams']['username'] = "subversion";
// if you do not need a password (like for public key authorization) just delete or comment out the following line
$SVNIntegrationSettings['svnParams']['password'] = "subversion";

// file extensions that will be mapped to GeSHi highlighting
$SVNIntegrationSettings['fileExtensions'] = array(
	'nat' => 'natural',
	'nsp' => 'natural',
	'nsn' => 'natural'
);

// whether to append the extension's output to the article's text, so that it 
// becomes indexed and available via wiki search
$SVNIntegrationSettings['appendOutputToText'] = true;

// date format
$SVNIntegrationSettings['dateFormat'] = "Y-m-d H:i:s";

// wether to use UTF-8 encoding while querying SVN
$SVNIntegrationSettings['useUtf8'] = true;

// the following fields will be printed in the file info table
// possible values: path, name, url, base, uuid, rev, node, author, revChanged, dateChanged, message
$SVNIntegrationSettings['fileInfoFields'] = array('name', 'url', 'rev', 'author', 'dateChanged', 'revChanged', 'message');

// number of lines after TODO tags that will optionally be displayed when using SVNIntegrationTodo
$SVNIntegrationSettings['todoContext'] = 3;


// do not modify below this line ------------------------------------------

// include needed files
$dir = dirname(__FILE__) . '/';
$wgExtensionMessagesFiles['SVNIntegration'] = $dir . 'SVNIntegration.i18n.php';
require_once($dir . 'SVNIntegration.body.php');

// check existence of PEAR library VersionControl_SVN
@ini_set("include_path", ini_get("include_path") . PATH_SEPARATOR . $SVNIntegrationSettings['pearPath']);
@include_once('VersionControl/SVN.php');
if (!class_exists('VersionControl_SVN'))
{
	die("Could not load needed class 'VersionControl_SVN' in path '" . ini_get("include_path") . "'");
}

// add hook functions for SVN tags
$wgExtensionFunctions[] = "SVNIntegrationRegisterHooks";
$wgHooks['ParserAfterTidy'][] = 'SVNIntegrationParserAfterTidy';
$wgHooks['ArticleSave'][] = 'SVNIntegrationArticleSave';
$wgHooks['EditPage::showEditForm:initial'][] = 'SVNIntegrationEditFormInitial';
