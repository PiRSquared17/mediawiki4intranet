<?php

/**
 * MediaWiki CategoryTemplate extension
 *
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 * This extension adds a textbox and a button to category pages, allowing
 * to easily create pages inside the category. A template with name
 * Template:Category:CATEGORY_NAME is used as a stub for the new page.
 * <!-- default title = ... --> is matched and removed from that template,
 * and the value is placed into new page title textbox by default. Also,
 * optional '$' inside the value indicates cursor position.
 * I.e. you can specify <!-- default title = Prefix $ (suffix) -->,
 * and the initial page title will be "Prefix  (suffix)" with cursor placed
 * between two spaces.
 *
 * Inspired by Liang Chen The BiGreat\'s extension:
 * http://www.liang-chen.com/myworld/content/view/36/70/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

if (!defined('MEDIAWIKI'))
    die();

$wgExtensionFunctions[] = "wfCategoryTemplate";
$wgExtensionMessagesFiles['CategoryTemplate'] = dirname(__FILE__) . '/CategoryTemplate.i18n.php';
$wgExtensionCredits['other'][] = array (
    'name'        => 'Add Article to Category with template',
    'description' => 'Your MediaWiki will get an inputbox on each Category page, and you can create a new article directly to that category, based on Template:Category:<category_name> or simple [[Category:<category_name>]] string',
    'author'      => 'Vitaliy Filippov',
    'url'         => 'http://wiki.4intra.net/CategoryTemplate_(MediaWiki)',
    'version'     => '1.1 (2011-05-03)',
);

function wfCategoryTemplate()
{
    global $wgHooks;
    wfLoadExtensionMessages('CategoryTemplate');
    $wgHooks['CategoryPageView'][] = 'efCategoryTemplateCategoryPageView';
}

function efCategoryTemplateCategoryPageView($catpage)
{
    global $wgOut, $wgScript, $wgParser, $wgContLang, $wgCanonicalNamespaceNames, $CategoryTemplateMessages;
    $boxtext = addslashes(wfMsg("addcategorytemplate-create-article"));
    $btext = addslashes(wfMsg("addcategorytemplate-submit"));
    $confirmtext = addslashes(wfMsg("addcategorytemplate-confirm"));
    $Action = htmlspecialchars($wgScript);
    $s1 = addslashes($wgCanonicalNamespaceNames[NS_IMAGE]);
    $s2 = addslashes($wgContLang->getNsText(NS_IMAGE));
    $cat = $catpage->mTitle->getText();
    $deftitle = $makedefpos = '';
    if (($title = Title::newFromText($wgContLang->getNsText(NS_TEMPLATE).":".$wgContLang->getNsText(NS_CATEGORY).":".$cat)) &&
        (!method_exists($title, 'userCanReadEx') || $title->userCanReadEx()) &&
        ($rev = Revision::newFromId($title->getLatestRevID())))
    {
        /* Fetch page template from Template:Category:CATEGORY_NAME */
        $text = $rev->getText();
        if (preg_match('/<!--\s*default\s*title\s*=\s*(.*?)-->\s*/is', $text, $m, PREG_OFFSET_CAPTURE))
        {
            /* Match and remove <!-- default title = ... --> from template source.
               This value will be placed into the page title editbox by default.
               Optional '$' inside it indicates cursor position. */
            $deftitle = addslashes(trim($m[1][0]));
            if (($defpos = strpos($deftitle, '$')) !== false)
            {
                $deftitle = substr($deftitle, 0, $defpos) . substr($deftitle, $defpos+1);
                $makedefpos = 'f.selectionStart='.$defpos.'; f.selectionEnd=0; ';
            }
            $text = substr($text, 0, $m[0][1]) . substr($text, $m[0][1]+strlen($m[0][0]));
        }
    }
    else
    {
        /* Default page template: add only category membership */
        $text = "\n\n\n[[".$wgContLang->getNsText(NS_CATEGORY).":$cat]]";
    }
    $text = str_replace("\n", "\\n", addslashes($text));
    $temp2 = <<<ENDFORM
<!-- Add Article Extension Start -->
<script type="text/javascript">
function clearText(f) { if (f.defaultValue == f.value) { f.value = "$deftitle"; $makedefpos} }
function addText(f) { if (f.value == "$deftitle" || f.value == "") f.value = f.defaultValue; }
function checkname()
{
    var inp = document.getElementById('createboxInput');
    var l = 0;
    var txt = "$text";
    txt = txt.replace(/__FULLPAGENAME__/g, inp.value);
    if (inp.value.substr(0, l = "$s1:".length) != "$s1:")
    {
        l = 0;
        if (inp.value.substr(0, l = "$s2:".length) != "$s2:")
            l = 0;
    }
    if (l)
    {
        document.createbox.wpDestFile.value = inp.value.substr(l);
        document.createbox.wpUploadDescription.value = txt;
        document.createbox.wpTextbox1.value = "";
        inp.value = 'Special:Upload';
    }
    else
    {
        document.createbox.wpUploadDescription.value = "";
        document.createbox.wpTextbox1.value = txt;
    }
    return inp.value != inp.defaultValue || confirm("$confirmtext".replace("%s", document.createbox.createboxInput.value));
}
</script>
<table border="0" align="right" width="423" cellspacing="0" cellpadding="0">
<tr><td width="100%" align="right" bgcolor="">
<form name="createbox" action="{$Action}" method="POST" class="createbox" onsubmit="return checkname()">
    <input type="hidden" name="action" value="edit" />
    <input type="hidden" name="wpDestFile" value="" />
    <input type="hidden" name="wpUploadDescription" value="" />
    <input type="hidden" name="wpTextbox1" value="" />
    <input id="createboxInput" class="createboxInput" name="title" type="text" value="$boxtext" size="30" style="color:#666;" onfocus="clearText(this);" onblur="addText(this);" />
    <input type="submit" name="create" class="createboxButton" value="$btext" />
</form>
</td></tr>
</table>
<!-- Add Article Extension End -->
ENDFORM;
    $wgOut->addHTML($temp2);
    return true;
}
