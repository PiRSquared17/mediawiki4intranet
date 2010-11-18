<?php

/* This extension adds a textbox and a button to category pages, allowing
 * to easily create pages inside the category. A template with name
 * Template:Category:CATEGORY_NAME is used as a stub for the new page.
 *
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
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
    'author'      => 'Vitaliy Filippov, based on Liang Chen The BiGreat\'s extension', # http://www.liang-chen.com/myworld/content/view/36/70/
    'url'         => 'http://lib.custis.ru/index.php/CategoryTemplate_(MediaWiki)',
    'version'     => '1.0 (2009-03-25)',
);

function wfCategoryTemplate()
{
    global $wgHooks;
    wfLoadExtensionMessages('CategoryTemplate');
    $wgHooks['CategoryPageView'][] = 'efCategoryTemplateCategoryPageView';
}

function efCategoryTemplateCategoryPageView($catpage)
{
    global $wgOut, $wgScript, $wgContLang, $wgCanonicalNamespaceNames, $CategoryTemplateMessages;
    $boxtext = addslashes(wfMsg("addcategorytemplate-create-article"));
    $btext = addslashes(wfMsg("addcategorytemplate-submit"));
    $confirmtext = addslashes(wfMsg("addcategorytemplate-confirm"));
    $Action = htmlspecialchars($wgScript);
    $s1 = addslashes($wgCanonicalNamespaceNames[NS_IMAGE]);
    $s2 = addslashes($wgContLang->getNsText(NS_IMAGE));
    $cat = $catpage->mTitle->getText();
    if (($title = Title::newFromText($wgContLang->getNsText(NS_TEMPLATE).":".$wgContLang->getNsText(NS_CATEGORY).":".$cat)) &&
        (!method_exists($title, 'userCanReadEx') || $title->userCanReadEx()) &&
        ($rev = Revision::newFromId($title->getLatestRevID())))
    {
        /* Fetch page template from Template:Category:CATEGORY_NAME */
        $text = $rev->getText();
    }
    else
    {
        /* Default page template: add only category membership */
        $text = "\n\n\n[[".$wgContLang->getNsText(NS_CATEGORY).":$cat]]";
    }
    /* Transform _subst: into subst:. This allows to use {{_subst:MAGICWORD}} inside
       the template without it being transformed instantly. */
    $text = str_replace("{{_subst:", "{{subst:", $text);
    $text = str_replace("\n", "\\n", addslashes($text));
    $temp2 = <<<ENDFORM
<!-- Add Article Extension Start -->
<script type="text/javascript">
function clearText(thefield) { if (thefield.defaultValue==thefield.value) thefield.value="" }
function addText(thefield) { if (thefield.value=="") thefield.value=thefield.defaultValue }
function checkname()
{
    var inp = document.getElementById('createboxInput');
    var l = 0;
    if (inp.value.substr(0, l = "$s1:".length) != "$s1:")
    {
        l = 0;
        if (inp.value.substr(0, l = "$s2:".length) != "$s2:")
            l = 0;
    }
    if (l)
    {
        document.createbox.wpDestFile.value = inp.value.substr(l);
        document.createbox.wpUploadDescription.value = "$text";
        document.createbox.wpTextbox1.value = "";
        inp.value = 'Special:Upload';
    }
    else
    {
        document.createbox.wpUploadDescription.value = "";
        document.createbox.wpTextbox1.value = "$text";
    }
    return inp.value!=inp.defaultValue || confirm("$confirmtext".replace("%s", document.createbox.createboxInput.value));
}
</script>
<table border="0" align="right" width="423" cellspacing="0" cellpadding="0">
<tr><td width="100%" align="right" bgcolor="">
<form name="createbox" action="{$Action}" method="POST" class="createbox" onsubmit="return checkname()">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="wpDestFile" value="">
    <input type="hidden" name="wpUploadDescription" value="">
    <input type="hidden" name="wpTextbox1" value="">
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
