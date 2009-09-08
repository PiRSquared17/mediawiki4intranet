<?php
/**
 * @Extension Full/Local Image
 * @Author Daniel Friesen (aka: Dantman )
 * @Description Creates 2 parserfunctions. Localimage and Fullimage, they work similar
 *      to the Localurl and Fullurl functions except they return the path to a image not a article.
 */
 
 
$wgExtensionFunctions[] = 'wfFLImage';
$wgHooks['LanguageGetMagic'][] = 'wfFLImageLanguageGetMagic';
$wgExtensionCredits['parserhook'][] = array(
	'name' => 'Full/Local Image',
	'author' => 'Daniel Friesen (aka: Dantman )',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Full_Local_Image'
);
 
function wfFLImage() {
	global $wgParser;
 
	$wgParser->setFunctionHook( 'Localimage', array( wgFLImageFuncts, 'Localimage' ), SFH_NO_HASH );
	$wgParser->setFunctionHook( 'Fullimage', array( wgFLImageFuncts, 'Fullimage' ), SFH_NO_HASH );
}
 
class wgFLImageFuncts {
	function Localimage ( &$parser, $name = '', $arg = null ) {
		$img = Image::newFromName( $name );
		if( $img != NULL ) return $img->getURL();
		return '';
	}
	function Fullimage ( &$parser, $name = '', $arg = null ) {
		global $wgServer;
		$img = Image::newFromName( $name );
		if( $img != NULL ) return $wgServer . $img->getURL();
		return '';
	}
}
 
# Register the magic words
function wfFLImageLanguageGetMagic( &$magicWords,$langCode = 0 ) {
	$magicWords['Localimage'] = array(0,'Localimage');
	$magicWords['Fullimage'] = array(0,'Fullimage');
	return true;
}
