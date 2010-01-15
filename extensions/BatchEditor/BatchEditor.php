<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @author Stas Fomin <stas-fomin@yandex.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if (!defined('MEDIAWIKI')) die();

require_once('LinksUpdate.php');

$wgExtensionFunctions[] = "wfInitBatchEditor";

function wfInitBatchEditor()
{
    global $IP, $wgMessageCache;
    require_once("$IP/includes/SpecialPage.php");

    $wgMessageCache->addMessages(
        array(
            'specialpagename' => 'BatchEditor',
            'batcheditor' => 'Batch Editor',
        )
    );

    SpecialPage::addPage(new BatchEditorPage);
}

function wfSpecialBatchEditor($par = null)
{
    global $wgOut, $wgRequest, $wgTitle, $wgUser, $wgContLang;

    $wgOut->setPagetitle('BatchEditor');

    extract($wgRequest->getValues('a_titles'));
    extract($wgRequest->getValues('a_comment'));
    extract($wgRequest->getValues('a_find'));
    extract($wgRequest->getValues('a_replace'));
    extract($wgRequest->getValues('a_add'));
    extract($wgRequest->getValues('a_delete'));
    extract($wgRequest->getValues('a_run'));

    $a_minor  = $wgRequest->getCheck('a_minor') ? ' checked="checked" ' : false;
    $a_regexp = $wgRequest->getCheck('a_regexp') ? ' checked="checked" ' : false;
    $a_one    = $wgRequest->getCheck('a_one') ? ' checked="checked" ' : false;

    $parserOptions = ParserOptions::newFromUser( $wgUser );
    $numSessionID = preg_replace( "[\D]", "", session_id() );
    $action = $wgTitle->escapeLocalUrl("");

    ob_start();
?>
This page is intended to batch (mass) editing of [[{{SITENAME}}]] articles. Please, use this promptly!
<html>
<form action=<?="'$action'"?> method='POST'>
<table>
<tr valign="top">
    <td style="vertical-align: top; padding-right: 20px"><b>Articles:</b><br><small>Article titles,<br/> one per line</small></td>
    <td><textarea name="a_titles" rows="8" cols="60"><?=htmlspecialchars($a_titles)?></textarea></td>
</tr>
<tr valign="top">
    <td>Comment:</td>
    <td>
        <input name="a_comment" size="80" value="<?=htmlspecialchars($a_comment)?>" />
        <input type="checkbox" name="a_minor" <?=$a_minor?> value="1"/><label for='a_minor'>Minor&nbsp;Edit</label>
    </td>
</tr>
</table>
<table cellspacing="8">
<tr>
    <th>Find</th>
    <th>Replace</th>
</tr>
<tr>
    <td><textarea name="a_find"    rows="5" cols="45"><?=htmlspecialchars($a_find)?></textarea></td>
    <td><textarea name="a_replace" rows="5" cols="45"><?=htmlspecialchars($a_replace)?></textarea></td>
</tr>
<tr>
    <td colspan="2" align="right">
        <input type="checkbox" name="a_regexp" <?=$a_regexp?> value="1" />&nbsp;<label for='a_regexp'>Use <a href="http://en.wikipedia.org/wiki/PCRE">Perl-compatible regular expressions for replacement</a></label>
        &nbsp; &nbsp;
        <input type="checkbox" name="a_one" <?=$a_one?> value="1" />&nbsp;<label for='a_one'>Treat this as <b>one</b> multi-line replacement</label>
    </td>
</tr>
<tr>
    <th>Add lines</th>
    <th>Delete lines</th>
</tr>
<tr>
    <td><textarea name="a_add"    rows="5" cols="45"><?=htmlspecialchars($a_add)?></textarea></td>
    <td><textarea name="a_delete" rows="5" cols="45"><?=htmlspecialchars($a_delete)?></textarea></td>
</tr>
<tr><td align="center" colspan="2">
    <input value='Preview' type='submit' /> &nbsp; &nbsp;
    <input name='a_run' value='Run' type='submit' /> &nbsp; &nbsp;
</td></tr>
</table>
</form>
</html>
<?
    $interface_form = ob_get_contents();
    ob_end_clean();
    $wgOut->addWikiText($interface_form);
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
                $f = '#'.str_replace('#', '\\#', $f).'#';
            $a_find[] = array($f, trim($ar[$a]));
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
            $f = '#^[ \t\r]*'.str_replace('#', '\\#', preg_quote($f)).'[ \t\r]*$#m';
            $a_delete[] = $f;
        }
    }
    if (isset($a_titles))
    {
        if ($a_run)
            $wgOut->addWikiText("= Results =");
        else
            $wgOut->addWikiText("= Preview =");
        $arr_titles = split("\n", $a_titles);
        foreach($arr_titles as $s_title)
        {
            $s_title = trim($s_title);
            $title = Title::newFromURL($s_title);
            if (!$title)
                continue;
            $article = new Article($title);
            if ($article->mTitle->getArticleID() == 0)
                $wgOut->addWikiText("== [[$s_title]] ==\nArticle [[$s_title]] not found!");
            else
            {
                $article->loadContent(false);
                $oldtext = $article->mContent;
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
        <td colspan='2' width='50%' align='center' class='diff-otitle'>Old text</td>
        <td colspan='2' width='50%' align='center' class='diff-ntitle'>New text</td>
    </tr>
" . $res . "</table>";
                    $wgOut->addStyle('common/diff.css');
                    $wgOut->addWikiText("== [[$s_title]] ==");
                    $wgOut->addHTML($res);
                    if ($a_run)
                        $article->updateArticle($newtext, $a_comment, $a_minor, $article->mTitle->userIsWatching(), true);
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
    }
}

?>
