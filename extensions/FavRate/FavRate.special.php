<?php

/**
 * MediaWiki FavRate extension
 * Copyright © 2010+ Vitaliy Filippov
 * License: GPLv3 or later
 * http://wiki.4intra.net/FavRate
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

if (!defined('MEDIAWIKI'))
    die();

class SpecialFavRate extends IncludableSpecialPage
{
    function __construct()
    {
        parent::__construct('FavRate');
        wfLoadExtensionMessages('FavRate');
    }

    function execute($par)
    {
        global $wgRequest, $wgOut, $wgLang, $wgScriptPath, $wgUser;
        global $egFavRatePublicLogs;
        $dbr = wfGetDB(DB_SLAVE);
        $title = NULL;
        if (!strpos($par, '/'))
            $par .= '/';
        list($action, $pagename) = explode('/', $par, 2);
        $is_adm = in_array('sysop', $wgUser->getGroups()) ||
            in_array('bureaucrat', $wgUser->getGroups());
        if ($action == 'log' && ($egFavRatePublicLogs || $is_adm))
        {
            // View log for a page
            $title = Title::newFromText($pagename);
            if (!$title || !$title->getArticleId() ||
                method_exists($title, 'userCanReadEx') && !$title->userCanReadEx())
            {
                // Page is unreadable, special or does not exist at all
                $wgOut->showErrorPage('favrate-invalid-title', 'favrate-invalid-title-text', array($pagename));
                return;
            }
            // TODO Resrict listing to 200 items on a page
            $res = $dbr->select(
                array('user', 'fr_page_stats'), '*',
                array('ps_page' => $title->getArticleId(), 'user_id=ps_user'),
                __METHOD__,
                array('ORDER BY' => 'ps_timestamp DESC', 'LIMIT' => 200)
            );
            $selected = $dbr->numRows($res);
            $key = array('view', 'fav');
            $text = '';
            $n = array(0, 0);
            foreach ($res as $row)
            {
                $n[$row->ps_type]++;
                $fav = $row->ps_type ? Title::newFromText('Special:FavRate/favorites/'.$row->user_name) : '';
                $text .= wfMsg(
                    'favrate-log-'.$key[$row->ps_type],
                    $wgLang->getNsText(NS_USER).':'.$row->user_name,
                    $wgLang->timeanddate($row->ps_timestamp, true),
                    $fav
                ) . "\n";
            }
            $wgOut->addWikiText(wfMsg('favrate-page-log', $title->getPrefixedText(), $n[0], $n[1]));
            $wgOut->addWikiText($text);
            $wgOut->setPageTitle(wfMsg('favrate-page-log-title'));
        }
        elseif ($action == 'favorites')
        {
            // View favorites of a user
            if (!$pagename)
                $person = $wgUser;
            else
                $person = User::newFromName($pagename, false);
            if (!$person || !$person->getId())
            {
                $wgOut->showErrorPage('favrate-invalid-user', 'favrate-invalid-user-text', array($pagename));
                return;
            }
            // TODO Resrict listing to 200 items on a page
            $res = $dbr->select(
                array('page', 'fr_page_stats'), '*',
                array('ps_user' => $person->getId(), 'page_id=ps_page', 'ps_type' => 1),
                __METHOD__,
                array('ORDER BY' => 'ps_timestamp DESC')
            );
            $text = '';
            foreach ($res as $row)
            {
                $text .= wfMsg(
                    'favrate-list-fav',
                    Title::newFromRow($row)->getPrefixedText(),
                    $wgLang->timeanddate($row->ps_timestamp, true)
                ) . "\n";
            }
            $wgOut->addWikiText(wfMsg('favrate-favlist-subtitle', $person->getName()));
            $wgOut->addWikiText($text);
            $wgOut->setPageTitle(wfMsg('favrate-favlist-title', $person->getName()));
        }
        else
        {
            // View 100 top rated pages
            $wgOut->setPageTitle(wfMsg('favrate-rating-title'));
            $where = array();
            // TODO Advanced page selection mechanisms
            $res = $dbr->select(
                array('page', 'fr_page_stats'), 'page.*, COUNT(1) fav',
                array('page_id=ps_page', 'ps_type=1'),
                __METHOD__,
                array('GROUP BY' => 'ps_page', 'ORDER BY' => 'fav DESC', 'LIMIT' => 100)
            );
            if (!$dbr->numRows($res))
                $wgOut->addWikiText(wfMsg('favrate-rating-empty'));
            $ids = array();
            foreach ($res as $row)
            {
                $rows[$row->page_id] = $row;
                $ids[] = $row->page_id;
            }
            // Retrieve link count
            $res = $dbr->select(
                array('page', 'pagelinks'), 'page_id, COUNT(1) links',
                array('pl_namespace=page_namespace', 'page_title=pl_title', 'page_id' => $ids),
                __METHOD__,
                array('GROUP BY' => 'page_id')
            );
            foreach ($res as $row)
                $rows[$row->page_id]->links = $row->links;
            foreach ($rows as $row)
            {
                $title = Title::newFromRow($row);
                if (!$title)
                    continue;
                $wgOut->addHTML(wfMsgExt(
                    'favrate-rating-item', 'parseinline', $title->getPrefixedText(),
                    $row->page_counter, $row->links, $row->fav
                ));
            }
        }
        return true;
    }
}
