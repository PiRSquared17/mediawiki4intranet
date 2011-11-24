<?php

/**
 * MediaWiki PositivePageRate extension
 * Copyright © 2010 Vitaliy Filippov
 * http://yourcmc.ru/wiki/PositivePageRate_(MediaWiki)
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

if (!defined('MEDIAWIKI'))
    die();

/* INSTALLATION
   $egPositivePageRateAllowNegative = false;    // Allow negative ("-") votes?
   $egPositivePageRateAllowRecall = false;      // Allow recalling votes?
   $egPositivePageRateHideLog = false;          // Don't show detailed rate/view logs for pages?
   $egPositivePageRateAnonymousLog = false;     // Don't show any user names in detailed logs, but only +/-/view?
   require_once("$IP/extensions/PositivePageRate/PositivePageRate.php");
*/

$wgExtensionCredits['specialpage'][] = array(
    'name'           => 'PositivePageRate',
    'version'        => '0.91 (2010-04-26)',
    'author'         => 'Vitaliy Filippov',
    'url'            => 'http://yourcmc.ru/wiki/index.php/PositivePageRate_(MediaWiki)',
    'description'    => 'Yet another page rating system counting unique views and positive (and optionally negative) votes. It also enables a distinct user access log file.',
    'descriptionmsg' => 'pprate-desc',
);
$wgHooks['LoadExtensionSchemaUpdates'][] = 'PositivePageRate::LoadExtensionSchemaUpdates';
$wgHooks['ArticleViewHeader'][] = 'PositivePageRate::ArticleViewHeader';
$wgHooks['UnknownAction'][] = 'PositivePageRate::UnknownAction';
$wgHooks['MediaWikiPerformAction'][] = 'PositivePageRate::MediaWikiPerformAction';
$wgHooks['SkinBuildSidebar'][] = 'PositivePageRate::SkinBuildSidebar';
$wgExtensionMessagesFiles['PositivePageRate'] = dirname(__FILE__) . '/PositivePageRate.i18n.php';
$wgAutoloadClasses['PositivePageRate'] = dirname(__FILE__) . '/PositivePageRate.class.php';
$wgAutoloadClasses['SpecialPositivePageRate'] = dirname(__FILE__) . '/PositivePageRate.special.php';
$wgSpecialPages['PositivePageRate'] = 'SpecialPositivePageRate';
$wgSpecialPageGroups['PositivePageRate'] = 'highuse';
