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
 * This file contains functions for client/server communication with Ajax.
 *
 * @author B2browse/Patrick Hilsbos, Steffen Schachtler
 * Date: 07.10.2009
 *
 */

/**
 * @param <string> javascript-escaped string
 * @return <string> unescaped string
 */
function unescape($source) {
    $decodedStr = '';
    $pos = 0;
    $len = strlen ($source);

    while ($pos < $len) {
        $charAt = substr ($source, $pos, 1);
        if ($charAt == '%') {
            $pos++;
            $charAt = substr ($source, $pos, 1);
            if ($charAt == 'u') {
                // we got a unicode character
                $pos++;
                $unicodeHexVal = substr ($source, $pos, 4);
                $unicode = hexdec ($unicodeHexVal);
                $decodedStr .= code2utf($unicode);
                $pos += 4;
            } else {
                // we have an escaped ascii character
                $hexVal = substr ($source, $pos, 2);
                $decodedStr .= code2utf (hexdec ($hexVal));
                $pos += 2;
            }
        } else {
            $decodedStr .= $charAt;
            $pos++;
        }
    }
    return $decodedStr;
}

/*
 * defining ajax-callable functions
 */
global $wgAjaxExportList;
$wgAjaxExportList[] = 'haclAutocomplete';
$wgAjaxExportList[] = 'haclAcllist';
$wgAjaxExportList[] = 'haclGroupClosure';
$wgAjaxExportList[] = 'haclSDExists';
$wgAjaxExportList[] = 'haclGrouplist';
$wgAjaxExportList[] = 'haclGroupExists';

function haclAutocomplete($t, $n, $limit = 11, $checkbox_prefix = false)
{
    if (!$limit)
        $limit = 11;
    $a = array();
    $dbr = wfGetDB(DB_SLAVE);
    // Users
    if ($t == 'user')
    {
        $r = $dbr->select(
            'user', 'user_name, user_real_name',
            array('user_name LIKE '.$dbr->addQuotes($n.'%').' OR user_real_name LIKE '.$dbr->addQuotes($n.'%')),
            __METHOD__,
            array('ORDER BY' => 'user_name', 'LIMIT' => $limit)
        );
        while ($row = $r->fetchRow())
            $a[] = array($row[1] ? $row[0] . ' (' . $row[1] . ')' : $row[0], $row[0]);
    }
    // HaloACL Groups
    elseif ($t == 'group')
    {
        $ip = 'hi_';
        $r = HACLStorage::getDatabase()->getGroups($n, $limit);
        foreach ($r as $group)
        {
            $n = $group->getGroupName();
            if (($p = strpos($n, '/')) !== false)
                $n = substr($n, $p+1);
            $a[] = array($n, $n);
        }
    }
    // MediaWiki Pages
    elseif ($t == 'page')
    {
        $ip = 'ti_';
        $n = str_replace(' ', '_', $n);
        $where = array();
        // Check if namespace is specified within $n
        $etc = haclfDisableTitlePatch();
        $tt = Title::newFromText($n.'X');
        if ($tt->getNamespace() != NS_MAIN)
        {
            $n = substr($tt->getDBkey(), 0, -1);
            $where['page_namespace'] = $tt->getNamespace();
        }
        haclfRestoreTitlePatch($etc);
        // Select page titles
        $where[] = 'page_title LIKE '.$dbr->addQuotes($n.'%');
        $r = $dbr->select(
            'page', 'page_title, page_namespace',
            $where, __METHOD__,
            array('ORDER BY' => 'page_namespace, page_title', 'LIMIT' => $limit)
        );
        while ($row = $r->fetchRow())
        {
            $t = Title::newFromText($row[0], $row[1]);
            // Filter unreadable
            if ($t->userCanRead())
            {
                $t = $t->getPrefixedText();
                $a[] = array($t, $t);
            }
        }
    }
    // Namespaces
    elseif ($t == 'namespace')
    {
        $ip = 'ti_';
        global $wgCanonicalNamespaceNames, $wgContLang;
        $ns = $wgCanonicalNamespaceNames;
        $ns[0] = 'Main';
        ksort($ns);
        // Unlimited
        $limit = count($ns)+1;
        $n = mb_strtolower($n);
        $nl = mb_strlen($n);
        foreach ($ns as $k => $v)
        {
            $v = str_replace('_', ' ', $v);
            $name = str_replace('_', ' ', $wgContLang->getNsText($k));
            if (!$name)
                $name = $v;
            if ($k >= 0 && (mb_strtolower(mb_substr($v, 0, $nl)) == $n ||
                mb_strtolower(mb_substr($name, 0, $nl)) == $n))
                $a[] = array($name, $v);
        }
    }
    // Categories
    elseif ($t == 'category')
    {
        $ip = 'ti_';
        $where = array(
            'page_namespace' => NS_CATEGORY,
            'page_title LIKE '.$dbr->addQuotes(str_replace(' ', '_', $n).'%')
        );
        $r = $dbr->select(
            'page', 'page_title',
            $where, __METHOD__,
            array('ORDER BY' => 'page_title', 'LIMIT' => $limit)
        );
        while ($row = $r->fetchRow())
        {
            $t = Title::newFromText($row[0], NS_CATEGORY);
            // Filter unreadable
            if ($t->userCanRead())
            {
                $t = $t->getText();
                $a[] = array($t, $t);
            }
        }
    }
    // ACL definitions of type = substr($t, 3)
    elseif (substr($t, 0, 3) == 'sd/')
    {
        $ip = 'ri_';
        foreach (HACLStorage::getDatabase()->getSDs2(substr($t, 3), $n, $limit) as $sd)
        {
            $rn = $sd->getSDName();
            if ($p = strpos($rn, '/'))
                $rn = substr($rn, $p+1);
            $a[] = array($rn, $sd->getSDName());
        }
    }
    // No items
    if (!$a)
        return '<div class="hacl_tt">'.wfMsg('hacl_autocomplete_no_'.$t.'s').'</div>';
    // More than (limit-1) items => add '...' at the end of list
    if (count($a) >= $limit)
    {
        array_pop($a);
        $max = true;
    }
    $i = 0;
    $html = '';
    if ($checkbox_prefix)
    {
        // This is used by Group Editor: display autocomplete list with checkboxes
        $ip = $checkbox_prefix . '_';
        foreach ($a as $item)
        {
            $i++;
            $html .= '<div id="'.$ip.$i.'" class="hacl_ti" title="'.
                htmlspecialchars($item[1]).'"><input style="cursor: pointer" type="checkbox" id="c'.$ip.$i.
                '" /> '.htmlspecialchars($item[0]).' <span id="t'.$ip.$i.'"></span></div>';
        }
    }
    else
    {
        // This is used by ACL Editor: simple autocomplete lists for editboxes
        foreach ($a as $item)
        {
            $i++;
            $html .= '<div id="'.$ip.$i.'" class="hacl_ti" title="'.
                htmlspecialchars($item[1]).'">'.
                htmlspecialchars($item[0]).'</div>';
        }
    }
    if ($max)
        $html .= '<div class="hacl_tt">...</div>';
    return $html;
}

function haclAcllist()
{
    $a = func_get_args();
    return call_user_func_array(array('HaloACLSpecial', 'haclAcllist'), $a);
}

function haclGrouplist()
{
    $a = func_get_args();
    return call_user_func_array(array('HaloACLSpecial', 'haclGrouplist'), $a);
}

// Return group members for each group of $groups='group1,group2,...',
// + returns rights for each predefined right of $predefined='sd1[sd2,...'
// predefined right names are joined by [ as it is forbidden by MediaWiki in titles
function haclGroupClosure($groups, $predefined = '')
{
    $st = HACLStorage::getDatabase();
    $members = array();
    foreach (explode(',', $groups) as $k)
    {
        if ($k && ($i = HACLGroup::idForGroup($k)))
        {
            $m = $st->getGroupMembersRecursive($i);
            $members[$k] = array();
            foreach ($st->getUserNames(@array_keys($m['user'])) as $u)
                $members[$k][] = 'User:'.$u['user_name'];
            foreach ($st->getGroupNames(@array_keys($m['group'])) as $g)
                $members[$k][] = 'Group/'.$g['group_name'];
        }
    }
    $rights = array();
    foreach (explode('[', $predefined) as $k)
        if ($k)
            $rights[$k] = HaloACLSpecial::getRights($k);
    // FIXME json_encode requires PHP >= 5.2.0
    return json_encode(array('groups' => $members, 'rights' => $rights));
}

function haclSDExists($type, $name)
{
    // FIXME this does not return incorrect SD definitions
    $peID = HACLSecurityDescriptor::peIDforName($name, $type);
    if (!$peID)
        return 'false';
    return HACLStorage::getDatabase()->getSDForPE($peID, $type) ? 'true' : 'false';
}

function haclGroupExists($name)
{
    global $haclgContLang;
    $grpTitle = Title::newFromText($haclgContLang->getGroupPrefix().'/'.$name, HACL_NS_ACL);
    return $grpTitle && $grpTitle->getArticleId() ? 'true' : 'false';
}
