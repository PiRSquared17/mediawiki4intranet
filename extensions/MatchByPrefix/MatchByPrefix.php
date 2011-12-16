<?php

/**
 * MediaWiki MatchByPrefix extension, for MediaWiki 1.14+
 * Copyright © 2011 Vitaliy Filippov
 * http://wiki.4intra.net/MatchByPrefix
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

$wgExtensionCredits['other'][] = array(
    'name'         => 'Match page by prefix',
    'version'      => '2011-12-16',
    'author'       => 'Vitaliy Filippov',
    'url'          => 'http://wiki.4intra.net/MatchByPrefix',
    'description'  => 'Redirects to page having a prefix equal to '.
        'one specified in URL, if the URL itself does not point to '.
        'a page and if only one matching page exists',
);
$wgHooks['InitializeArticleMaybeRedirect'][] = 'efMatchByPrefixMaybeRedirect';

function efMatchByPrefixMaybeRedirect(&$title, &$request, &$ignoreRedirect, &$target, &$article)
{
    if (!$article->exists())
    {
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select('page', '*', array(
            'page_namespace' => $title->getNamespace(),
            'page_title LIKE '.$dbr->addQuotes($title->getDBkey().'%'),
        ), __FUNCTION__);
        if ($dbr->numRows($res) == 1)
        {
            $row = $dbr->fetchObject($res);
            $target = Title::newFromRow($row);
            if ($target->isRedirect())
            {
                $article = new Article($target);
                $nt = $article->followRedirect();
                if ($nt)
                    $target = $nt;
            }
            return false;
        }
    }
    return true;
}
