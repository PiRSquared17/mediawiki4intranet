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

/**
 * FavRate extension main class
 */
class FavRate
{
    static $cache = array();

    /**
     * Database schema updates
     */
    static function LoadExtensionSchemaUpdates()
    {
        global $wgExtNewTables, $wgExtNewFields;
        $dir = dirname(__FILE__);
        $wgExtNewTables[] = array('fr_page_stats', "$dir/FavRate.sql");
        $wgExtNewFields[] = array('fr_page_stats', 'ps_comment', "$dir/ps_comment.sql");
        return true;
    }

    /**
     * Unique page view tracking for ALL pages
     * WARNING: probably not recommended for large sites with high load
     */
    static function ArticleViewHeader(&$article, &$outputDone, &$pcache)
    {
        global $wgUser, $egFavRateLogVisitors;
        // Only track authorized users
        if ($egFavRateLogVisitors && $wgUser && $wgUser->getId())
        {
            $pageId = $article->getId();
            if ($pageId)
                self::setFavorite($pageId, $wgUser->getId(), 0, true);
        }
        return true;
    }

    /**
     * AJAX callback for adding/removing page $pageid to/from favorites
     * @param int $pageid Page ID
     * @param boolean $addremove true to add, false to remove
     * @param string $comment optional comment to add for this favorite
     * @return JSON-encoded array(boolean $ok, string $message)
     */
    static function ajaxSetFavorite($pageid, $addremove, $comment = NULL)
    {
        global $wgUser;
        if (!$wgUser->getId())
        {
            // Unauthorized user can't add anything to favorites
            return '[false,"'.addslashes(wfMsgExt(
                'favrate-unauthorized', 'parseinline', Title::newFromText('Special:Userlogin')
            )).'"]';
        }
        $title = Title::newFromId($pageid);
        if (!$title || !$title->exists() ||
            method_exists($title, 'userCanReadEx') && !$title->userCanReadEx())
        {
            // Page is invalid or unreadable
            return '[false,"'.addslashes(wfMsg('favrate-invalid-page')).'"]';
        }
        self::setFavorite($pageid, $wgUser->getId(), 1, $addremove, $comment);
        // Invalidate cache for the page (+/- talk page)
        $title->invalidateCache();
        if (class_exists('WikilogComment'))
        {
            $comment = WikilogComment::newFromPageID($pageid);
            if ($comment)
                $comment->invalidateCache();
        }
        $msg = $addremove ? 'favrate-added' : 'favrate-removed';
        $html = wfMsgExt($msg, 'parseinline', 'Special:FavRate/favorites/'.$wgUser->getName());
        if ($addremove)
        {
            $html .= '<br /><input type="text" id="favrate-comment-'.$pageid.'" value="'.
                wfMsg('favrate-comment').'" class="favcomment favempty" onfocus="favRateStartComment('.$pageid.')" />'.
                '&nbsp;<a href="javascript:void(0)" onclick="favRateComment('.$pageid.')">'.wfMsg('favrate-post-comment').'</a>';
        }
        return '[true,"'.addslashes($html).'"]';
    }

    /**
     * Set or clear favorites table entry, optionally with a short comment.
     * Comments are stored in the same DB table, maybe it's not optimal for large sites.
     * @param int $pageid Page ID
     * @param int $userid User ID
     * @param boolean $fav true to set favorite entry, false to set view tracking
     * @param boolean $add true to add entry, false to remove
     * @param string $comment Optional comment to the favorite (useful only for $fav==true)
     */
    static function setFavorite($pageid, $userid, $fav, $add, $comment = NULL)
    {
        $dbw = wfGetDB(DB_MASTER);
        $fav = $fav ? 1 : 0;
        if ($add)
        {
            $dbw->replace(
                'fr_page_stats',
                array(array('ps_page', 'ps_user', 'ps_type')),
                array(
                    'ps_page'       => $pageid,
                    'ps_user'       => $userid,
                    'ps_timestamp'  => wfTimestamp(TS_MW),
                    'ps_type'       => $fav,
                    'ps_comment'    => $comment,
                ), __METHOD__
            );
        }
        else
        {
            $dbw->delete('fr_page_stats', array(
                'ps_page' => $pageid,
                'ps_user' => $userid,
                'ps_type' => $fav,
            ));
        }
    }

    /**
     * Get rating counters for $article and user $userid (may be an instance of WikiPage or Title)
     * Not suitable for mass fetch. Should not be called in a loop!
     *
     * @param object $article
     * @param int $userid
     * @return false or array(
     *   counter => page counter,
     *   fav => favorites count,
     *   myfav => is user's favorite,
     *   comment => user's comment,
     *   links => page link count
     * )
     */
    static function getPageCounters($article, $userId = 0)
    {
        $counters = array();
        $dbr = wfGetDB(DB_SLAVE);
        if ($article instanceof Title)
            $article = new WikiPage($article);
        $pageId = $article->getId();
        if (!$pageId)
            return false;
        $counters['counter'] = $article->getCount();
        $res = $dbr->select(
            'fr_page_stats',
            array('COUNT(*) fav', 'ps_user='.intval($userId).' myfav'),
            array('ps_type' => 1, 'ps_page' => $pageId),
            __METHOD__
        );
        $row = $dbr->fetchRow($res);
        $comment = NULL;
        if ($row['myfav'])
        {
            $comment = $dbr->selectField(
                'fr_page_stats', 'ps_comment',
                array('ps_user' => $userId, 'ps_type' => 1, 'ps_page' => $pageId), __METHOD__
            );
        }
        $counters['fav'] = $row['fav'];
        $counters['myfav'] = $row['myfav'] ? 1 : 0;
        $counters['comment'] = $comment;
        $counters['links'] = $dbr->selectField('pagelinks', 'COUNT(*)', array(
            'pl_namespace' => $article->getTitle()->getNamespace(),
            'pl_title' => $article->getTitle()->getDBkey()
        ), __METHOD__);
        return $counters;
    }

    /**
     * Preloads favorites for $userId and $pageIds
     */
    static function preload($userId, $pageIds)
    {
        $dbr = wfGetDB(DB_SLAVE);
        $res = $dbr->select(
            'fr_page_stats',
            array('ps_page', "COUNT(1) fav", 'SUM(ps_user='.intval($userId).') myfav'),
            array('ps_type' => 1, 'ps_page' => $pageIds),
            __METHOD__,
            array('GROUP BY' => 'ps_page')
        );
        foreach ($res as $row)
        {
            self::$cache[$row->ps_page]['total'] = $row->fav;
            if ($userId)
                self::$cache[$row->ps_page]['user'.$userId] = $row->myfav;
        }
    }

    /**
     * Preloads ratings for all Wikilog comments in $comments
     */
    static function WikilogPreloadComments($pager, &$comments)
    {
        global $wgUser;
        $userId = $wgUser->getId();
        foreach ($comments as $comment)
            $pageIds[] = $comment->mCommentPage;
        self::preload($userId, $pageIds);
        return true;
    }

    /**
     * Displays rating count and button for Wikilog comments
     */
    static function WikilogCommentToolLinks($formatter, $comment, &$tools)
    {
        global $wgUser;
        $userId = $wgUser->getId();
        $pageId = $comment->mCommentPage;
        $total = $my = 0;
        if (isset(self::$cache[$pageId]))
        {
            $total = intval(self::$cache[$pageId]["total"]);
            $my = $userId && self::$cache[$pageId]["user$userId"] ? 1 : 0;
        }
        if ($userId || $total)
        {
            $text = '+'.$total;
            if ($userId)
                $text = '<a href="javascript:void(0)" '.
                    'onclick="favRateToggleFavWikilog(this, '.$pageId.')">'.$text.'</a>';
            $tools['fav'.$my.($total > 0 ? ' hasfav' : '')] = $text;
        }
        return true;
    }

    /**
     * Loads extension resource module everywhere :-(
     * Because we can't modify modules during sidebar construction
     */
    static function BeforePageDisplay($out, $skin)
    {
        $out->addModules('ext.favrate');
        return true;
    }

    /**
     * Adds rating bars and add/remove to/from favorites button to page sidebar
     */
    static function SkinBuildSidebar($skin, &$bar)
    {
        global $wgTitle, $wgArticle, $wgUser, $wgRequest, $wgScriptPath, $wgOut;
        global $egFavRateMaxHits, $egFavRateMaxFav, $egFavRateMaxLinks, $egFavRatePublicLogs;
        global $egFavRateHitsColor, $egFavRateFavColor, $egFavRateLinksColor;
        if (!isset($bar['favratebar']))
            return true;
        $pageCounters = self::getPageCounters($wgArticle ? $wgArticle : $wgTitle, $wgUser ? $wgUser->getId() : 0);
        if ($pageCounters)
        {
            wfLoadExtensionMessages('FavRate');
            $blank = "$wgScriptPath/extensions/FavRate/blank.gif";
            $html = '<div style="margin-top: 6px">';
            // Toggle button and status message placeholder
            if ($pageCounters['comment'])
                $linkAlt = wfMsg('favrate-remfav-cmt', $pageCounters['comment']);
            else
                $linkAlt = wfMsg($pageCounters['myfav'] ? 'favrate-remfav' : 'favrate-addfav');
            $html .= '<div class="favtoggle">'.
                '<img class="favtogglebtn fav'.$pageCounters['myfav'].'" onclick="favRateToggleFav(this)"'.
                ' title="'.$linkAlt.'" alt=" " src="'.$blank.'" /></div>';
            // Rating bars
            $html .= self::bar($pageCounters['counter'], $egFavRateMaxHits, $egFavRateHitsColor, wfMsg('favrate-hits'));
            $html .= self::bar($pageCounters['fav'], $egFavRateMaxFav, $egFavRateFavColor, wfMsg('favrate-fav'));
            $html .= self::bar($pageCounters['links'], $egFavRateMaxLinks, $egFavRateLinksColor, wfMsg('favrate-links'));
            $html .= '<div class="favlinks">';
            if ($egFavRatePublicLogs)
            {
                // Link to page logs
                $html .= '<a rel="nofollow" href="'.htmlspecialchars(
                    Title::newFromText("Special:FavRate/log/$wgTitle")->getLocalUrl()
                ).'">'.wfMsg('favrate-viewlogs').'</a> ';
            }
            // Link to "My Likes"
            $html .= '<a rel="nofollow" href="'.htmlspecialchars(
                Title::newFromText("Special:FavRate/favorites")->getLocalUrl()
            ).'">'.wfMsg('favrate-favorites').'</a>';
            $html .= '</div></div>';
            $bar['favratebar'] = $html;
        }
        else
            unset($bar['favratebar']);
        return true;
    }

    /**
     * Builds HTML code for a rating bar
     */
    static function bar($n, $max, $color, $text)
    {
        global $wgScriptPath;
        $text .= ": $n/$max";
        $imgstyle = "vertical-align: top; height: 7px; margin: 0; border-width: 0";
        $blank = "$wgScriptPath/extensions/FavRate/blank.gif";
        $text = htmlspecialchars($text);
        $bar = sprintf("%.2f", 100*log(1+min($n,$max))/log(1+$max));
        $rest = 100-$bar;
        $html = "<div class='favbar'>".
            ($bar > 0   ? "<img style='background-color: $color; width: $bar%; $imgstyle' alt=' ' title='$text' src='$blank' />" : '').
            ($bar < 100 ? "<img style='background-color: gray; width: $rest%; $imgstyle' alt=' ' title='$text' src='$blank' />" : '').
            "</div>";
        return $html;
    }
}
