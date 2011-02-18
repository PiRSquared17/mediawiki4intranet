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
 * HaloACL toolbar for MediaWiki edit mode
 */

class HACLToolbar
{
    static function get($title)
    {
        global $wgUser, $wgRequest, $haclgContLang, $wgContLang, $haclgIP;

        $ns = $wgContLang->getNsText(HACL_NS_ACL);
        $protected = NULL;
        $canModify = true;
        $options = array();

        if (!is_object($title))
            $title = Title::newFromText($title);

        if ($title->exists())
        {
            // try to get assigned right
            $pagePrefix = $haclgContLang->getPetPrefix(HACLLanguage::PET_PAGE);
            try
            {
                $SD = HACLSecurityDescriptor::newFromName("$pagePrefix/$title");
                $options[] = array('sdname' => ($protected = "$ns:".$SD), 'current' => true);
                if (!$SD->userCanModify($wgUser->getName()))
                    $canModify = false;
            }
            catch(Exception $e) {}
        }

        // TODO move "default" to Quickacl
        // does a user default template exist?
        $templatePrefix = $haclgContLang->getSDTemplateName();
        try
        {
            $sd = HACLSecurityDescriptor::newFromName("$templatePrefix/".$wgUser->getName());
            if ($sd->checkIntegrity() === true)
                $options[] = array('sdname' => "$ns:".$sd->getSDName());
        } catch(Exception $e) {}

        // does a global default template exist?
        $globalDefault = Title::newFromText($haclgContLang->getGlobalDefault());
        if ($globalDefault->articleExists())
            $options[] = array('sdname' => $globalDefault->getPrefixedText());

        // adding Quickacl to selectbox
        $quickacl = HACLQuickacl::newForUserId($wgUser->getId());
        foreach ($quickacls->getSDs() as $sd)
        {
            try
            {
                // Check if the template is valid or corrupted by missing groups, user,...
                // TODO always remove SD definition from database when it is corrupted
                if ($sd->checkIntegrity() === true)
                    $options[] = array('sdname' => "$ns:".$sd);
            } catch (HACLException $e) {}
        }

        // haloacl_protect_with = SD article name
        if ($canModify && ($st = $wgRequest->getVal('haloacl_protect_with')))
        {
            foreach ($options as &$o)
            {
                $o['current'] = $o['sdname'] == $st;
                if ($o['sdname'] == $st)
                    $protected = $o['sdname'];
            }
        }

        // Run template
        ob_start();
        require(dirname(__FILE__).'/HACL_Toolbar.tpl.php');
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}
