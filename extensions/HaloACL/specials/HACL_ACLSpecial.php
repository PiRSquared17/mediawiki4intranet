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
        global $wgOut;
        
    }

    public function html_acl(&$q)
    {
        global $wgOut, $wgUser, $wgScript, $haclgHaloScriptPath, $haclgContLang;
        haclCheckScriptPath();
        ob_start();
        require(dirname(__FILE__).'/HACL_ACLEditor.tpl.php');
        $html = ob_get_contents();
        ob_end_clean();
        $wgOut->setPageTitle(wfMsg('hacl_acl_editor'));
        $wgOut->addHTML($html);
    }

    public function html_quickaccess(&$q)
    {
        global $wgOut;
        /* Load data */
        $dbr = wfGetDB(DB_SLAVE);
        $templates = HACLStorage::getDatabase()->getSDs(
            'acltemplate', $q['like'] ? array('page_title LIKE '.$dbr->addQuotes('%'.$q['like'].'%')) : ''
        );
        $quickacl = HACLQuickacl::newForUserId($wgUser->getId());
        $quickacl_ids = array_flip($quickacl->getSD_IDs());
        foreach ($templates as $sd)
            $sd->selected = array_key_exists($sd->getSDId(), $quickacl_ids);
        /* Build HTML code */
        $html = wfMsg('hacl_quickaccess_manage');
        $form = self::xelement('label', array('for' => 'hacl_qafilter'), wfMsg('hacl_filter'));
        $form .= Xml::element('input', array('type' => 'text', 'name' => 'like', 'id' => 'hacl_qafilter', 'value' => $q['like']), '');
        $form .= Xml::submitButton(wfMsg('hacl_filter_submit'));
        $form = self::xelement('form', array('action' => '?action=quickaccess'), $form);
        $form = self::xelement('legend', NULL, wfMsg('hacl_filter_sds')) . $form;
        $form = self::xelement('fieldset', NULL, $form);
        $html .= $form;
        if (!$templates)
            $html .= wfMsg('hacl_empty_list');
        else
        {
            $list = '';
            foreach ($templates as &$sd)
            {
                $li = Xml::check('qa_'.$sd->getSDId(), $sd['selected']);
                $li .= ' ' . htmlspecialchars($sd->getSDName());
                $list .= "<li>$li</li>";
            }
            $list = "<ul>$list</ul>";
            $list .= Xml::submitButton(wfMsg('hacl_quickacl_save'));
            $list = "<form action='?action=quickaccess&save=1' method='POST'>$list</form>";
            $html .= $list;
        }
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
}
