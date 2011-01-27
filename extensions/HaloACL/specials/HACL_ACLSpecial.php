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
 * A special page for defining and managing Access Control Lists.
 *
 * @author Thomas Schweitzer
 */

if (!defined('MEDIAWIKI'))
    die();

class HaloACLSpecial extends SpecialPage
{
    static $actions = array(
        'acllist'     => 1,
        'acl'         => 1,
        'quickaccess' => 1,
        'grouplist'   => 1,
        'group'       => 1,
        'whitelist'   => 1,
    );

    var $aclTargetTypes = array(
        'protect' => array('page' => 1, 'namespace' => 1, 'category' => 1, 'property' => 1),
        'define' => array('right' => 1, 'template' => 1),
    );

    /* Identical to Xml::element, but does no htmlspecialchars() on $contents */
    static function xelement($element, $attribs = null, $contents = '', $allowShortTag = true)
    {
        if (is_null($contents))
            return Xml::openElement($element, $attribs);
        elseif ($contents == '')
            return Xml::element($element, $attribs, $contents, $allowShortTag);
        return Xml::openElement($element, $attribs) . $contents . Xml::closeElement($element);
    }

    public function __construct()
    {
        if (!defined('SMW_NS_PROPERTY'))
        {
            $this->hasProp = false;
            unset($this->aclTargetTypes['protect']['property']);
        }
        parent::__construct('HaloACL');
    }

    public function execute()
    {
        global $wgOut, $wgRequest, $wgUser;
        $q = $wgRequest->getValues();
        if ($wgUser->isLoggedIn())
        {
            wfLoadExtensionMessages('HaloACL');
            $wgOut->setPageTitle(wfMsg('hacl_special_page'));
            if (!self::$actions[$q['action']])
                $q['action'] = 'acllist';
            $f = 'html_'.$q['action'];
            $this->$f($q);
        }
        else
            $wgOut->showErrorPage('hacl_login_first_title', 'hacl_login_first_text');
    }

    public function html_acllist(&$q)
    {
        global $wgOut, $wgUser, $wgScript, $haclgHaloScriptPath, $haclgContLang;
        $aclOwnTemplate = HACLStorage::getDatabase()->getSDForPE($wgUser->getId(), 'template');
        if ($aclOwnTemplate)
            $aclOwnTemplate = HACLSecurityDescriptor::newFromId($aclOwnTemplate);
        haclCheckScriptPath();
        ob_start();
        require(dirname(__FILE__).'/HACL_ACLList.tpl.php');
        $html = ob_get_contents();
        ob_end_clean();
        $wgOut->setPageTitle(wfMsg('hacl_acllist'));
        $wgOut->addHTML($html);
    }

    public function html_acl(&$q)
    {
        global $wgOut, $wgUser, $wgScript, $haclgHaloScriptPath, $haclgContLang, $wgContLang;
        $predefinedRightsExist = HACLStorage::getDatabase()->getSDForPE(0, 'right');
        if (!($q['sd'] &&
            ($t = Title::newFromText($q['sd'], HACL_NS_ACL)) &&
            ($aclArticle = new Article($t)) &&
            $aclArticle->exists() &&
            ($aclSD = HACLStorage::getDatabase()->getSDByID($aclArticle->getId()))))
        {
            $aclArticle = NULL;
            $aclSD = NULL;
        }
        haclCheckScriptPath();
        ob_start();
        require(dirname(__FILE__).'/HACL_ACLEditor.tpl.php');
        $html = ob_get_contents();
        ob_end_clean();
        $wgOut->setPageTitle($aclSD ? wfMsg('hacl_acl_edit', $aclSD->getSDName()) : wfMsg('hacl_acl_create'));
        $wgOut->addHTML($html);
    }

    /* Manage Quick Access ACL list */
    public function html_quickaccess(&$args)
    {
        global $wgOut, $wgUser, $wgScript, $haclgHaloScriptPath, $wgRequest;
        haclCheckScriptPath();
        /* Handle save */
        $args = $wgRequest->getValues();
        if ($args['save'])
        {
            $ids = array();
            foreach ($args as $k => $v)
                if (substr($k, 0, 3) == 'qa_')
                    $ids[] = substr($k, 3);
            HACLStorage::getDatabase()->saveQuickAcl($wgUser->getId(), $ids);
            wfGetDB(DB_MASTER)->commit();
            header("Location: $wgScript?title=Special:HaloACL&action=quickaccess");
            exit;
        }
        /* Load data */
        $templates = HACLStorage::getDatabase()->getSDs2('right', $args['like']);
        if ($aclOwnTemplate = HACLStorage::getDatabase()->getSDForPE($wgUser->getId(), 'template'))
        {
            $aclOwnTemplate = HACLSecurityDescriptor::newFromId($aclOwnTemplate);
            $aclOwnTemplate->owntemplate = true;
            array_unshift($templates, $aclOwnTemplate);
        }
        $quickacl = HACLQuickacl::newForUserId($wgUser->getId());
        $quickacl_ids = array_flip($quickacl->getSD_IDs());
        foreach ($templates as $sd)
        {
            $sd->selected = array_key_exists($sd->getSDId(), $quickacl_ids);
            $sd->editlink = $wgScript.'?title=Special:HaloACL&action=acl&sd='.$sd->getSDName();
            $sd->viewlink = Title::newFromText($sd->getSDName(), HACL_NS_ACL)->getLocalUrl();
        }
        /* Run template */
        ob_start();
        require(dirname(__FILE__).'/HACL_QuickACL.tpl.php');
        $html = ob_get_contents();
        ob_end_clean();
        $wgOut->setPageTitle(wfMsg('hacl_quickaccess_manage'));
        $wgOut->addHTML($html);
    }

    public function html_grouplist(&$q)
    {
        global $wgOut;
        
    }

    public function html_group(&$q)
    {
        global $wgOut;
        
    }

    public function html_whitelist(&$q)
    {
        global $wgOut;
        
    }

    /* AJAXly loaded ACL list */
    static function haclAcllist($t, $n, $limit = 100)
    {
        global $wgScript, $wgTitle, $haclgHaloScriptPath, $wgUser;
        haclCheckScriptPath();
        /* Load data */
        $t = $t ? explode(',', $t) : NULL;
        if (!$limit)
            $limit = 101;
        $sds = HACLStorage::getDatabase()->getSDs2($t, $n, $limit);
        if (count($sds) == $limit)
        {
            array_pop($sds);
            $max = true;
        }
        $lists = array();
        foreach ($sds as $sd)
        {
            $d = array(
                'name' => $sd->getSDName(),
                'real' => $sd->getSDName(),
                'editlink' => $wgScript.'?title=Special:HaloACL&action=acl&sd='.$sd->getSDName(),
                'viewlink' => Title::newFromText($sd->getSDName(), HACL_NS_ACL)->getLocalUrl(),
            );
            if ($p = strpos($d['real'], '/'))
            {
                $d['real'] = substr($d['real'], $p+1);
                if ($sd->getPEType() == 'template' && $d['real'] == $wgUser->getName())
                    $d['real'] = wfMsg('hacl_acllist_own_template', $d['real']);
            }
            $lists[$sd->getPEType()][] = $d;
        }
        /* Run template */
        ob_start();
        require(dirname(__FILE__).'/HACL_ACLListContents.tpl.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}
