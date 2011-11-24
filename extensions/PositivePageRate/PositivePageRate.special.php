<?php

if (!defined('MEDIAWIKI'))
    die();

class SpecialPositivePageRate extends IncludableSpecialPage
{
    function __construct()
    {
        parent::__construct('PositivePageRate');
        wfLoadExtensionMessages('PositivePageRate');
    }
    function execute($parameters)
    {
        global $wgRequest, $wgOut, $wgLang, $wgScriptPath, $egPositivePageRateAnonymousLog, $egPositivePageRateHideLog;
        if ($rp = $wgRequest->getVal('page'))
        {
            $p = Title::newFromText($rp);
            if (!$p || !$p->getArticleId())
            {
                $wgOut->addWikiText(wfMsg('pprate-invalid-title', $rp));
                $p = NULL;
            }
        }
        if ($rcat = $wgRequest->getVal('category'))
        {
            $cat = Title::newFromText($rcat, NS_CATEGORY);
            if (!$cat)
                $wgOut->addWikiText(wfMsg('pprate-invalid-category', $rcat));
        }
        if (($rts = $wgRequest->getVal('to_timestamp')) && $rts != 'YYYY-MM-DD HH:MM:SS')
        {
            $to_ts = wfTimestamp(TS_MW, $rts);
            if ($to_ts != $rts && wfTimestamp(TS_DB, $to_ts) != trim($rts))
            {
                $to_ts = NULL;
                $wgOut->addWikiText(wfMsg('pprate-invalid-tots', $rts));
            }
        }
        if (($rts = $wgRequest->getVal('from_timestamp')) && $rts != 'YYYY-MM-DD HH:MM:SS')
        {
            $from_ts = wfTimestamp(TS_MW, $rts);
            if ($from_ts != $rts && wfTimestamp(TS_DB, $from_ts) != trim($rts))
            {
                $from_ts = NULL;
                $wgOut->addWikiText(wfMsg('pprate-invalid-fromts', $rts));
            }
        }
        $dbr = wfGetDB(DB_SLAVE);
        if ($p && !$egPositivePageRateHideLog)
        {
            $wgOut->setPageTitle(wfMsg('pprate-page-log-title'));
            $wgOut->addWikiText(wfMsg('pprate-page-log', $p->getPrefixedText()));
            $users = $dbr->selectField('ppr_page_stats', 'COUNT(DISTINCT ps_user)', '1', __METHOD__);
            $result = $dbr->select(array('user', 'ppr_page_stats'), '*', array('ps_page' => $p->getArticleId(), 'user_id=ps_user'), __METHOD__, array('ORDER BY' => 'ps_timestamp'));
            $total = $plus = $minus = 0;
            $text = '';
            if ($egPositivePageRateAnonymousLog)
                $nousers = 'nu-';
            while ($row = $dbr->fetchRow($result))
            {
                $total++;
                $key = 'view';
                if ($row['ps_rate'] > 0)
                {
                    $key = 'plus';
                    $plus++;
                }
                else if ($row['ps_rate'] < 0)
                {
                    $key = 'minus';
                    $minus++;
                }
                $text .= wfMsg('pprate-log-'.$nousers.$key, $wgLang->getNsText(NS_USER).':'.$row['user_name'], $wgLang->timeanddate($row['ps_timestamp'], true)) . "\n";
            }
            if ($total > 0)
            {
                $wgOut->addHTML(wfMsgExt('pprate-log-stats', 'parseinline', $total, $plus+$minus, $plus, $minus));
                $wgOut->addHTML('<p>' . PositivePageRate::bar(500*log($total)/log($users), $total, $plus, $minus) . '</p>');
            }
            $wgOut->addWikiText($text);
            $dbr->freeResult($result);
        }
        else
        {
            $wgOut->setPageTitle(wfMsg('pprate-rating-title'));
            $form = '<fieldset><legend>'.wfMsg('pprate-rating-form-title').'</legend>';
            $form .= '<form action="?" method="GET">';
            $form .= '<label for="pprate-category">'.wfMsg('pprate-input-category').'</label>:&nbsp;';
            $form .= '<input name="category" size="30" type="text" value="'.($cat ? htmlspecialchars($cat->getText()) : '').'" />';
            $form .= ' <label for="pprate-from-ts">'.wfMsg('pprate-input-fromts').'</label>:&nbsp;';
            $form .= '<input name="from_timestamp" size="20" type="text" value="'.($from_ts ? wfTimestamp(TS_DB, $from_ts) : 'YYYY-MM-DD HH:MM:SS" style="color: gray').'" onfocus="if(this.value==\'YYYY-MM-DD HH:MM:SS\'){this.value=\'\';this.style.color=\'black\';}" />';
            $form .= ' <label for="pprate-to-ts">'.wfMsg('pprate-input-tots').'</label>:&nbsp;';
            $form .= '<input name="to_timestamp" size="20" type="text" value="'.($to_ts ? wfTimestamp(TS_DB, $to_ts) : 'YYYY-MM-DD HH:MM:SS" style="color: gray').'" onfocus="if(this.value==\'YYYY-MM-DD HH:MM:SS\'){this.value=\'\';this.style.color=\'black\';}" />';
            $form .= ' <input type="submit" value="'.wfMsg('pprate-rating-submit').'" />';
            $form .= '</form></fieldset>';
            $wgOut->addHTML($form);
            $tables = array('ppr_page_aggr');
            $where = array();
            /* TODO Вкрутить сюда же нашу Special:Export'овскую выборку страниц?
               $catname = $state['catname'];
               $modifydate = $state['modifydate'];
               $namespace = $state['namespace'];
               $closure = $state['closure'];
               $catpages = wfExportGetPagesFromCategory($catname, $modifydate, $namespace, $closure);
            */
            if ($cat)
            {
                $tables[] = 'categorylinks';
                $where[] = 'cl_from=pa_page';
                $where['cl_to'] = $cat->getText();
            }
            if ($to_ts || $from_ts)
            {
                $tables[] = 'page';
                $where[] = 'page_id=pa_page';
                if ($to_ts)
                    $where[] = 'page_touched<='.$to_ts;
                if ($from_ts)
                    $where[] = 'page_touched>='.$from_ts;
            }
            $options = array('GROUP BY' => 'pa_page', 'ORDER BY' => 'pa_plus-pa_minus DESC', 'LIMIT' => 100);
            $result = $dbr->select($tables, '*', $where, __METHOD__, $options);
            $wgOut->addHTML(wfMsgExt('pprate-rating-text', 'parseinline',
                $cat ? $cat->getText() : '',
                $from_ts ? $wgLang->timeanddate($from_ts, true) : '',
                $to_ts ? $wgLang->timeanddate($to_ts, true) : ''
            ));
            if (!$dbr->numRows($result))
                $wgOut->addWikiText(wfMsg('pprate-rating-empty'));
            while ($row = $dbr->fetchRow($result))
            {
                $title = Title::newFromId($row['pa_page']);
                if (!$title)
                    continue;
                $wgOut->addHTML(wfMsgExt('pprate-rating-item', 'parseinline', $title->getPrefixedText(), $row['pa_total'], $row['pa_plus']+$row['pa_minus'], $row['pa_plus'], $row['pa_minus'], $row['pa_plus']-$row['pa_minus']));
            }
            $dbr->freeResult($result);
        }
        return true;
    }
}
