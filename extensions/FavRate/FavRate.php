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

/* INSTALLATION:

require_once("$IP/extensions/FavRate/FavRate.php");

// Enable collecting of unique visitor logs for each page (false by default)
$egFavRateLogVisitors = true;

// Make all visitor logs public (false by default)
$egFavRatePublicLogs = true;

// Maximum value for logarithmic scale of hit count
$egFavRateMaxHits = 100000;

// Maximum value for logarithmic scale of favorite count
$egFavRateMaxFav = 100;

// Maximum value for logarithmic scale of link count
$egFavRateMaxLinks = 100;

// Hit count scale color
$egFavRateHitsColor = "#0c0";

// Favorites count scale color
$egFavRateFavColor = "#c00";

// Link count scale color
$egFavRateLinksColor = "#00c";

*/

$wgExtensionCredits['specialpage'][] = array(
    'name'           => 'FavRate',
    'version'        => '0.91 (2010-04-26)',
    'author'         => 'Vitaliy Filippov',
    'url'            => 'http://wiki.4intra.net/FavRate',
    'description'    => 'Yet another page rating system for MediaWiki.',
    'descriptionmsg' => 'favrate-desc',
);
$wgHooks['LoadExtensionSchemaUpdates'][] = 'FavRate::LoadExtensionSchemaUpdates';
$wgHooks['ArticleViewHeader'][] = 'FavRate::ArticleViewHeader';
$wgHooks['UnknownAction'][] = 'FavRate::UnknownAction';
$wgHooks['SkinBuildSidebar'][] = 'FavRate::SkinBuildSidebar';
$wgExtensionMessagesFiles['FavRate'] = dirname(__FILE__) . '/FavRate.i18n.php';
$wgAutoloadClasses['FavRate'] = dirname(__FILE__) . '/FavRate.class.php';
$wgAutoloadClasses['SpecialFavRate'] = dirname(__FILE__) . '/FavRate.special.php';
$wgSpecialPages['FavRate'] = 'SpecialFavRate';
$wgSpecialPageGroups['FavRate'] = 'highuse';
$wgAjaxExportList[] = 'efFavRateSet';

// Default configuration values
$egFavRateLogVisitors = false;
$egFavRatePublicLogs = false;
$egFavRateMaxHits = 100000;
$egFavRateMaxFav = 100;
$egFavRateMaxLinks = 100;
$egFavRateHitsColor = "#0c0";
$egFavRateFavColor = "#c00";
$egFavRateLinksColor = "#00c";

function efFavRateSet($pageid, $addremove)
{
    return FavRate::setFavorite($pageid, $addremove);
}
