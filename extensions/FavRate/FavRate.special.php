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

    /**
     * Special:FavRate main function
     */
    function execute($par)
    {
        global $wgUser, $egFavRatePublicLogs;
        $title = NULL;
        if (!strpos($par, '/'))
            $par .= '/';
        list($action, $pagename) = explode('/', $par, 2);
        $is_adm = in_array('sysop', $wgUser->getGroups()) ||
            in_array('bureaucrat', $wgUser->getGroups());
        if ($action == 'log' && ($egFavRatePublicLogs || $is_adm))
            self::viewPageLog($pagename);
        elseif ($action == 'favorites')
            self::viewUserFavorites($pagename);
        else
            self::viewTopPages();
        return true;
    }

    /**
     * Show logs for a page
     * @TODO Restrict listing to 200 items on a page
     * @param string $pagename Page title
     */
    function viewPageLog($pagename)
    {
        global $wgOut, $wgLang;
        $title = Title::newFromText($pagename);
        if (!$title || !$title->getArticleId() ||
            method_exists($title, 'userCanReadEx') && !$title->userCanReadEx())
        {
            // Page is unreadable, special or does not exist at all
            $wgOut->showErrorPage('favrate-invalid-title', 'favrate-invalid-title-text', array($pagename));
            return;
        }
        // TODO move query away from here
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select(
            array('user', 'fr_page_stats'), '*',
            array('ps_page' => $title->getArticleId(), 'user_id=ps_user'),
            __METHOD__,
            array('ORDER BY' => 'ps_timestamp DESC', 'LIMIT' => 200)
        );
        $text = '';
        $total = array(0, 0); // total views, total likes
        $ns_user = $wgLang->getNsText(NS_USER);
        foreach ($res as $row)
        {
            $total[$row->ps_type]++;
            $favLink = $row->ps_type ? Title::newFromText('Special:FavRate/favorites/'.$row->user_name) : '';
            $key = $row->ps_type ? ($row->ps_comment ? 'favrate-log-comment' : 'favrate-log-fav') : 'favrate-log-view';
            $text .= wfMsg($key,
                $ns_user.':'.$row->user_name,
                $wgLang->timeanddate($row->ps_timestamp, true),
                $favLink, $row->ps_comment
            ) . "\n";
        }
        $wgOut->addWikiText(wfMsg('favrate-page-log', $title->getPrefixedText(), $total[0], $total[1]));
        $wgOut->addWikiText($text);
        $wgOut->setPageTitle(wfMsg('favrate-page-log-title'));
    }

    /**
     * Show favorites of given user, or of the current user by default
     * @TODO Restrict listing to 200 items on a page
     * @param string $username desired user name
     */
    function viewUserFavorites($username)
    {
        global $wgUser, $wgOut, $wgLang;
        if (!$username)
            $person = $wgUser;
        else
            $person = User::newFromName($username, false);
        $link = Title::makeTitle(NS_SPECIAL, 'FavRate/favorites/')->getLocalUrl();
        $wgOut->addHTML(
            wfMsg('favrate-select-user').
            ' <input type="text" id="favUser" value="'.htmlspecialchars($username).'" />'.
            ' <a href="javascript:void(0)" onclick="document.location=\''.$link.
            '\'+encodeURIComponent(document.getElementById(\'favUser\').value)">'.wfMsg('favrate-user-go').'</a>'
        );
        if (!$person || !$person->getId())
        {
            $wgOut->setPageTitle(wfMsg('favrate-invalid-user'));
            $wgOut->addWikiText(wfMsgNoTrans('favrate-invalid-user-text', $username));
            return;
        }
        // TODO move query away from here
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select(
            array('page', 'fr_page_stats'), '*',
            array('ps_user' => $person->getId(), 'page_id=ps_page', 'ps_type' => 1),
            __METHOD__,
            array('ORDER BY' => 'ps_timestamp DESC')
        );
        $text = '';
        foreach ($res as $row)
        {
            $text .= wfMsgNoTrans(
                'favrate-list-fav',
                Title::newFromRow($row)->getPrefixedText(),
                $wgLang->timeanddate($row->ps_timestamp, true),
                $row->ps_comment
            ) . "\n";
        }
        $wgOut->addWikiText(wfMsg('favrate-favlist-subtitle', $person->getName()));
        $wgOut->addWikiText($text);
        $wgOut->setPageTitle(wfMsg('favrate-favlist-title', $person->getName()));
    }

    /**
     * Show 100 top rated pages
     */
    function viewTopPages()
    {
        global $wgOut;
        $wgOut->setPageTitle(wfMsg('favrate-rating-title'));
        $rows = $this->getTopPages();
        if (!$rows)
            $wgOut->addWikiText(wfMsg('favrate-rating-empty'));
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

    /**
     * Select 100 top rated pages with favorite and link counts
     * @TODO Advanced page selection mechanisms
     */
    function getTopPages()
    {
        $where = array();
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select(
            array('page', 'fr_page_stats'), 'page.*, COUNT(1) fav',
            array('page_id=ps_page', 'ps_type=1'),
            __METHOD__,
            array('GROUP BY' => 'ps_page', 'ORDER BY' => 'fav DESC', 'LIMIT' => 100)
        );
        if (!$dbr->numRows($res))
            return false;
        $ids = array();
        foreach ($res as $row)
        {
            $row->links = 0;
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
        return $rows;
    }
}
