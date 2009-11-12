<?php

/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * ATTENTION: This extension requires a patched includes/UserMailer.php
 *            See ../../custisinstall/includes.diff
 *            PreferencesExtension <http://www.mediawiki.org/wiki/Extension:PreferencesExtension> is also needed
 *
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if (!defined('MEDIAWIKI'))
    die();

$wgExtensionFunctions[] = "wfEnotifDiff";
$wgExtensionMessagesFiles[EnotifDiff] = dirname(__FILE__) . '/EnotifDiff.i18n.php';
$wgExtensionCredits[other][] = array (
    name        => 'Differences in Enotify mail',
    description => 'Your MediaWiki will get an ability to send page diffs in enotify mail messages',
    author      => 'Vitaliy Filippov',
    url         => 'http://lib.custis.ru/index.php/EnotifDiff',
    version     => '1.0 (2009-04-01)',
);

function wfEnotifDiff()
{
    global $wgHooks, $wgEmailContentType;
    wfLoadExtensionMessages('EnotifDiff');
    wfAddPreferences(array(
        array(
            name      => 'enotifsenddiffs',
            section   => 'prefs-personal',
            type      => PREF_TOGGLE_T,
            'default' => 0,
        ),
        array(
            name      => 'enotifsendmultiple',
            section   => 'prefs-personal',
            type      => PREF_TOGGLE_T,
            'default' => 0,
        )
    ));
    $wgHooks[EnotifComposeCommonMailtext][] = '_enotifdiff_compose_common_mailtext';
    $wgHooks[EnotifPersonalizeMailtext][] = '_enotifdiff_personalize_mailtext';
    $wgHooks[EnotifUserCondition][] = '_enotifdiff_user_condition';
    $wgEmailContentType = 'text/html';
}

function _enotifdiff_compose_common_mailtext(&$mailer, &$keys)
{
    global $wgOut;
    $oldWgOut = $wgOut;
    $wgOut = new OutputPage;
    $de = new DifferenceEngine($mailer->getTitle(), $mailer->oldid, 'next');
    $de->showDiffPage(true);
    $keys['$DIFF'] = $wgOut->getHTML();
    $keys['$DIFF'] = preg_replace('#^(.*?)<tr[^<>]*>.*?</tr\s*>#is', '\1', $keys['$DIFF'], 1);
    $keys['$DIFF'] = preg_replace('#class=[\"\']?diff-deletedline[\"\']?#is', 'style="background-color: #ffffaa"', $keys['$DIFF']);
    $keys['$DIFF'] = preg_replace('#class=[\"\']?diff-addedline[\"\']?#is', 'style="background-color: #ccffcc"', $keys['$DIFF']);
    $keys['$DIFF'] = preg_replace('#class=[\"\']?diffchange\s*diffchange-inline[\"\']?#is', 'style="color: red; font-weight: bold"', $keys['$DIFF']);
    if (trim($keys['$PAGESUMMARY']) == '-')
        $keys['$PAGESUMMARY'] = $keys['$PAGEMINOREDIT'] ? '' : wfMsg('enotif-empty-summary');
    $wgOut = $oldWgOut;
    return true;
}

function _enotifdiff_personalize_mailtext(&$mailer, &$user, &$body)
{
    $diff = '';
    if ($s = strpos($body, '</body>'))
    {
        $diff = trim(substr($body, $s+7));
        $body = substr($body, 0, $s+7);
    }
    if (!$diff)
        return true;
    if ($user->getOption('enotifsenddiffs'))
        $body = str_replace('$REALDIFF', $diff, $body);
    else
        $body = str_replace('$REALDIFF', '', $body);
    return true;
}

function _enotifdiff_user_condition(&$mailer, &$condition)
{
    $condition = str_ireplace('wl_notificationtimestamp IS NULL', '(wl_notificationtimestamp IS NULL OR user_options LIKE \'%enotifsendmultiple=1%\' OR user_options LIKE \'%enotifsenddiffs=1%\')', $condition);
    return true;
}
