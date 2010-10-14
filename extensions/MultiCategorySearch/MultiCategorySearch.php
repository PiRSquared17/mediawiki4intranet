<?php
/**
* Multi-Category Search 1.5
* This MediaWiki extension represents a [[Special:MultiCategorySearch|special page]],
* 	that allows to find pages, included in several specified categories at once.
* Extension setup file.
* Requires MediaWiki 1.8 or higher and MySQL 4.1 or higher.
* Extension's home page: http://www.mediawiki.org/wiki/Extension:Multi-Category_Search
*
* Copyright (c) Moscow, 2008-2010, Iaroslav Vassiliev  <codedriller@gmail.com>
* Distributed under GNU General Public License 2.0 or later (http://www.gnu.org/copyleft/gpl.html)
*/

if ( !defined('MEDIAWIKI') ) {
	echo <<<EOT
To install this extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/MultiCategorySearch/MultiCategorySearch.php" );
EOT;
	exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Multi-Category Search',
	'version' => 1.41,
	'author' => 'Iaroslav Vassiliev <codedriller@gmail.com>',
	'description' => 'Represents a [[Special:MultiCategorySearch|special page]], ' .
		'that allows to find pages, included in several specified categories at once.',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Multi-Category_Search'
);

$wgExtensionFunctions[] = 'wfSetupMultiCategorySearchExtension';

$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['MultiCategorySearch'] = $dir . 'MultiCategorySearch_body.php';
$wgSpecialPages['MultiCategorySearch'] = 'MultiCategorySearch';
//$wgExtensionMessagesFiles['MultiCategorySearch'] = $dir . 'MultiCategorySearch.i18n.php';

function wfSetupMultiCategorySearchExtension() {
	global $IP, $wgMessageCache;

	if( !function_exists('efMultiCategorySearchMessages') ) {
		require_once( 'MultiCategorySearch.i18n.php' );
		foreach( efMultiCategorySearchMessages() as $lang => $messages )
			$wgMessageCache->addMessages( $messages, $lang );
	}

	$title = Title::newFromText( 'MultiCategorySearch' );

	return true;
}
?>