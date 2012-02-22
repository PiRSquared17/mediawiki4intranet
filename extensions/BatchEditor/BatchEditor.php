<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @author Stas Fomin <stas-fomin@yandex.ru>
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if (!defined('MEDIAWIKI'))
    die('Not an entry point');

$wgExtensionFunctions[] = "wfInitBatchEditor";
$wgExtensionMessagesFiles['BatchEditor'] = dirname(__FILE__) . '/BatchEditor.i18n.php';
$wgSpecialPageGroups['BatchEditor'] = 'pagetools';

function wfInitBatchEditor()
{
    global $IP;
    require_once("$IP/includes/SpecialPage.php");
    SpecialPage::addPage(new BatchEditorPage);
}

function wfSpecialBatchEditor($par = null)
{
    global $wgOut, $wgRequest, $wgTitle, $wgUser, $wgLang, $IP, $wgScriptPath, $wgVersion;

    $wgOut->setPageTitle(wfMsg('batcheditor-title'));

    $a_titles  = $wgRequest->getVal('a_titles');
    $a_comment = $wgRequest->getVal('a_comment');
    $a_find    = $wgRequest->getVal('a_find');
    $a_replace = $wgRequest->getVal('a_replace');
    $a_add     = $wgRequest->getVal('a_add');
    $a_delete  = $wgRequest->getVal('a_delete');
    $a_run     = $wgRequest->getVal('a_run');
    $a_preview = $wgRequest->getVal('a_preview');

    $a_create = $wgRequest->getCheck('a_create') ? ' checked="checked" ' : false;
    $a_minor  = $wgRequest->getCheck('a_minor') ? ' checked="checked" ' : false;
    $a_regexp = $wgRequest->getCheck('a_regexp') ? ' checked="checked" ' : false;
    $a_one    = $wgRequest->getCheck('a_one') ? ' checked="checked" ' : false;

    /* Is Mediawiki4Intranet Import/Export engine available? */
    require_once("$IP/includes/specials/SpecialExport.php");
    $mw4i_addpages = NULL;
    if (function_exists('wfExportAddPagesExec'))
        $mw4i_addpages = array('wfExportAddPagesExec', 'wfExportAddPagesForm');
    elseif (method_exists('SpecialExport', 'addPagesExec'))
        $mw4i_addpages = array(
            array('SpecialExport', 'addPagesExec'),
            array('SpecialExport', 'addPagesForm'),
        );
    if ($mw4i_addpages)
    {
        $state = $_POST;
        $state['pages'] = $a_titles;
        if ($wgRequest->getCheck('addcat'))
        {
            call_user_func_array($mw4i_addpages[0], array(&$state));
            $a_titles = $state['pages'];
        }
    }

    $parserOptions = ParserOptions::newFromUser( $wgUser );
    $numSessionID = preg_replace( "[\D]", "", session_id() );
    $action = $wgTitle->escapeLocalUrl("");

    ob_start();
?>
<form action='<?=$action?>' method='POST'>
<table>
<tr valign="top">
    <td style="vertical-align: top; padding-right: 20px"><?=wfMsgExt('batcheditor-list-title', array('parseinline'))?></td>
    <td><textarea name="a_titles" rows="8" cols="60"><?=htmlspecialchars($a_titles)?></textarea></td>
    <td rowspan="3" style="padding-left: 16px"><?= $mw4i_addpages ? call_user_func($mw4i_addpages[1], $state) : '' ?></td>
</tr>
<tr valign="top">
    <td><?=wfMsgExt('batcheditor-comment-title', array('parseinline'))?></td>
    <td>
        <input name="a_comment" style="width: 100%" value="<?=htmlspecialchars($a_comment)?>" /><br />
        <input type="checkbox" name="a_minor" id="a_minor" <?=$a_minor?> value="1"/><label for="a_minor"><?=wfMsg('minoredit')?></label>
        <input type="checkbox" name="a_create" id="a_create" <?=$a_create?> value="1"/><label for="a_create"><?=wfMsg('batcheditor-create')?></label>
    </td>
</tr>
<tr>
    <td colspan="2">

<table cellspacing="8" style="border-collapse: separate">
<tr>
    <th><?=wfMsgExt('batcheditor-find', array('parseinline'))?></th>
    <th><?=wfMsgExt('batcheditor-replace', array('parseinline'))?></th>
</tr>
<tr>
    <td><textarea name="a_find" rows="5" cols="45">
<?=htmlspecialchars($a_find)?>
</textarea></td>
    <td><textarea name="a_replace" rows="5" cols="45">
<?=htmlspecialchars($a_replace)?>
</textarea></td>
</tr>
<tr>
    <td colspan="2" align="right">
        <input type="checkbox" name="a_regexp" id="a_regexp" <?=$a_regexp?> value="1" />&nbsp;<label for='a_regexp'><?=wfMsgExt('batcheditor-pcre', array('parseinline'))?></label>
        &nbsp; &nbsp;
        <input type="checkbox" name="a_one" id="a_one" <?=$a_one?> value="1" />&nbsp;<label for='a_one'><?=wfMsgExt('batcheditor-oneline', array('parseinline'))?></label>
    </td>
</tr>
<tr>
    <th><?=wfMsgExt('batcheditor-addlines', array('parseinline'))?></th>
    <th><?=wfMsgExt('batcheditor-deletelines', array('parseinline'))?></th>
</tr>
<tr>
    <td><textarea name="a_add"    rows="5" cols="45"><?=htmlspecialchars($a_add)?></textarea></td>
    <td><textarea name="a_delete" rows="5" cols="45"><?=htmlspecialchars($a_delete)?></textarea></td>
</tr>
<tr><td align="center" colspan="2">
    <input name='a_preview' value='<?=wfMsgExt('batcheditor-preview', array('parseinline'))?>' type='submit' /> &nbsp; &nbsp;
    <input name='a_run' value='<?=wfMsgExt('batcheditor-run', array('parseinline'))?>' type='submit' /> &nbsp; &nbsp;
</td></tr>
</table>

    </td>
</tr>
</table>
</form>
<?php
    $interface_form = ob_get_contents();
    ob_end_clean();
    $wgOut->addExtensionStyle($wgScriptPath . '/extensions/BatchEditor/BatchEditor.css');
    $wgOut->addHTML($interface_form);
    # Remove CRLF
    $a_find    = str_replace("\r", "", $a_find);
    $a_replace = str_replace("\r", "", $a_replace);
    $a_add     = str_replace("\r", "", $a_add);
    $a_delete  = str_replace("\r", "", $a_delete);
    # One replacement or multiple?
    if ($a_one)
    {
        $af = array($a_find);
        $ar = array($a_replace);
    }
    else
    {
        $af = explode("\n", $a_find);
        $ar = explode("\n", $a_replace);
    }
    # Replacements
    $a_find = array();
    foreach ($af as $a => $f)
    {
        $f = trim($f);
        if ($f)
        {
            if ($a_regexp)
                $f = '#'.str_replace('#', "\\#", $f).'#'.($a_one ? 's' : '');
            $a_find[] = array($f, $ar[$a]);
        }
    }
    # Regexps for deleting whole lines
    $af = explode("\n", $a_delete);
    $a_delete = array();
    foreach ($af as $a => $f)
    {
        $f = trim($f);
        if ($f)
        {
            $f = '#^[ \t\r]*'.str_replace('#', "\\#", preg_quote($f)).'[ \t\r]*$#m';
            $a_delete[] = $f;
        }
    }
    if (trim($a_titles) != '' && ($a_run || $a_preview))
    {
        $wgOut->addWikiText(wfMsg('batcheditor-'.($a_run?'results':'preview').'-page'));
        $arr_titles = explode("\n", $a_titles);
        foreach($arr_titles as $s_title)
        {
            $s_title = trim($s_title);
            $title = Title::newFromText($s_title);
            if (!$title)
                continue;
/*patch|2011-03-01|IntraACL|start*/
            if (method_exists($title, 'userCanReadEx') && !$title->userCanReadEx())
                continue;
/*patch|2011-03-01|IntraACL|end*/
            $article = new Article($title);
            $oldtext = '';
            if ($article->exists())
            {
                $article->loadContent(false);
                $oldtext = $article->mContent;
            }
            elseif (!$a_create)
            {
                $wgOut->addWikiText("== [[$s_title]] ==\n".wfMsg('batcheditor-not-found', $s_title));
                continue;
            }
            $newtext = $oldtext;
            if (count($a_find))
            {
                $cb = $a_regexp ? 'preg_replace' : 'str_replace';
                foreach ($a_find as $f)
                    $newtext = call_user_func($cb, $f[0], $f[1], $newtext);
            }
            if (count($a_delete))
                foreach ($a_delete as $s)
                    $newtext = preg_replace($s, "", $newtext);
            if (@trim($a_add))
                $newtext .= "\n" . $a_add;
            # Preview or run only if new text differs
            if ($newtext != $oldtext)
            {
                $oldrev = wfMsgHTML('revisionasof', $wgLang->timeanddate($article->getTimestamp(), true));
                $newrev = wfMsg('yourtext');
                if (version_compare($wgVersion, '1.18') >= 0)
                    $wgOut->addModules('mediawiki.action.history.diff');
                else
                    $wgOut->addStyle('common/diff.css');
                $wgOut->addWikiText("== [[$s_title]] ==");
                $canedit = $article->getTitle()->userCan('edit');
                if (!$canedit)
                    $wgOut->addWikiText('<div style="color:red">\'\'\''.wfMsg('batcheditor-edit-denied').'\'\'\'</div>');
                elseif ($a_run)
                {
                    $flags = EDIT_DEFER_UPDATES | EDIT_AUTOSUMMARY;
                    if ($a_minor)
                        $flags |= EDIT_MINOR;
                    $st = false;
                    if (($st = $article->doEdit($newtext, $a_comment, $flags)) && $st->isGood())
                        $newrev = wfMsgHTML('currentrev-asof', $wgLang->timeanddate($st->value['revision']->getTimestamp(), true));
                    else
                    {
                        $msg = wfMsg('batcheditor-edit-error');
                        if ($st)
                            $msg .= ': ' . $st->getWikiText();
                        else
                            $msg .= '.';
                        $wgOut->addWikiText('<div style="color:red">\'\'\'' . $msg . '\'\'\'</div>');
                    }
                }
                if (!$a_run || $canedit)
                {
                    $de = new DifferenceEngine();
                    $de->setText($oldtext, $newtext);
                    $res = $de->getDiffBody();
                    $res = "
<table class='diff'>
    <col class='diff-marker' />
    <col class='diff-content' />
    <col class='diff-marker' />
    <col class='diff-content' />
    <tr>
        <td colspan='2' width='50%' align='center' class='diff-otitle'>$oldrev</td>
        <td colspan='2' width='50%' align='center' class='diff-ntitle'>$newrev</td>
    </tr>
" . $res . "</table>";
                    $wgOut->addHTML($res);
                }
            }
        }
    }
}

class BatchEditorPage extends SpecialPage
{
    function BatchEditorPage()
    {
        SpecialPage::SpecialPage('BatchEditor', 'edit');
        wfLoadExtensionMessages('BatchEditor');
    }
}
