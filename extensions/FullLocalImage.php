<?php
/**
 * @Extension Full/Local Image
 * @Author Daniel Friesen (aka: Dantman )
 * @Description Creates 2 parserfunctions. Localimage and Fullimage, they work similar
 *      to the Localurl and Fullurl functions except they return the path to a image not a article.
 * @Author Vitaliy Filippov: ParserOutput::addImage()
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

	$wgParser->setFunctionHook( 'Localimage', array( 'wgFLImageFuncts', 'Localimage' ), SFH_NO_HASH );
	$wgParser->setFunctionHook( 'Fullimage', array( 'wgFLImageFuncts', 'Fullimage' ), SFH_NO_HASH );
}

class wgFLImageFuncts {
	static function getImage( $name ) {
		$title = Title::makeTitleSafe( NS_FILE, $name );
		if ( is_object( $title ) ) {
			$img = wfFindFile( $title );
			if ( !$img ) {
				$img = wfLocalFile( $title );
			}
			return $img;
		} else {
			return null;
		}
	}
	static function Localimage ( &$parser, $name = '', $arg = null ) {
		$img = self::getImage( $name );
		if( $img != NULL ) {
			$parser->mOutput->addImage($img->title->getDBkey());
			return $img->getURL();
		}
		return '';
	}
	static function Fullimage( &$parser, $name = '', $arg = null ) {
		global $wgServer;
		$img = self::getImage( $name );
		if( !$img ) return '';
		$url = $img->getURL();
		if( substr( $url, 0, strlen( $wgServer ) ) != $wgServer ) $url = $wgServer . $url;
		$parser->mOutput->addImage($img->title->getDBkey());
		return $url;
	}
}

# Register the magic words
function wfFLImageLanguageGetMagic( &$magicWords,$langCode = 0 ) {
	$magicWords['Localimage'] = array(0,'Localimage');
	$magicWords['Fullimage'] = array(0,'Fullimage');
	return true;
}
