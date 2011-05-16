<?php

if ( defined( 'MW_PATCH_MERGE_CONFLICTS' ) )
{
    $wgHooks['EditPageBeforeConflictDiff'][] = 'wfShowMergeConflicts';
    $wgExtensionMessagesFiles['MergeConflicts'] = dirname(__FILE__).'/MergeConflicts.i18n.php';
    $wgExtensionFunctions[] = 'wfSetupMergeConflicts';
}
else
{
    /* Refuse to work if MW_PATCH_MERGE_CONFLICTS is not defined,
       which means our patch is not applied to this installation */
    wfDebug('ATTENTION! MergeConflicts extension patch is not applied to this MediaWiki installation.
Please apply it before using this extension with the following command:
patch -d "'.$IP.'" -p0 < "'.dirname(__FILE__).'/MergeConflicts.diff"'."\n");
}

function wfSetupMergeConflicts()
{
    wfLoadExtensionMessages( 'MergeConflicts' );
}

function wfParseDiff3( $merged )
{
    $lines = explode( "\n", $merged );
    $conflicts = array();
    $precontext = array();
    $to = NULL;
    $lineno = array( 0, 0, 0 );
    $m_mine = '<<<<<<< '.wfMsg( 'merge-mine' );
    $m_old = '||||||| '.wfMsg( 'merge-old' );
    $m_their = '>>>>>>> '.wfMsg( 'merge-their' );
    foreach ( $lines as $line )
    {
        if ( trim( $line ) == $m_mine )
        {
            $conflictline = $lineno;
            $mine = array();
            $to = &$mine;
        }
        elseif ( trim( $line ) == $m_old )
        {
            $old = array();
            $to = &$old;
        }
        elseif ( trim( $line ) == '=======' && $old !== NULL )
        {
            $their = array();
            $to = &$their;
        }
        elseif ( trim( $line ) == $m_their )
        {
            $postcontext = array();
            unset( $to );
            $lineno[0] += count( $mine );
            $lineno[1] += count( $old );
            $lineno[2] += count( $their );
        }
        else
        {
            if ( $to !== NULL )
                $to[] = $line;
            else
            {
                $lineno[0]++;
                $lineno[1]++;
                $lineno[2]++;
                if ( $postcontext !== NULL )
                {
                    $postcontext[] = $line;
                    if ( count( $postcontext ) >= 3 )
                    {
                        $conflicts[] = array(
                            'line'  => $conflictline,
                            'pre'   => $precontext,
                            'mine'  => $mine,
                            'old'   => $old,
                            'their' => $their,
                            'post'  => $postcontext,
                        );
                        $postcontext = $mine = $old = $their = $precontext = NULL;
                    }
                }
                else
                {
                    $precontext[] = $line;
                    if ( count( $precontext ) > 3 )
                        array_shift( $precontext );
                }
            }
        }
    }
    if ( $postcontext !== NULL )
        $conflicts[] = array(
            'line'  => $conflictline,
            'pre'   => $precontext,
            'mine'  => $mine,
            'old'   => $old,
            'their' => $their,
            'post'  => $postcontext,
        );
    return $conflicts;
}

function wfFormatDiff3Conflicts( $conflicts )
{
    $html =
        '<tr><th colspan="2">'.wfMsg('yourtext').
        '</th><th colspan="2">'.wfMsg('basetext').
        '</th><th colspan="2">'.wfMsg('storedversion').'</th></tr>';
    foreach ( $conflicts as $conflict )
    {
        $html .= '<tr><th colspan="2" style="text-align: left">'.
            wfMsg('lineno', $conflict['line'][0]+1-count($conflict['pre'])).
            '</th><th colspan="2" style="text-align: left">'.
            wfMsg('lineno', $conflict['line'][1]+1-count($conflict['pre'])).
            '</th><th colspan="2" style="text-align: left">'.
            wfMsg('lineno', $conflict['line'][2]+1-count($conflict['pre'])).
            '</th></tr>';
        $lines = max( count( $conflict['mine'] ), count( $conflict['old'] ), count( $conflict['their'] ) );
        $lines_with_context = count( $conflict['pre'] ) + count( $conflict[ 'post' ] ) + $lines;
        foreach ( $conflict['pre'] as $i => $str )
        {
            $str = htmlspecialchars( $str );
            if ( $str == '' )
                $str = '&nbsp;';
            $str = "<td>$str</td>";
            if ( !$i )
                $str = '<th rowspan="'.$lines_with_context.'" class="diff3_pad"></th>'.$str;
            $html .= "<tr class='diff3_context'>$str$str$str</tr>";
        }
        for ( $i = 0; $i < $lines; $i++ )
        {
            $mine = htmlspecialchars( $conflict['mine'][$i] );
            $old = htmlspecialchars( $conflict['old'][$i] );
            $their = htmlspecialchars( $conflict['their'][$i] );
            if ( $mine == '' && $old == '' && $their == '' )
                $mine = '&nbsp;';
            $html .=
                "<tr><td class='diff3_mine'>$mine".
                "</td><td class='diff3_old'>$old".
                "</td><td class='diff3_their'>$their".
                "</td></tr>";
        }
        foreach ( $conflict['post'] as $str )
        {
            $str = htmlspecialchars($str);
            if ( $str == '' )
                $str = '&nbsp;';
            $html .= "<tr class='diff3_context'><td>$str</td><td>$str</td><td>$str</td></tr>";
        }
    }
    $html = "<table class='diff3_table'>$html</table>";
    return $html;
}

function wfShowMergeConflicts( $editpage, $out )
{
    global $wgDiff3;
    if ( !$editpage->mMergeAvailable )
        return true;
    $out->wrapWikiMsg( '<h2>$1</h2>', "yourdiff" );

    $conflicts = wfParseDiff3( $editpage->textbox1 );
    $html = wfFormatDiff3Conflicts( $conflicts );
    $out->addHeadItem( 'mergeconflicts-css',
'<style type="text/css">
.diff3_table td { white-space: pre-wrap; font-family: monospace; vertical-align: top; }
.diff3_context td { background-color: #e0e0e0; }
.diff3_mine { background-color: #ffcccc; }
.diff3_old { background-color: #ccffcc; }
.diff3_their { background-color: #ffffaa; }
.diff3_pad { width: 1em; }
</style>');
    $out->addHTML( $html );

    $out->wrapWikiMsg( '<h2>$1</h2>', "yourtext" );
    $editpage->showTextbox2();

    return false;
}
