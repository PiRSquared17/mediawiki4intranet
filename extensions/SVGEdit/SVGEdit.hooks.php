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
		global $wgTitle, $wgScriptPath, $wgScriptExtension;
		if( $wgTitle && $wgTitle->getNamespace() == NS_FILE && ( $file = wfFindFile( $wgTitle ) ) &&
			(!$_REQUEST['action'] || $_REQUEST['action'] == 'view' || $_REQUEST['action'] == 'purge') &&
			preg_match( '/\.svg$/is', $wgTitle->getText() ) )
		{
			$out->addHTML(
'<iframe id="svg-edit" style="display: none; position: fixed; left: 2.5%; top: 2.5%; width: 95%; height: 95%; z-index: 99999"></iframe>
<input type="button" value="Edit drawing" onclick="triggerSVGEdit()" />
<script language="JavaScript">
if (!window.wgScriptExtension)
    window.wgScriptExtension = "'.addslashes($wgScriptExtension).'";
window.wgFileFullUrl = "'.addslashes($file->getFullUrl()).'";
function triggerSVGEdit()
{
  var i = document.getElementById("svg-edit");
  i.style.display = "";
  i.src = wgScriptPath + "/extensions/SVGEdit/svg-edit/svg-editor.html";
}
</script>'
			);
		}
		return true;
	}
}
