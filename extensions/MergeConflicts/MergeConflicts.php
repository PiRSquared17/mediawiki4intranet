<?php

/* MergeConflicts extension
 * Copyright (c) 2011, Vitaliy Filippov <vitalif[d.o.g]mail.ru>
 * License: GPLv3.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#### ENGLISH ####

# (!) This extension requires a patch into core MediaWiki code (!)
# Apply it with GNU patch utility, with the following command:
# patch -p0 -d <your_mediawiki_directory> < MergeConflicts.diff

# When $wgDiff3 is available, this extension enables 3-column display of
# edit conflicts and also places the merged text with conflicts bracketed
# in the standard diff3 style (see 'man diff3') into the upper textbox.

# When $wgDiff3 is unavailable, the patch coming with this extension swaps
# upper and lower textboxes, so your text is not places into the second
# textbox, but remains in the first one.
# WARNING: this changes core MediaWiki editing behaviour and, despite of that
# changed behaviour is usually more convenient, it could be confusing for your users
# and does not force them to copy their changes from the lower textbox into the
# upper one, which (as MediaWiki authors probably think) could lead to that they'll
# always overwrite conflicting changes with their own simply clicking on "Save" and
# not reading any warnings.

#### РУССКИЙ ####

# (!) Расширение для работы требует патча в код MediaWiki (!)
# Применяйте его утилитой GNU patch и командой:
# patch -p0 -d <ДИРЕКТОРИЯ_С_MEDIAWIKI> < MergeConflicts.diff

# Когда $wgDiff3 установлен, это расширение включает показ конфликтов
# редактирования в 3 колонки и помещает объединённый текст со стандартным
# выделением конфликтов в верхнее поле редактирования.

# Когда $wgDiff3 недоступен, патч, идущий в комплекте с этим расширением,
# меняет верхнее и нижнее поля местами, так что ваш текст остаётся в верхнем поле,
# а не перемещается в нижнее, как это происходит в обычной MediaWiki.
# ВНИМАНИЕ: это меняет базовое поведение MediaWiki при конфликтах редактирования,
# и хотя изменённое поведение обычно более удобно, оно может быть непривычно для ваших
# пользователей и не заставляет их копировать их изменения из нижнего поля в верхнее,
# а это (как, по-видимому, думают авторы MediaWiki) может привести к тому, что они
# просто будут перезаписывать чужие правки своими, тупо нажимая "Сохранить" и не читая
# предупреждения.

$wgExtensionCredits['other'][] = array(
    'name'           => 'MergeConflicts',
    'version'        => '2011-05-16',
    'author'         => 'Vitaliy Filippov',
    'url'            => 'http://wiki.4intra.net/MergeConflicts',
    'description'    => 'Allows 3-column edit conflict display and swaps editboxes on conflict',
);

if ( defined( 'MW_PATCH_MERGE_CONFLICTS' ) )
{
    $wgHooks['EditPageBeforeConflictDiff'][] = 'wfShowMergeConflicts';
    $wgExtensionMessagesFiles['MergeConflicts'] = dirname(__FILE__).'/MergeConflicts.i18n.php';
    $wgExtensionFunctions[] = 'wfSetupMergeConflicts';
}
elseif ( !$_SERVER['SERVER_NAME'] )
{
    /* Refuse to work if MW_PATCH_MERGE_CONFLICTS is not defined,
       which means our patch is not applied to this installation */
    die( 'ATTENTION! MergeConflicts extension patch is not applied to this MediaWiki installation.
Please apply it before using this extension with the following command:
patch -d "'.$IP.'" -p0 < "'.dirname(__FILE__).'/MergeConflicts.diff"'."\n" );
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
    $postcontext = $mine = $old = $their = NULL;
    $lineno = array( 0, 0, 0 );
    $m_mine = '<<<<<<< '.wfMsg( 'merge-mine' );
    $m_empty_mine = '<<<<<<< '.wfMsg( 'merge-old' );
    $m_old = '||||||| '.wfMsg( 'merge-old' );
    $m_their = '>>>>>>> '.wfMsg( 'merge-their' );
    foreach ( $lines as $line )
    {
        if ( trim( $line ) == $m_mine )
        {
            if ( $postcontext !== NULL )
            {
                $conflicts[] = array(
                    'line'  => $conflictline,
                    'pre'   => $precontext,
                    'mine'  => $mine,
                    'old'   => $old,
                    'their' => $their,
                    'post'  => $postcontext,
                );
                $postcontext = $mine = $old = $their = NULL;
                $precontext = array();
            }
            $conflictline = $lineno;
            $mine = array();
            $to = &$mine;
        }
        elseif ( trim( $line ) == $m_empty_mine )
        {
            if ( $postcontext !== NULL )
            {
                $conflicts[] = array(
                    'line'  => $conflictline,
                    'pre'   => $precontext,
                    'mine'  => $mine,
                    'old'   => $old,
                    'their' => $their,
                    'post'  => $postcontext,
                );
                $postcontext = $mine = $old = $their = NULL;
                $precontext = array();
            }
            // diff3 emits "<<< old ... === ... >>> (their)" when '<<< my' is empty
            // instead of "<<< mine ||| old === ... >>> (their)".
            $conflictline = $lineno;
            $mine = array();
            $old = array();
            $to = &$old;
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
            if ( isset( $to ) )
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
                        $postcontext = $mine = $old = $their = NULL;
                        $precontext = array();
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
    // Table header
    $html =
        '<tr><th colspan="2">'.wfMsg('yourtext').
        '</th><th colspan="2">'.wfMsg('basetext').
        '</th><th colspan="2">'.wfMsg('storedversion').'</th></tr>';
    foreach ( $conflicts as $conflict )
    {
        // Conflict header
        $html .= '<tr><th colspan="2" style="text-align: left">'.
            wfMsg('lineno', $conflict['line'][0]+1-count($conflict['pre'])).
            '</th><th colspan="2" style="text-align: left">'.
            wfMsg('lineno', $conflict['line'][1]+1-count($conflict['pre'])).
            '</th><th colspan="2" style="text-align: left">'.
            wfMsg('lineno', $conflict['line'][2]+1-count($conflict['pre'])).
            '</th></tr>';
        $lines = max( count( $conflict['mine'] ), count( $conflict['old'] ), count( $conflict['their'] ) );
        $lines_with_context = count( $conflict['pre'] ) + count( $conflict[ 'post' ] ) + $lines;
        $tr = array();
        // Pre-context
        foreach ( $conflict['pre'] as $i => $str )
        {
            $str = htmlspecialchars( $str );
            if ( $str == '' )
                $str = '&nbsp;';
            $str = "<td>$str</td>";
            $tr[] = array( ' class="diff3_context"', $str, $str, $str );
        }
        // Conflicting lines
        for ( $i = 0; $i < $lines; $i++ )
        {
            $mine = htmlspecialchars( isset( $conflict['mine'][$i] ) ? $conflict['mine'][$i] : '' );
            $old = htmlspecialchars( isset( $conflict['old'][$i] ) ? $conflict['old'][$i] : '' );
            $their = htmlspecialchars( isset( $conflict['their'][$i] ) ? $conflict['their'][$i] : '' );
            if ( $mine == '' && $old == '' && $their == '' )
                $mine = '&nbsp;';
            $tr[] = array( '',
                "<td class='diff3_mine'>$mine</td>",
                "<td class='diff3_old'>$old</td>",
                "<td class='diff3_their'>$their</td>",
            );
        }
        // Post-context
        foreach ( $conflict['post'] as $str )
        {
            $str = htmlspecialchars($str);
            if ( $str == '' )
                $str = '&nbsp;';
            $str = "<td>$str</td>";
            $tr[] = array(' class="diff3_context"', $str, $str, $str);
        }
        // Add spanned cells for margins into the first row
        $str = '<th rowspan="'.$lines_with_context.'" class="diff3_pad"></th>';
        $tr[0] = array( $tr[0][0], $str, $tr[0][1], $str, $tr[0][2], $str, $tr[0][3] );
        foreach ( $tr as $t )
            $html .= '<tr'.array_shift($t).'>'.implode('', $t).'</tr>';
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

    $out->wrapWikiMsg( '<h2>$1</h2>', "storedversion" );
    $editpage->textbox2 = $editpage->getContent();
    $editpage->showTextbox2();

    return false;
}
