<?php

/**
 * Wrapper to integrate SVG-edit in-browser vector graphics editor in MediaWiki.
 * http://www.mediawiki.org/wiki/Extension:SVGEdit
 *
 * @copyright 2010 Brion Vibber <brion@pobox.com>
 *
 * MediaWiki-side code is GPL
 *
 * SVG-edit is under Apache license: http://code.google.com/p/svg-edit/
 */

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'SVGEdit',
	'author'         => array( 'Brion Vibber' ),
	'url'            => 'http://www.mediawiki.org/wiki/Extension:SVGEdit',
	'descriptionmsg' => 'svgedit-desc',
);
$wgExtensionMessagesFiles['SVGEdit'] =  dirname(__FILE__) . '/SVGEdit.i18n.php';

$wgHooks['BeforePageDisplay'][] = 'SVGEditHooks::beforePageDisplay';
$wgHooks['MakeGlobalVariablesScript'][] = 'SVGEditHooks::makeGlobalVariablesScript';
$wgHooks['UploadForm:initial'][] = 'SVGEditHooks::uploadFormInitial';

$wgAutoloadClasses['SVGEditHooks'] = dirname( __FILE__ ) . '/SVGEdit.hooks.php';

// Can set to alternate SVGEdit URL to pull the editor's HTML/CSS/JS/SVG
// resources from another domain; will still need to have the MediaWiki
// extension in it, so use a checkout of this extension rather than a
// master copy of svg-edit on its own.
//
// Example: $wgSVGEditEditor = 'http://toolserver.org/~brion/svg-edit/svg-editor.html';
//
// If left empty, the local copy will be used on the main MediaWiki domain.
//
$wgSVGEditEditor = false;

// Set to enable experimental triggers for SVG editing within article views.
// Not yet recommended.
$wgSVGEditInline = false;
