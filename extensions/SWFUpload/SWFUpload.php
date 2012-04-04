<?php
/**
 * SWFUpload.php -- Flash applet support for uploading files into MediaWiki
 * Copyright 2009-2011 Vitaliy Filippov <vitalif@mail.ru>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @file
 * @ingroup Extensions
 * @author Vitaliy Filippov <vitalif@mail.ru>
 */

// INSTALLATION:
// 1) Make sure Magic Quotes are DISABLED in your PHP settings!
//    This extension won't work when they're enabled.
// 2) Put the following into your LocalSettings.php:
//    require_once "$IP/extensions/SWFUpload/SWFUpload.php";

$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['SpecialSWFUpload'] = $dir . 'SWFUpload.body.php';
$wgExtensionMessagesFiles['SWFUpload'] = $dir . 'SWFUpload.i18n.php';
$wgSpecialPages['SWFUpload'] = 'SpecialSWFUpload';
SpecialPage::setGroup('SWFUpload', 'media');

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
    'path'        => __FILE__,
    'name'        => 'SWFUpload',
    'version'     => '0.96 (2011-09-22)',
    'author'      => 'Vitaliy Filippov',
    'url'         => 'http://wiki.4intra.net/SWFUpload_(MediaWiki)',
    'description' => 'Flash applet support for uploading files into MediaWiki',
);
