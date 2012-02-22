<?php
/**
 * SWFUpload.body.php -- Flash applet support for uploading files into MediaWiki
 * Copyright 2009 Vitaliy Filippov <vitalif@mail.ru>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @file
 * @ingroup Extensions
 * @author Vitaliy Filippov <vitalif@mail.ru>
 */

if(!defined('MEDIAWIKI'))
    exit(1);

class SpecialSWFUpload extends SpecialPage
{
    public function __construct()
    {
        parent::__construct('SWFUpload', 'upload');
    }
    public function execute($par)
    {
        global $wgScriptPath, $wgJsMimeType, $wgOut, $swfuploadJsMessageKeys, $wgUser;
        if (!$this->userCanExecute($wgUser))
        {
            $this->displayRestrictionError();
            return;
        }
        wfLoadExtensionMessages('SWFUpload');
        $path = $wgScriptPath . '/extensions/SWFUpload/';
        $cook = addslashes(serialize($_COOKIE));
        $vars = array();
        foreach (explode(' ',
            'allfiles pending uploading finished cancelled upload-stopped queue-limit-exceeded-0 queue-limit-exceeded-1 queue-limit-exceeded-2 '.
            'queue-limit-exceeded-5 file-too-big file-empty invalid-filetype unknown-error upload-error-http upload-error io-error security-error ' .
            'too-many-files validation-error num-files-uploaded-5 num-files-uploaded-2 num-files-uploaded-1 num-files-uploaded-0') as $k)
        {
            // Pass localisation messages to JS
            $vars[] = "'$k' : \"".str_replace("\n", "\\n", addslashes(wfMsg("swfupload-js-$k")))."\"";
        }
        $wgOut->setPageTitle(wfMsg('swfupload-title'));
        $wgOut->addScript("<script type=\"$wgJsMimeType\" language=\"JavaScript\">
var swfupload_path = \"".addslashes($path)."\";
var swfupload_cookies = \"$cook\";
var swfupload_lang = {" . join(", ", $vars) . "};
</script>");
        foreach (array('swfupload.js', 'swfupload.queue.js', 'fileprogress.js', 'handlers.js', 'add.js') as $a)
            $wgOut->addScript(Xml::element('script', array('type' => $wgJsMimeType, 'src' => $path . 'data/' . $a), '', false));
        $wgOut->addLink(array(
            'rel'  => 'stylesheet',
            'type' => 'text/css',
            'href' => $path . 'data/swfupload.css',
        ));
        $msg_comment = wfMsg('swfupload-comment');
        $msg_name_prefix = wfMsg('swfupload-filename-prefix');
        $msg_select_file = wfMsg('swfupload-select-file');
        $msg_cancel = wfMsg('swfupload-cancelbtn');
        $wgOut->addHTML(wfMsgExt('swfupload-page-text', 'parse'));
        $wgOut->addHTML(<<<EOF
<table>
    <tr><td colspan="2">$msg_comment</td></tr>
    <tr><td colspan="2"><textarea style="width:100%" onchange="upload1.addPostParam('wpUploadDescription', this.value)" cols="30" rows="3"></textarea></td></tr>
    <tr><td>$msg_name_prefix</td><td><input type="text" style="width:100%" onchange="upload1.addPostParam('swfuploadNamePrefix', this.value)" value="" /></td></tr>
    <tr><td colspan="2" align="right">$msg_select_file <span id="spanButtonPlaceholder1"></span> <input id="btnCancel1" type="button" value="$msg_cancel" onclick="upload1.cancelQueue();" disabled="disabled" style="width: 61px; margin: 0 0 0 2px; padding: 0; height: 22px; font-size: 8pt;" /></td></tr>
</table>
<div class="fieldset flash" id="fsUploadProgress1"></div>
EOF
        );
    }
}
