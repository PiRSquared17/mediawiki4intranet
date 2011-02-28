<?php

/* Copyright 2010+, Vitaliy Filippov <vitalif[d.o.g]mail.ru>
 *                  Stas Fomin <stas.fomin[d.o.g]yandex.ru>
 * This file is part of IntraACL MediaWiki extension. License: GPLv3.
 * http://wiki.4intra.net/IntraACL
 * $Id$
 *
 * Based on HaloACL
 * Copyright 2009, ontoprise GmbH
 *
 * The IntraACL-Extension is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The IntraACL-Extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * IntraACL toolbar for article edit mode.
 * On each article edit, there is a small toolbar at the top of the page with
 * a selectbox allowing to select desired page protection from Quick ACL list.
 */
class HACLToolbar
{
    /**
     * This method returns HTML code for the IntraACL toolbar,
     * for the $title editing mode.
     *
     * Looks like the following:
     * Page protection: <selectbox>. [Additional ACL ↓] [Used content ↓] [Edit ACL] ... [Manage Quick ACL]
     *
     * Options for selectbox:
     * - [no custom rights] - use only category/namespace rights
     * - [ACL:Page/XXX] - use custom ACL
     * - [Right 1] - use ACL template 1
     * - [Right 2] - use ACL template 2
     * - ...
     * ACL templates are detected using HACLSecurityDescriptor::isSinglePredefinedRightInclusion()
     * So if ACL:Page/XXX is really the inclusion of a single right template, it will be detected.
     */
    static function get($title)
    {
        global $wgUser, $wgRequest, $haclgContLang, $wgContLang,
            $haclgIP, $haclgHaloScriptPath, $wgScriptPath, $wgOut,
            $haclgOpenWikiAccess;

        $wgOut->addHeadItem('hacl_toolbar_js', '<script type="text/javascript" src="' . $haclgHaloScriptPath . '/scripts/HACL_Toolbar.js"></script>');
        $wgOut->addHeadItem('hacl_toolbar_css', '<link rel="stylesheet" type="text/css" media="screen, projection" href="'.$haclgHaloScriptPath.'/skins/haloacl_toolbar.css" />');

        $ns = $wgContLang->getNsText(HACL_NS_ACL);
        $canModify = true;
        $options = array(
            array('value' => 'unprotected', 'name' => wfMsg('hacl_toolbar_unprotected')),
        );

        if (!is_object($title))
            $title = Title::newFromText($title);

        if ($title->getNamespace() == HACL_NS_ACL)
            return '';

        // $found = "is current page SD in the list?"
        $found = false;

        // The list of ACLs which have effect on $title, but are not ACL:Page/$title by themselves
        // I.e. category and namespace ACLs
        $globalACL = array();

        if ($title->exists())
        {
            // Check SD modification rights
            $realPageSDId = $pageSDId = HACLSecurityDescriptor::getSDForPE($title->getArticleId(), HACLLanguage::PET_PAGE);
            if ($pageSDId)
            {
                $pageSD = HACLSecurityDescriptor::newFromId($pageSDId);
                $pageSDTitle = Title::newFromId($pageSDId);
                $canModify = HACLEvaluator::checkACLManager($pageSDTitle, $wgUser, HACLLanguage::RIGHT_EDIT);
                // Check if page SD is a single predefined right inclusion
                if ($single = $pageSD->isSinglePredefinedRightInclusion())
                {
                    $pageSDId = $single;
                    // But don't change $realPageSDId
                }
                else
                {
                    $found = true;
                    $options[] = array(
                        'current' => true,
                        'value' => $pageSDId,
                        'name' => $pageSDTitle->getFullText(),
                        'title' => $pageSDTitle->getFullText(),
                    );
                }
            }
            // Get categories which have SDs and to which belongs this article (for hint)
            foreach ($title->getParentCategories() as $p => $true)
            {
                list($unused, $cat) = explode(':', $p, 2);
                $id = Title::makeTitle(NS_CATEGORY, $cat)->getArticleId();
                if ($sdid = HACLSecurityDescriptor::getSDForPE($id, HACLLanguage::PET_CATEGORY))
                    $globalACL[] = Title::newFromId($sdid);
            }
        }

        // Add Quick ACLs
        $quickacl = HACLQuickacl::newForUserId($wgUser->getId());
        $default = $quickacl->getDefaultSD_ID();
        foreach ($quickacl->getSDs() as $sd)
        {
            try
            {
                // Check if the template is valid or corrupted by missing groups, user, ...
                // FIXME do no such check, simply remove SD definition from database when it is corrupted
                if ($sd->checkIntegrity() === true)
                {
                    $option = array(
                        'name'    => $sd->getPEName(),
                        'value'   => $sd->getSDId(),
                        'current' => $pageSDId == $sd->getSDId(),
                        'title'   => $ns.':'.$sd->getSDName(),
                    );
                    $found = $found || ($pageSDId == $sd->getSDId());
                    if ($default == $sd->getSDId())
                    {
                        // Always insert default SD as the second option
                        if (!$title->exists())
                            $option['current'] = true;
                        array_splice($options, 1, 0, array($option));
                    }
                    else
                        $options[] = $option;
                }
            } catch (HACLException $e) {}
        }

        // If page SD is not yet in the list, insert it as the second option
        if ($pageSDId && !$found)
        {
            $sd = HACLSecurityDescriptor::newFromId($pageSDId);
            array_splice($options, 1, 0, array(array(
                'name'    => $sd->getPEName(),
                'value'   => $sd->getSDId(),
                'current' => true,
                'title'   => $ns.':'.$sd->getSDName(),
            )));
        }

        // Alter selection using request data (haloacl_protect_with)
        if ($canModify && ($st = $wgRequest->getVal('haloacl_protect_with')))
        {
            foreach ($options as &$o)
                $o['current'] = $o['value'] == $st;
            unset($o); // prevent reference bugs
        }

        $selectedIndex = -1;
        foreach ($options as $i => $o)
            if ($o['current'])
                $selectedIndex = $i;

        // Check if page namespace has an ACL (for hint)
        if ($sdid = HACLSecurityDescriptor::getSDForPE($title->getNamespace(), HACLLanguage::PET_NAMESPACE))
            $globalACL[] = Title::newFromId($sdid);

        if ($globalACL)
        {
            foreach ($globalACL as &$t)
                if ($haclgOpenWikiAccess || $t->userCanReadEx())
                    $t = Xml::element('a', array('href' => $t->getLocalUrl(), 'target' => '_blank'), $t->getText());
            unset($t); // prevent reference bugs
            $globalACL = implode(', ', $globalACL);
        }

        // Check if the article does include any content
        if ($title->exists())
        {
            if (!$pageSDId)
                $pageSDId = '';
            $c = false;
            foreach ($wgRequest->getValues() as $k => $v)
            {
                if (substr($k, 0, 7) == 'sd_emb_' && $v !== "")
                {
                    $c = true;
                    break;
                }
            }
            if ($c)
            {
                // If there were any changes in the embedded content
                // toolbar, display it initially
                $embeddedToolbar = self::getEmbeddedHtml($title->getArticleId(), $realPageSDId);
            }
            else
            {
                // Else only check for imagelinks/templatelinks existence
                $dbr = wfGetDB(DB_SLAVE);
                $res = $dbr->query(
                    $dbr->selectSQLText('imagelinks', '1',
                        array('il_from' => $title->getArticleId()),
                        __METHOD__, array('GROUP BY' => 'il_from')) . ' UNION ' .
                    $dbr->selectSQLText('templatelinks', '1',
                        array('tl_from' => $title->getArticleId()),
                        __METHOD__, array('GROUP BY' => 'tl_from')),
                    __METHOD__
                );
                $res = $res->fetchObject();
                if ($res)
                    $anyLinks = true;
            }
        }

        // Link to Quick ACL manage page
        $quick_acl_link = Title::newFromText('Special:IntraACL')->getLocalUrl(array('action' => 'quickaccess'));

        // Run template
        ob_start();
        require(dirname(__FILE__).'/HACL_Toolbar.tpl.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * This method is called after an article has been saved.
     * This is the server side of IntraACL protection toolbar,
     * allowing to modify page SD together with article save.
     *
     * No modifications are made if:
     * - Page namespace is ACL
     * - User is anonymous
     * - Users don't have the right to modify page SD
     * - 'haloacl_protect_with' request value is invalid
     *   (valid are 'unprotected', or ID/name of predefined right or THIS page SD)
     *
     * @param Article $article
     *        The article which was saved
     * @param User $user
     *        The user who saved the article
     * @param string $text
     *        The content of the article
     *
     * @return true
     */
    public static function articleSaveComplete_SaveSD(&$article, &$user, $text)
    {
        global $wgUser, $wgRequest, $haclgContLang;

        if ($user->isAnon())
        {
            // Don't handle protection toolbar for anonymous users
            return true;
        }

        if ($article->getTitle()->getNamespace() == HACL_NS_ACL)
        {
            // Don't use protection toolbar for articles in the namespace ACL.
            // Note that embedded content protection toolbar is handled nevertheless.
            return true;
        }

        // Obtain user selection
        // haloacl_protect_with is an ID of SD/right or 'unprotected'
        $selectedSD = $wgRequest->getVal('hacl_protected_with');
        if ($selectedSD && $selectedSD != 'unprotected')
        {
            // Some SD is selected by the user
            // Ignore selection of invalid SDs
            if (''.intval($selectedSD) !== $selectedSD)
                $selectedSD = HACLSecurityDescriptor::idForSD($SDName);
        }

        if (!$selectedSD)
            return true;

        if ($selectedSD == 'unprotected')
            $selectedSD = NULL;

        // Check if current SD must be modified
        if ($article->exists())
            $pageSD = HACLSecurityDescriptor::getSDForPE($article->getId(), HACLLanguage::PET_PAGE);
        if (!$selectedSD && !$pageSD ||
            $selectedSD && $pageSD && $pageSD == $selectedSD)
            return true;

        // Check if other SD is a predefined right
        if ($selectedSD)
        {
            $sd = HACLStorage::getDatabase()->getSDByID($selectedSD);
            if ($sd->getPEType() != HACLLanguage::PET_RIGHT)
                return true;
        }

        // Check SD modification rights
        if ($pageSD)
        {
            list($r, $sd) = HACLEvaluator::checkACLManager(Title::newFromId($pageSD), $wgUser, 'edit');
            if (!$r)
                return true;
        }

        // Create an article object for the SD
        $newSDName = HACLSecurityDescriptor::nameOfSD($article->getTitle()->getFullText(), HACLLanguage::PET_PAGE);
        $etc = haclfDisableTitlePatch();
        $newSD = Title::newFromText($newSDName);
        haclfRestoreTitlePatch($etc);
        $newSDArticle = new Article($newSD);

        // Create/modify page SD
        if ($selectedSD)
        {
            $selectedSDTitle = Title::newFromId($selectedSD);
            $pf = $haclgContLang->getParserFunction(HACLLanguage::PF_PREDEFINED_RIGHT);
            $pfp = $haclgContLang->getParserFunctionParameter(HACLLanguage::PFP_RIGHTS);
            $content = '{{#'.$pf.': '.$pfp.' = '.$selectedSDTitle->getText().'}}';
            $newSDArticle->doEdit($content, wfMsg('hacl_comment_protect_with', $selectedSDTitle->getFullText()), EDIT_NEW);
        }
        // Remove page SD
        else
            $newSDArticle->doDelete(wfMsg('hacl_comment_unprotect'));

        // Continue hook processing
        return true;
    }

    /**
     * This method handles embedded content protection.
     * Must be set onto ArticleSaveComplete hook AFTER articleSaveComplete_SaveSD
     * in order to handle newly created page SDs.
     */
    public static function articleSaveComplete_SaveEmbedded(&$article, &$user, $text)
    {
        // Flag to prevent recursion
        static $InsideSaveEmbedded;
        if ($InsideSaveEmbedded)
            return true;
        $InsideSaveEmbedded = true;

        global $wgRequest, $wgOut, $haclgContLang, $wgUser;
        $st = HACLStorage::getDatabase();

        $isACL = $article->getTitle()->getNamespace() == HACL_NS_ACL;
        if ($isACL)
        {
            $articleSD = $st->getSDById($article->getId());
            if ($articleSD->getPEType() != 'page')
            {
                // This is not a page SD, do nothing.
                return true;
            }
        }
        else
        {
            // FIXME possibly use the category SD for category pages
            //       the problem here is that in ACL editor two different SDs
            //       may be created and queried for embedded content:
            //       for category article and for category itself
            $articleSD = HACLSecurityDescriptor::getSDForPE($article->getId(), HACLLanguage::PET_PAGE);
            if (!$articleSD)
                return true;
            else
                $articleSD = $st->getSDById($articleSD);
        }

        // Handle embedded content protection
        $errors = array();
        foreach ($wgRequest->getValues() as $k => $v)
        {
            if (substr($k, 0, 7) == 'sd_emb_' && $v)
            {
                $wgRequest->setVal($k, false); // clear value to handle embedded content only one time
                $emb_pe_id = intval(substr($k, 7));
                $emb_title = Title::newFromId($emb_pe_id);
                list($req_sd_id, $emb_sd_timestamp) = explode('-', $v, 2);
                if ($emb_title)
                {
                    $emb_sd_title = Title::newFromText(
                        $haclgContLang->getPetPrefix(HACLLanguage::PET_PAGE)
                        . '/' . $emb_title->getPrefixedText(),
                        HACL_NS_ACL
                    );
                    $emb_sd_article = new Article($emb_sd_title);
                }
                if (// Embedded content deleted
                    !$emb_title ||
                    // Manage access denied
                    !$emb_sd_title->userCan('edit') ||
                    // Invalid SD requested for protection
                    $req_sd_id && $req_sd_id != $articleSD->getSDId() ||
                    // SD created by someone in the meantime
                    !$emb_sd_timestamp && $emb_sd_article->exists() ||
                    // SD changed by someone in the meantime
                    $emb_sd_timestamp && $emb_sd_article->getTimestamp() > $emb_sd_timestamp)
                {
                    $errors[] = $emb_title;
                }
                else
                {
                    // Save embedded element SD
                    $emb_sd_article->doEdit(
                        '{{#predefined right: rights='.$articleSD->getSDName().'}}',
                        wfMsg('hacl_comment_protect_embedded', $articleSD->getSDName()),
                        EDIT_FORCE_BOT
                    );
                }
            }
        }

        // Display errors to the user, if any
        // This is safe to do as we are definitely in interactive non-batch edit mode
        if ($errors)
        {
            foreach ($errors as &$e)
                $e = "[[".$e->getPrefixedText()."]]";
            $wgOut->setTitle(Title::newFromText('Special:IntraACL'));
            $wgOut->addWikiText(wfMsgNoTrans(
                'hacl_embedded_not_saved',
                implode(", ", $errors),
                $article->getTitle()->getPrefixedText()
            ));
            $wgOut->setPageTitle(wfMsg('hacl_embedded_not_saved_title'));
            $wgOut->output();
            // FIXME terminate MediaWiki more correctly
            wfGetDB( DB_MASTER )->commit();
            exit;
        }

        // Clear flag and continue hook processing
        $InsideSaveEmbedded = false;
        return true;
    }

    // Get HTML code for linked content protection toolbar.
    // Used by ACL editor and IntraACL toolbar.
    // Handled by HACLToolbar::articleSaveComplete_SaveEmbedded.
    //
    // @param required int $peID - page ID to retrieve linked content from
    // @param optional int $sdID - page SD ID to check if SDs of linked content are already
    //     single inclusions of this SD.
    // @return html code for embedded content protection toolbar
    //     it containts checkboxes with names "sd_emb_$pageID" and values
    //     "$sdID-$ts". $sdID here is the passed $sdID and $ts is the modification
    //     timestamp of embedded element's SD, if it does exist.
    //     Value may be even just "-" when the toolbar was queried for article without SD,
    //     and when the embedded element did not have any SD.
    public function getEmbeddedHtml($peID, $sdID = '')
    {
        global $haclgContLang, $wgRequest;
        if (!$sdID)
            $sdID = '';
        $st = HACLStorage::getDatabase();
        // Retrieve the list of templates used on the page with id=$peID
        $templatelinks = $st->getEmbedded($peID, $sdID, 'templatelinks');
        // Retrieve the list of images used on the page
        $imagelinks = $st->getEmbedded($peID, $sdID, 'imagelinks');
        // Build HTML code for embedded content toolbar
        $links = array_merge($templatelinks, $imagelinks);
        $html = array();
        $all = array();
        foreach ($links as $link)
        {
            $id = $link['title']->getArticleId();
            $href = $link['title']->getLocalUrl();
            $t = $link['title']->getPrefixedText();
            $ts = $link['sd_touched'];
            if ($prev = $wgRequest->getVal("sd_emb_$id"))
                list($unused, $ts) = explode($prev, '/', 2);
            if ($link['sd_title'])
            {
                if ($link['sd_single'])
                {
                    // Already protected by page SD
                    $customprot = wfMsgForContent('hacl_toolbar_emb_already_prot');
                }
                else
                {
                    // Custom SD defined
                    $customprot = wfMsgForContent('hacl_toolbar_emb_custom_prot', $link['sd_title']->getLocalUrl());
                }
            }
            else
                $customprot = '';
            if ($link['used_on_pages'] > 1)
            {
                $usedon = Title::newFromText("Special:WhatLinksHere/$t")->getLocalUrl(array('hidelinks' => 1));
                $usedon = wfMsgForContent('hacl_toolbar_used_on', $link['used_on_pages'], $usedon);
            }
            else
                $usedon = '';
            $P = $customprot || $usedon ? " — " : "";
            $S = $customprot && $usedon ? "; " : "";
            // [x] Title — custom SD defined; used on Y pages
            $h = '<input type="checkbox" id="sd_emb_'.$id.'" name="sd_emb_'.$id.'"'.
                ($link['sd_single']
                    ? ' value="" checked="checked" disabled="disabled"'
                    : " value=\"$sdID-$ts\" onchange=\"hacle_noall(this)\" onclick=\"hacle_noall(this)\"".
                      ($prev ? ' checked="checked"' : '')).
                ' />'.
                ' <label for="sd_emb_'.$id.'"><a target="_blank" href="'.htmlspecialchars($href).'">'.
                htmlspecialchars($t).'</a></label>'.$P.$customprot.$S.$usedon;
            $h = '<div class="hacl_embed'.($link['sd_single'] ? '_disabled' : '').'">'.$h.'</div>';
            $html[] = $h;
            if (!$link['sd_single'])
                $all[] = $id;
        }
        if ($all)
        {
            $html[] = '<div class="hacl_embed"><input type="checkbox" id="hacl_emb_all" onchange="hacle_checkall(this, ['.
                implode(',',$all).'])" onclick="hacle_checkall(this, ['.implode(',',$all).'])" /> '.
                wfMsg('hacl_toolbar_emb_all').'</div>';
        }
        elseif ($html)
        {
            $html[] = '<div class="hacl_embed_disabled"><input type="checkbox" disabled="disabled" checked="checked" /> '.
                wfMsg('hacl_toolbar_emb_all_already').'</div>';
        }
        if ($html)
            array_unshift($html, '<div class="hacl_emb_text">'.wfMsgForContent('hacl_toolbar_protect_embedded').'</div>');
        $html = implode("\n", $html);
        return $html;
    }

    // Hook for displaying "ACL" tab for standard skins
    static function SkinTemplateContentActions(&$actions)
    {
        if ($act = self::getContentAction())
            array_splice($actions, 1, 0, array($act));
        return true;
    }

    // Hook for displaying "ACL" tab for Vector skin
    static function SkinTemplateNavigation(&$skin, &$links)
    {
        if ($act = self::getContentAction())
            array_splice($links['namespaces'], 1, 0, array($act));
        return true;
    }

    // Returns content-action for inserting into skin tabs
    static function getContentAction()
    {
        global $wgTitle, $haclgContLang, $haclgDisableACLTab, $wgUser;
        if ($wgUser->isAnon())
            return NULL;
        if ($wgTitle->getNamespace() == HACL_NS_ACL)
        {
            // Display the link to article or category
            list($peName, $peType) = HACLSecurityDescriptor::nameOfPE($wgTitle->getText());
            if ($peType == 'page' || $peType == 'category')
            {
                $title = Title::newFromText($peName);
                return array(
                    'class' => false,
                    'text'  => wfMsg("hacl_tab_$peType"),
                    'href'  => $title->getLocalUrl(),
                );
            }
        }
        elseif ($wgTitle->exists())
        {
            // Display the link to category or page SD
            if ($wgTitle->getNamespace() == NS_CATEGORY)
                $sd = $haclgContLang->getPetPrefix(HACLLanguage::PET_CATEGORY).
                    '/'.$wgTitle->getText();
            else
                $sd = $haclgContLang->getPetPrefix(HACLLanguage::PET_PAGE).
                    '/'.$wgTitle->getPrefixedText();
            $sd = Title::newFromText($sd, HACL_NS_ACL);
            // Hide ACL tab if SD does not exist and $haclgDisableACLTab is true
            if ($haclgDisableACLTab && !$sd->exists())
                return NULL;
            return array(
                'class' => $sd->exists() ? false : 'new',
                'text'  => wfMsg('hacl_tab_acl'),
                'href'  => $sd->getLocalUrl(),
            );
        }
        return NULL;
    }
}
