<?php
/**
 * SupaMW.php -- SUPA java applet support for uploading images
 * from clipboard directly into MediaWiki
 * Copyright 2011+ Vitaliy Filippov <vitalif@mail.ru>
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

// DESCRIPTION:
// This extension adds an extra "Clipboard" option to the normal file upload page,
// so you can upload images to MediaWiki directly from the clipboard.
// SUPA applet is used for that (http://supa.sourceforge.net/)
// REQUIREMENTS: PHP 5, MediaWiki 1.16, Java on client machines.
// INSTALLATION:
// 1) Make sure that you either don't have PHP Suhosin extension installed,
//    or that suhosin.post.max_value_length AND suhosin.request.max_value_length
//    settings are enough to pass screenshots. They are 65000 by default on stock Suhosin,
//    and 1000000 (i.e. 1 decimal megabyte) on Debian's Suhosin, which is too low to pass
//    through large images.
//    This is required because SUPA images are uploaded not as "file" parts, but as
//    "normal" POST values. This is a sort of the hack, but other ways to integrate
//    SUPA with normal MediaWiki file upload form are much more tricky.
// 2) Put the following into your LocalSettings.php:
//    require_once "$IP/extensions/SupaMW/SupaMW.php";

$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['SupaMW'] = $dir . 'SupaMW.class.php';
$wgAutoloadClasses['UploadFromSupa'] = $dir . 'SupaMW.class.php';
$wgExtensionMessagesFiles['SupaMW'] = $dir . 'SupaMW.i18n.php';
$wgHooks['UploadFormSourceDescriptors'][] = 'SupaMW::uploadForm';
$wgHooks['UploadCreateFromRequest'][] = 'SupaMW::addHandler';

// Extension credits that will show up on Special:Version
$wgExtensionCredits['other'][] = array(
    'path'        => __FILE__,
    'name'        => 'SupaMW',
    'version'     => '0.9 (2011-11-23)',
    'author'      => 'Vitaliy Filippov',
    'url'         => 'http://wiki.4intra.net/SupaMW',
    'description' => 'SUPA java applet support for pasting images from clipboard directly into MediaWiki',
);
