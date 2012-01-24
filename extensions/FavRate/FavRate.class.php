<?php

/**
 * MediaWiki FavRate extension
 * Copyright © 2010-2012 Vitaliy Filippov
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
 * Main class for extension
 */
class FavRate
{
    // Database schema updates
    static function LoadExtensionSchemaUpdates()
    {
        $dbw = wfGetDB(DB_MASTER);
        if (!$dbw->tableExists('fr_page_aggr'))
            $dbw->sourceFile(dirname(__FILE__) . '/FavRate.sql');
        return true;
    }
    // Unique page view tracking
    // Probably not recommended for large sites
    static function ArticleViewHeader(&$article, &$outputDone, &$pcache)
    {
        global $wgUser, $egFavRateLogVisitors;
        // Only track authorized users
        if ($wgUser && $wgUser->getId())
        {
            $dbw = wfGetDB(DB_MASTER);
            $user_id = $wgUser->getId();
            $page_id = $article->getId();
            if ($user_id && $page_id)
            {
                $dbw->replace(
                    'fr_page_stats',
                    array(array('ps_page', 'ps_user', 'ps_type')),
                    array(
                        'ps_page'       => $page_id,
                        'ps_user'       => $user_id,
                        'ps_timestamp'  => wfTimestamp(TS_MW),
                        'ps_type'       => 0,
                    ), __METHOD__
                );
            }
        }
        return true;
    }
    // Add/remove favorites
    static function setFavorite($pageid, $addremove)
    {
        global $wgUser;
        if (!$wgUser || !$wgUser->getId())
        {
            // Unauthorized user cannot add anything to favorites
            return '[false,"'.addslashes(wfMsgExt(
                'favrate-unauthorized', 'parseinline', Title::newFromText('Special:Userlogin')
            )).'"]';
        }
        $title = Title::newFromId($pageid);
        if (!$title || !$title->exists() ||
            method_exists($title, 'userCanReadEx') && !$title->userCanReadEx())
        {
            // Page is invalid
            return '[false,"'.addslashes(wfMsg('favrate-invalid-page')).'"]';
        }
        $dbw = wfGetDB(DB_MASTER);
        if ($addremove)
            $dbw->replace('fr_page_stats', array(array('ps_page', 'ps_user')), array(
                'ps_page'       => $pageid,
                'ps_user'       => $wgUser->getId(),
                'ps_timestamp'  => wfTimestamp(TS_MW),
                'ps_type'       => 1,
            ), __METHOD__);
        else
            $dbw->delete('fr_page_stats', array(
                'ps_page'       => $pageid,
                'ps_user'       => $wgUser->getId(),
                'ps_type'       => 1,
            ));
        return '[true,"'.addslashes(wfMsgExt(
            $addremove ? 'favrate-added' : 'favrate-removed',
            'parseinline', 'Special:FavRate/favorites/'.$wgUser->getName()
        )).'"]';
    }
    // Display rating bar
    static function SkinBuildSidebar($skin, &$bar)
    {
        global $wgTitle, $wgArticle, $wgUser, $wgRequest, $wgScriptPath, $wgOut;
        global $egFavRateMaxHits, $egFavRateMaxFav, $egFavRateMaxLinks, $egFavRatePublicLogs;
        if (($page_id = $wgTitle->getArticleID()) && array_key_exists('favratebar', $bar))
        {
            $dbr = wfGetDB(DB_SLAVE);
            wfLoadExtensionMessages('FavRate');
            $wgOut->addHeadItem(
                'favrate.css',
                '<link rel="stylesheet" type="text/css" href="'.
                $wgScriptPath.'/extensions/FavRate/favrate.css" />'
            );
            $msg = array();
            foreach (array('addfav', 'remfav') as $s)
                $msg[] = "'$s': '".addslashes(wfMsg("favrate-$s"))."'";
            $wgOut->addHeadItem(
                'favrate.js',
                '<script language="JavaScript">var favRateMsg = {'.implode(',', $msg).'}</script>'.
                '<script language="JavaScript" src="'.$wgScriptPath.'/extensions/FavRate/favrate.js"></script>'
            );
            // 1) $counter = page counter
            if ($wgArticle)
                $counter = $wgArticle->getCount();
            else
            {
                $a = new Article($wgTitle);
                $counter = $a->getCount();
            }
            $userid = 0;
            if ($wgUser && $wgUser->getId())
                $userid = $wgUser->getId();
            $result = $dbr->select(
                'fr_page_stats',
                array("COUNT(*) fav", "SUM(ps_user=$userid) myfav"),
                array('ps_type' => 1, 'ps_page' => $page_id), __METHOD__
            );
            $row = $dbr->fetchRow($result);
            $dbr->freeResult($result);
            // 2) $fav = favorites
            $fav = $row['fav'];
            // 3) $myfav = is favorite for current user
            $myfav = $row['myfav'] ? 1 : 0;
            // 4) $links = backlinks
            $links = $dbr->selectField(
                'pagelinks', 'COUNT(*)',
                array('pl_namespace' => $wgTitle->getNamespace(), 'pl_title' => $wgTitle->getDBkey()),
                __METHOD__
            );
            $blank = "$wgScriptPath/extensions/FavRate/blank.gif";
            $html = '<div style="margin-top: 6px">';
            // Toggle button and status message placeholder
            $html .=
                "<div class='favtoggle'><div id='favstatus'></div>".
                "<img id='favtogglebtn' class='fav$myfav' onclick='favRateToggleFav()'".
                " title='".wfMsg('favrate-addfav')."' alt=' ' src='$blank' /></div>";
            // Rating bars
            $html .= self::bar($counter, $egFavRateMaxHits, '#0C0', wfMsg('favrate-hits'));
            $html .= self::bar($fav, $egFavRateMaxFav, '#C00', wfMsg('favrate-fav'));
            $html .= self::bar($links, $egFavRateMaxLinks, '#00C', wfMsg('favrate-links'));
            $html .= '<div style="margin: 0; text-align: center">';
            if ($egFavRatePublicLogs)
            {
                // Link to page logs
                $html .= '<a rel="nofollow" href="'.htmlspecialchars(
                    Title::newFromText("Special:FavRate/log/$wgTitle")->getLocalUrl()
                ).'">'.wfMsg('favrate-viewlogs').'</a> &nbsp; ';
            }
            // Links to "My Favorites"
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
    // Get HTML for a rating bar
    static function bar($n, $max, $color, $text)
    {
        global $wgScriptPath;
        $text .= ": $n/$max";
        $contstyle = "height: 7px; border: 1px outset gray; margin: 2px 0 0 0";
        $imgstyle = "vertical-align: top; height: 7px; margin: 0; border-width: 0";
        $blank = "$wgScriptPath/extensions/FavRate/blank.gif";
        $text = htmlspecialchars($text);
        $bar = sprintf("%.2f", 100*log(1+min($n,$max))/log(1+$max));
        $rest = 100-$bar;
        $html = "<div style='$contstyle; width: 100%; background-color: gray'>".
            ($bar > 0   ? "<img style='background-color: $color; width: $bar%; $imgstyle' alt=' ' title='$text' src='$blank' />" : '').
            ($bar < 100 ? "<img style='background-color: gray; width: $rest%; $imgstyle' alt=' ' title='$text' src='$blank' />" : '').
            "</div>";
        return $html;
    }
}
