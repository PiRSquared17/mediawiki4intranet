<?php

# THIS FILE IS OBSOLETE
# RUN maintenance/update.php INSTEAD!

/* Copyright 2010+, Vitaliy Filippov <vitalif[d.o.g]mail.ru>
 *                  Stas Fomin <stas.fomin[d.o.g]yandex.ru>
 * This file is part of heavily modified "Web 1.0" HaloACL-extension.
 * http://wiki.4intra.net/Mediawiki4Intranet
 * $Id: $
 *
 * Copyright 2009, ontoprise GmbH
 *
 * The HaloACL-Extension is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The HaloACL-Extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * OBSOLETE maintenance script for setting up the database tables for HaloACL
 * Still works, but is now ran from standard MediaWiki's maintenance/update.php
 *
 * @author Thomas Schweitzer
 * Date: 21.04.2009
 */
if (array_key_exists('SERVER_NAME', $_SERVER) && $_SERVER['SERVER_NAME'] != NULL)
{
    echo "Invalid access! A maintenance script MUST NOT be accessed from remote.";
    return;
}

$mediaWikiLocation = dirname(__FILE__) . '/../../..';
require_once "$mediaWikiLocation/maintenance/commandLine.inc";
$dir = dirname(__FILE__);
$haclgIP = "$dir/../../HaloACL";

require_once("$haclgIP/includes/HACL_Storage.php");
require_once("$haclgIP/includes/HACL_GlobalFunctions.php");

if (array_key_exists('delete', $options))
    $_ENV['HACL_DELETE_DB'] = true;

global $wgLanguageCode;
haclfInitContentLanguage($wgLanguageCode);
haclfInitDatabase();
