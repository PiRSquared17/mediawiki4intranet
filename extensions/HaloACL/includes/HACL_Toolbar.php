<?php

/* Copyright 2010+, Vitaliy Filippov <vitalif[d.o.g]mail.ru>
 *                  Stas Fomin <stas.fomin[d.o.g]yandex.ru>
 * This file is part of heavily modified "Web 1.0" HaloACL-extension.
 * http://wiki.4intra.net/Mediawiki4Intranet
 * $Id: $
 *
 * Copyright 2009, ontoprise GmbH
 *
 * The HaloACL-Extension is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The HaloACL-Extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * HaloACL toolbar for article edit mode.
 * On each article edit, there is a small toolbar at the top of the page with
 * a selectbox allowing to select desired page protection from Quick ACL list.
 */
class HACLToolbar
{
    /**
     * This method returns HTML code for the HaloACL toolbar,
     * for the $title editing mode.
     *
     * Page protection: <selectbox>. Also using: [[ACL:Namespace/Main]], [[ACL:Category/XX]].
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

        // $found = "is current page SD in the list?"
        $found = false;

        // The list of ACLs which have effect on $title, but are not ACL:Page/$title by themselves
        // I.e. category and namespace ACLs
        $globalACL = array();

        if ($title->exists())
        {
            // Check SD modification rights
            $pageSDId = HACLSecurityDescriptor::getSDForPE($title->getArticleId(), HACLLanguage::PET_PAGE);
            if ($pageSDId)
            {
                $pageSD = HACLSecurityDescriptor::newFromId($pageSDId);
                $pageSDTitle = Title::newFromId($pageSDId);
                $canModify = HACLEvaluator::checkACLManager($pageSDTitle, $wgUser, HACLLanguage::RIGHT_EDIT);
                // Check if page SD is a single predefined right inclusion
                if ($single = $pageSD->isSinglePredefinedRightInclusion())
                    $pageSDid = $single;
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

        $quick_acl_link = Title::newFromText('Special:HaloACL')->getLocalUrl(array('action' => 'quickaccess'));

        // Run template
        ob_start();
        require(dirname(__FILE__).'/HACL_Toolbar.tpl.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * This method is called, after an article has been saved.
     * This is "server-side" of HaloACL protection toolbar, allowing to modify page
     * security descriptors on article save.
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
    public static function articleSaveComplete(&$article, &$user, $text)
    {
        global $wgUser, $wgRequest, $haclgContLang;

        if ($article->getTitle()->getNamespace() == HACL_NS_ACL)
        {
            // Don't create default SDs for articles in the namespace ACL
            return true;
        }

        if ($user->isAnon())
        {
            // Don't create default SDs for anonymous users
            return true;
        }

        // Obtain user selection
        // haloacl_protect_with is an ID of SD/right or 'unprotected'
        $selectedSD = $wgRequest->getVal('haloacl_protect_with');
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
}
