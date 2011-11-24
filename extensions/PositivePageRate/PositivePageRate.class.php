<?php

if (!defined('MEDIAWIKI'))
    die();

/**
 * Main class for extension
 */
class PositivePageRate
{
    /* Database schema updates */
    static function LoadExtensionSchemaUpdates()
    {
        $dbw = wfGetDB(DB_MASTER);
        if (!$dbw->tableExists('ppr_page_aggr'))
            $dbw->sourceFile(dirname(__FILE__) . '/PositivePageRate.sql');
        return true;
    }
    /* Update aggregate statistics for a given page */
    static function updateAggregate($page_id)
    {
        $dbw = wfGetDB(DB_MASTER);
        $r = $dbw->select('ppr_page_stats',
            array('COUNT(*) total',
                'SUM(CASE WHEN ps_rate>0 THEN 1 ELSE 0 END) plus',
                'SUM(CASE WHEN ps_rate<0 THEN 1 ELSE 0 END) minus'),
            "ps_page=$page_id", __METHOD__);
        $row = $dbw->fetchRow($r);
        $dbw->freeResult($r);
        $dbw->replace('ppr_page_aggr', 1, array(
            'pa_page' => $page_id,
            'pa_total' => $row['total'],
            'pa_plus' => $row['plus'],
            'pa_minus' => $row['minus'],
        ));
    }
    /* Unique page view tracking */
    static function ArticleViewHeader(&$article, &$outputDone, &$pcache)
    {
        global $wgUser;
        /* We are tracking only authorized users */
        if ($wgUser && $wgUser->getId())
        {
            $dbr = wfGetDB(DB_SLAVE);
            $user_id = $wgUser->getId();
            $page_id = $article->getId();
            if ($user_id && $page_id)
            {
                $count = $dbr->selectField('ppr_page_stats', 'ps_page', array('ps_page' => $page_id, 'ps_user' => $user_id), __METHOD__);
                if (!$count)
                {
                    $dbw = wfGetDB(DB_MASTER);
                    $dbw->insert('ppr_page_stats', array(
                        'ps_page'       => $page_id,
                        'ps_user'       => $user_id,
                        'ps_timestamp'  => wfTimestamp(TS_MW),
                        'ps_rate'       => 0,
                    ), __METHOD__);
                    self::updateAggregate($page_id);
                }
            }
        }
        return true;
    }
    /* Rating actions */
    static function UnknownAction($action, $article)
    {
        global $egPositivePageRateAllowRecall, $egPositivePageRateAllowNegative;
        if ($action == 'pprate' || $action == 'ppunrate' && $egPositivePageRateAllowRecall)
        {
            global $wgUser, $wgOut, $wgRequest;
            wfLoadExtensionMessages('PositivePageRate');
            if (!$wgUser || !$wgUser->mPassword)
            {
                /* Unauthorized users are not allowed to rate articles */
                $wgOut->showErrorPage('pprate-unauthorized-title', 'pprate-unauthorized-text');
                return false;
            }
            $dbw = wfGetDB(DB_MASTER);
            $rate = $action == 'pprate' ? $wgRequest->getVal('minus') && $egPositivePageRateAllowNegative ? -1 : 1 : 0;
            $ts = wfTimestamp(TS_MW);
            $user_id = $wgUser->getId();
            $page_id = $article->getId();
            /* Touch database row */
            $where = array('ps_page' => $page_id, 'ps_user' => $user_id);
            $values = array('ps_rate' => $rate, 'ps_timestamp' => $ts);
            $count = $dbw->selectField('ppr_page_stats', 'ps_page', $where, __METHOD__, array('FOR UPDATE'));
            if (!$count)
                $dbw->insert('ppr_page_stats', $where+$values, __METHOD__);
            else
                $dbw->update('ppr_page_stats', $values, $where, __METHOD__);
            self::updateAggregate($page_id);
            /* Display status message */
            $wgOut->addWikiText(wfMsg($rate ? 'pprate-rated' : 'pprate-unrated'));
            $article->view();
            return false;
        }
        return true;
    }
    /* Log page actions when enabled */
    static function MediaWikiPerformAction($output, $article, $title, $user, $request, $wiki)
    {
        global $wgUser, $wgUserAccessLogFile, $wgUserAccessLogSpecials, $egPositivePageRateHideLog;
        /* Log page access */
        if ($wgUserAccessLogFile && $wgUser && $wgUser->mPassword &&
            ($wgUserAccessLogSpecials || $article->getId()))
        {
            wfErrorLog(
                wfTimestamp(TS_DB, $ts) .
                sprintf(" %08x %08x ", $article->getId(), $wgUser->getId()) .
                str_replace(' ', '\ ', $wiki->getVal('action')) . ' ' . str_replace(' ', '\ ', $wgUser->getName()) .
                ' ' . str_replace(' ', '\ ', $article->getTitle()->getPrefixedDBkey()) . "\n",
                $wgUserAccessLogFile);
        }
        return true;
    }
    /* Display rating bar */
    static function SkinBuildSidebar($skin, &$bar)
    {
        global $wgTitle, $wgUser, $wgRequest, $egPositivePageRateAllowRecall, $egPositivePageRateAllowNegative;
        if (($page_id = $wgTitle->getArticleID()) && array_key_exists('pprate', $bar))
        {
            wfLoadExtensionMessages('PositivePageRate');
            /* Read total unique views and rating */
            $dbr = wfGetDB(DB_SLAVE);
            $users = $dbr->selectField('ppr_page_stats', 'COUNT(DISTINCT ps_user)', '1', __METHOD__);
            $result = $dbr->select('ppr_page_aggr', '*', array('pa_page' => $page_id), __METHOD__);
            $row = $dbr->fetchRow($result);
            $dbr->freeResult($result);
            if ($wgUser && $wgUser->mPassword)
            {
                $user_id = $wgUser->getId();
                $me = $dbr->selectField('ppr_page_stats', '1', array('ps_page' => $page_id, 'ps_user' => $user_id, 'ps_rate!=0'), __METHOD__);
            }
            if ($row)
            {
                extract($row);
                /* Build HTML code and insert into sidebar */
                if ($egPositivePageRateHideLog)
                    $log_url = '';
                else
                    $log_url = Title::newFromText('Special:PositivePageRate')->getLocalUrl(array('page' => $wgTitle->getPrefixedText()));
                $html = '<div style="margin: 3pt 0">';
                if ($log_url)
                    $html .= '<a rel="nofollow" href="' . $log_url . '">';
                $html .= wfMsgExt('pprate-statistics', 'parseinline', $pa_total, $pa_plus+$pa_minus, $pa_plus, $pa_minus, $pa_total-$pa_plus-$pa_minus);
                if ($log_url)
                    $html .= '</a>';
                if ($pa_total > 1)
                {
                    $html .= '<div style="margin-top: 3pt">';
                    if ($log_url)
                        $html .= '<a rel="nofollow" href="' . $log_url . '">';
                    $html .= self::bar((100*log($pa_total)/log($users)).'%', $pa_total, $pa_plus, $pa_minus);
                    if ($log_url)
                        $html .= '</a>';
                    $html .= '</div>';
                }
                if ($me && $egPositivePageRateAllowRecall)
                    $html .= '<div><a rel="nofollow" href="' . $wgTitle->getLocalUrl('action=ppunrate') . '">' . wfMsgExt('pprate-unrate', 'parseinline') . '</a></div>';
            }
            else
                $html .= '<div style="margin: 3pt 0">' . wfMsg('pprate-no-stats') . '</div>';
            if (!$me && $user_id)
            {
                $html .= '<div><a rel="nofollow" href="' . $wgTitle->getLocalUrl('action=pprate&plus=1') . '">';
                $html .= wfMsg('pprate-plus').'</a>';
                if ($egPositivePageRateAllowNegative)
                {
                    $html .= ' | <a rel="nofollow" href="' . $wgTitle->getLocalUrl('action=pprate&minus=1') . '">';
                    $html .= wfMsg('pprate-minus').'</a></div>';
                }
            }
            $html .= '</div>';
            $bar['pprate'] = $html;
        }
        else
            unset($bar['pprate']);
        return true;
    }
    /* Get HTML for a rating bar */
    function bar($width, $total, $plus, $minus)
    {
        global $wgScriptPath;
        if (!$total || !$width)
            return '';
        $p = '';
        if (substr($width, -1) == '%')
        {
            $p = '%';
            $width = substr($width, 0, -1);
        }
        if ($plus > 0)
            $html .= '<img alt="'.wfMsg('pprate-nplus').'" title="'.wfMsg('pprate-nplus').'" src="'.$wgScriptPath.'/extensions/PositivePageRate/good.gif" height="7" width="'.intval($plus/$total*$width).$p.'" />';
        if ($plus+$minus < $total)
            $html .= '<img alt="'.wfMsg('pprate-nview').'" title="'.wfMsg('pprate-nview').'" src="'.$wgScriptPath.'/extensions/PositivePageRate/neutral.gif" height="7" width="'.intval(($total-$plus-$minus)/$total*$width).$p.'" />';
        if ($minus > 0)
            $html .= '<img alt="'.wfMsg('pprate-nminus').'" title="'.wfMsg('pprate-nminus').'" src="'.$wgScriptPath.'/extensions/PositivePageRate/bad.gif" height="7" width="'.intval($minus/$total*$width).$p.'" />';
        return $html;
    }
}
