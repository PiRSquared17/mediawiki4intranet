<?php
/**
 * SVGEdit extension: hooks
 * @copyright 2010 Brion Vibber <brion@pobox.com>
 */

class SVGEditHooks {
	/* Static Methods */

	/**
	 * BeforePageDisplay hook
	 *
	 * Adds the modules to the page
	 *
	 * @param $out OutputPage output page
	 * @param $skin Skin current skin
	 */
	public static function beforePageDisplay( $out, $skin ) {
		global $wgUser, $wgSVGEditInline, $wgScriptPath, $wgTitle;
		$title = $wgTitle;
		if( self::trigger( $title ) )
		{
			wfLoadExtensionMessages( 'SVGEdit' );
			$out->addHeadItem( 'jquery', '<script language="JavaScript" src="'.$wgScriptPath.'/extensions/SVGEdit/modules/jquery.min.js"></script>' );
			$out->addHeadItem( 'jquery-ui', '<script language="JavaScript" src="'.$wgScriptPath.'/extensions/SVGEdit/modules/jquery-ui.min.js"></script>' );
			$out->addHeadItem( 'js-mediaWiki-emu', '<script type="text/javascript">
var mediaWiki = {
	messages : {
		"svgedit-summary-label" : "'     . addslashes( wfMsg( 'svgedit-summary-label' ) )     . '",
		"svgedit-summary-default" : "'   . addslashes( wfMsg( 'svgedit-summary-default' ) )   . '",
		"svgedit-editor-save-close" : "' . addslashes( wfMsg( 'svgedit-editor-save-close' ) ) . '",
		"svgedit-editor-close" : "'      . addslashes( wfMsg( 'svgedit-editor-close' ) )      . '",
		"svgedit-editbutton-edit" : "'   . addslashes( wfMsg( 'svgedit-editbutton-edit' ) )   . '",
		"svgedit-editbutton-create" : "' . addslashes( wfMsg( 'svgedit-editbutton-create' ) ) . '",
		"svgedit-edit-tab" : "'          . addslashes( wfMsg( 'svgedit-edit-tab' ) )          . '",
		"svgedit-edit-tab-tooltip" : "'  . addslashes( wfMsg( 'svgedit-edit-tab-tooltip' ) )  . '",
		"svgedit-editbutton-edit" : "'   . addslashes( wfMsg( 'svgedit-editbutton-edit' ) )   . '"
	},
	msg : function(k) { return mediaWiki.messages[k]; },
	util : {
		"addPortletLink" : addPortletLink
	},
};
var wgScriptExtension = \'.php\';
</script>');
			$out->addHeadItem( 'svgedit.embedapi', '<script language="JavaScript" src="'.$wgScriptPath.'/extensions/SVGEdit/modules/ext.svgedit.embedapi.js"></script>' );
			$out->addHeadItem( 'svgedit.formmultipart', '<script language="JavaScript" src="'.$wgScriptPath.'/extensions/SVGEdit/modules/ext.svgedit.formmultipart.js"></script>' );
			$out->addHeadItem( 'svgedit.io', '<script language="JavaScript" src="'.$wgScriptPath.'/extensions/SVGEdit/modules/ext.svgedit.io.js"></script>' );
			$out->addHeadItem( 'svgedit.editor', '<script language="JavaScript" src="'.$wgScriptPath.'/extensions/SVGEdit/modules/ext.svgedit.editor.js"></script>' );
			$out->addHeadItem( 'svgedit.editButton.js', '<script language="JavaScript" src="'.$wgScriptPath.'/extensions/SVGEdit/modules/ext.svgedit.editButton.js"></script>' );
			$out->addHeadItem( 'svgedit.editButton.css', '<link rel="stylesheet" type="text/css" href="'.$wgScriptPath.'/extensions/SVGEdit/modules/ext.svgedit.editButton.css" />' );
		}
		if ($wgSVGEditInline) {
			// Experimental inline edit trigger.
			// Potentially expensive and tricky as far as UI on article pages!
			if( $wgUser->isAllowed( 'upload' ) ) {
				$out->addHeadItem( 'svgedit.inline', '<script language="JavaScript" src="'.$wgScriptPath.'/extensions/SVGEdit/modules/ext.svgedit.inline.js"></script>' );
			}
		}
		return true;
	}

	/**
	 * MakeGlobalVariablesScript hook
	 *
	 * Exports a setting if necessary.
	 *
	 * @param $vars array of vars
	 */
	public static function makeGlobalVariablesScript( &$vars ) {
		global $wgTitle, $wgSVGEditEditor;
		#if( self::trigger( $wgTitle ) ) {
			$vars['wgSVGEditEditor'] = $wgSVGEditEditor;
		#}
		return true;
	}

	/**
	 * Should the editor links trigger on this page?
	 *
	 * @param Title $title
	 * @return boolean
	 */
	private static function trigger( $title ) {
		return $title && $title->getNamespace() == NS_FILE &&
			$title->userCan( 'edit' ) && $title->userCan( 'upload' );
	}

	/**
	 * UploadForm:initial hook, suggests creating non-existing SVG files with SVGEdit
	 */
	public static function uploadFormInitial($upload)
	{
		if ( strtolower( substr( $upload->mDesiredDestName, -4 ) ) == '.svg' )
		{
			$title = Title::newFromText( $upload->mDesiredDestName, NS_FILE );
			if ( $title )
				$upload->uploadFormTextTop .= wfMsgNoTrans(
					'svgedit-suggest-create', $title->getFullUrl().'#!action=svgedit'
				);
		}
		return true;
	}
}
