<?php

/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if (!defined('MEDIAWIKI'))
    die();

$wgExtensionFunctions[] = "wfCategoryTemplate";
$wgExtensionMessagesFiles[CategoryTemplate] = dirname(__FILE__) . '/CategoryTemplate.i18n.php';
$wgExtensionCredits[other][] = array (
    name        => 'Add Article to Category with template',
    description => 'Your MediaWiki will get an inputbox on each Category page, and you can create a new article directly to that category, based on Template:Category:<category_name> or simple [[Category:<category_name>]] string',
    author      => 'Vitaliy Filippov, based on Liang Chen The BiGreat\'s extension', # http://www.liang-chen.com/myworld/content/view/36/70/
    url         => 'http://lib.custis.ru/index.php/AddCategoryTemplate',
    version     => '1.0 (2009-03-25)',
);

function wfCategoryTemplate()
{
    global $wgHooks;
    wfLoadExtensionMessages('CategoryTemplate');
    $wgHooks[EditFormPreloadText][] = '_cattemplateaddcategory';
    $wgHooks[CategoryPageView][] = '_cattemplatecategorychange';
}

function _cattemplateaddcategory(&$text)
{
    global $wgLang;
    $cname = $_GET[_category];
    $wnew = $_GET[_new];
    $namespaceNames = $wgLang->getNamespaces();
    if ($wnew == 1)
    {
        if (($title = Title::newFromText($namespaceNames[NS_TEMPLATE].":".$namespaceNames[NS_CATEGORY].":$cname")) &&
            ($rev = Revision::newFromId($title->getLatestRevID())))
            $text = $rev->getText();
        else
            $text = "\n\n\n[[".$namespaceNames[NS_CATEGORY].":$cname]]";
    }
    return true;
}

function _cattemplatecategorychange($catpage)
{
    global $wgOut, $wgScript, $CategoryTemplateMessages;
    $boxtext = addcslashes(wfMsg("addcategorytemplate-create-article"));
    $btext = addcslashes(wfMsg("addcategorytemplate-submit"));
    $confirmtext = addcslashes(wfMsg("addcategorytemplate-confirm"));
    $Action = htmlspecialchars($wgScript);
    $temp2 = <<<ENDFORM
<!-- Add Article Extension Start -->
<script type="text/javascript">
function clearText(thefield) { if (thefield.defaultValue==thefield.value) thefield.value="" }
function addText(thefield) { if (thefield.value=="") thefield.value=thefield.defaultValue }
function checkname() { return document.createbox.createboxInput.value!=document.createbox.createboxInput.defaultValue || confirm("$confirmtext".replace("%s", document.createbox.createboxInput.value)); }
</script>
<table border="0" align="right" width="423" cellspacing="0" cellpadding="0">
<tr><td width="100%" align="right" bgcolor="">
<form name="createbox" action="{$Action}" method="get" class="createbox" onsubmit="return checkname()">
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="_new" value="1">
    <input type="hidden" name="_category" value="{$catpage->mTitle->getText()}">
    <input class="createboxInput" name="title" type="text" value="{$boxtext}" size="30" style="color:#666;" onfocus="clearText(this);" onblur="addText(this);" />
    <input type="submit" name="create" class="createboxButton" value="{$btext}" />
</form>
</td></tr>
</table>
<!-- Add Article Extension End -->
ENDFORM;
    $wgOut->addHTML($temp2);
    return true;
}
