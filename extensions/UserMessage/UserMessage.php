<?php

/**
 * MediaWiki UserMessage extension
 * Copyright © 2009-2010 Vitaliy Filippov
 * http://yourcmc.ru/wiki/UserMessage_(MediaWiki)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

/* ABOUT */

/* This extension allows to customize messages used by wfMsg(...),
   editable through generic article edit interface (MediaWiki:...),
   to be customized on per-user basis.

   It is useful, for example, for (CustIS Bug 61726) personalization of
   MediaWiki:Edittools. Personalized messages will have names like
   MediaWiki:Edittools@UserName.
*/

/* INSTALLATION */

/* 1. Copy source to extensions/UserMessage/ subdirectory of your Wiki installation
   2. Add following lines to your LocalSettings.php:
        require_once("extensions/UserMessage/UserMessage.php");
        $wgUserMessageDelimiter = '@'; // default
        $wgUserMessageAllowCustomization = array(
            'edittools' => true,
            // 'message_key' => true for each message that you want to allow to be customized
        );
   3. Put some text on MediaWiki:editingpersonalinterface page. This text will be shown to
      users editing their personal interface messages.
*/

$wgExtensionCredits['other'][] = array(
    'name'         => 'User Message',
    'version'      => '2010-12-03',
    'author'       => 'Vitaliy Filippov',
    'url'          => 'http://yourcmc.ru/wiki/index.php/UserMessage_(MediaWiki)',
    'description'  => 'Allows customization of MediaWiki:xxx messages on a per-user basis',
);
$wgHooks['NormalizeMessageKey'][] = 'efUserMessageNormalizeMessageKey';
$wgHooks['userCan'][] = 'efUserMessageAllowEditPersonalMessages';
$wgExtensionMessagesFiles['UserMessage'] = dirname(__FILE__) . '/UserMessage.i18n.php';
if (!isset($wgUserMessageAllowCustomization))
    $wgUserMessageAllowCustomization = array('edittools' => true);

function efUserMessageIsPersonalMessage($title)
{
    global $wgContLang, $wgUserMessageAllowCustomization, $wgUserMessageDelimiter;
    /* Match keys like MediaWiki:Something_Customisable@UserName */
    if (!$wgUserMessageDelimiter)
        $wgUserMessageDelimiter = '@';
    return $title->getNamespace() == NS_MEDIAWIKI &&
        ($newkey = $wgContLang->lcfirst($title->getText())) &&
        ($p = mb_strrpos($newkey, $wgUserMessageDelimiter)) !== false &&
        $wgUserMessageAllowCustomization[mb_substr($newkey, 0, $p)] &&
        User::newFromName(mb_substr($newkey, $p+mb_strlen($delim)));
}

function efUserMessageNormalizeMessageKey(&$key, &$useDB, &$langCode, &$transform)
{
    global $wgUserMessageAllowCustomization, $wgUserMessageDelimiter;
    global $wgUser, $wgMessageCache, $wgTitle;
    if (!$wgUserMessageDelimiter)
        $wgUserMessageDelimiter = '@';
    if (array_key_exists($key, $wgUserMessageAllowCustomization) && $wgUser && $wgUser->getID() &&
        is_object($wgMessageCache))
    {
        /* This is a customisable message */
        $newkey = $key.$wgUserMessageDelimiter.$wgUser->getName();
        if (!wfEmptyMsg($newkey, $wgMessageCache->get($newkey, true, $langCode)))
            $key = $newkey;
    }
    elseif (($p = mb_strrpos($key, $wgUserMessageDelimiter)) !== false &&
        $wgUserMessageAllowCustomization[mb_substr($key, 0, $p)] &&
        User::newFromName(mb_substr($key, $p+mb_strlen($wgUserMessageDelimiter))))
    {
        /* Personal message is requested, but no such exists for a user,
           so try a default one */
        $key = mb_substr($key, 0, $p);
        $useDB = true;
    }
    elseif ($key == 'editinginterface' &&
        efUserMessageIsPersonalMessage($wgTitle))
    {
        wfLoadExtensionMessages('UserMessage');
        /* We are editing a personal message */
        $key = 'editingpersonalinterface';
        $useDB = true;
    }
    return true;
}

function efUserMessageAllowEditPersonalMessages(&$title, &$user, $action, &$result)
{
    if (efUserMessageIsPersonalMessage($title) && ($action == 'edit' || $action == 'create'))
    {
        /* Allow to edit personal messages */
        $result = true;
        return false;
    }
    return true;
}
