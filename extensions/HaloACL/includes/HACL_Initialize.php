<?php

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
 * This is the main entry file for the Halo-Access-Control-List extension.
 * It contains mainly constants for the configuration of the extension. This
 * file has to be included in LocalSettings.php to enable the extension. The
 * constants defined here can be overwritten in LocalSettings.php. After that
 * the function enableHaloACL() must be called.
 *
 * @author Thomas Schweitzer
 *
 */
if (!defined('MEDIAWIKI'))
    die("This file is part of the HaloACL extension. It is not a valid entry point.");

define('HACL_STORE_SQL', 'HaclStoreSQL');
// constant for special schema properties

###
# This is the path to your installation of HaloACL as seen on your
# local filesystem. Used against some PHP file path issues.
##
if (!$haclgIP)
    $haclgIP = $IP . '/extensions/HaloACL';
##

###
# This is the path to your installation of HaloACL as seen from the
# web. Change it if required ($wgScriptPath is the path to the base directory
# of your wiki). No final slash.
##
if (!$haclgHaloScriptPath)
    $haclgHaloScriptPath = 'extensions/HaloACL';

###
# Set this variable to false to disable the patch that checks all titles
# for accessibility. Unfortunately, the Title-object does not check if an article
# can be accessed. A patch adds this functionality and checks every title that is
# created. If a title can not be accessed, a replacement title called "Permission
# denied" is returned. This is the best and securest way of protecting an article,
# however, it slows down things a bit.
##
if ($haclgEnableTitleCheck === NULL)
    $haclgEnableTitleCheck = false;

###
# This variable controls the behaviour of unreadable articles included into other
# articles. When it is a non-empty string, it is treated as the name of a message
# inside MediaWiki: namespace (i.e. localisation message). When it is set to an
# empty string, nothing is displayed in place of protected inclusion. When it is
# set to boolean FALSE, inclusion directive is shown instead of article content.
###
if ($haclgInclusionDeniedMessage === NULL)
    $haclgInclusionDeniedMessage = 'haloacl-inclusion-denied';

###
# This flag applies to articles that have or inherit no security descriptor.
#
# true
#    If this value is <true>, all articles that have no security descriptor are
#    fully accessible for HaloACL. Other extensions or $wgGroupPermissions can
#     still prohibit access.
#    Remember that security descriptor are also inherited via categories or
#    namespaces.
# false
#    If it is <false>, no access is granted at all. Only the latest author of an
#    article can create a security descriptor.
if ($haclgOpenWikiAccess === NULL)
    $haclgOpenWikiAccess = true;

###
# true
#    If this value is <true>, semantic properties can be protected.
# false
#    If it is <false>, semantic properties are not protected even if they have
#     security descriptors.
if ($haclgProtectProperties === NULL)
    $haclgProtectProperties = false;

##
# By design several databases can be connected to HaloACL. (However, in the first
# version there is only an implementation for MySQL.) With this variable you can
# specify which store will actually be used.
# Possible values:
# - HACL_STORE_SQL
if ($haclgBaseStore === NULL)
    $haclgBaseStore = HACL_STORE_SQL;

##
# Values of this array are treated as language-dependent names of namespaces which
# can not be protected by HaloACL.
if ($haclgUnprotectableNamespaces === NULL)
    $haclgUnprotectableNamespaces = array();

##
# If this is true, "ACL" tab will be hidden for unprotected pages.
if ($haclgDisableACLTab === NULL)
    $haclgDisableACLTab = false;

##
# If $haclgEvaluatorLog is <true>, you can specify the URL-parameter "hacllog=true".
# In this case HaloACL echos the reason why actions are permitted or prohibited.
if ($haclgEvaluatorLog === NULL)
    $haclgEvaluatorLog = true;

##
# This key is used for protected properties in Semantic Forms. SF has to embed
# all values of input fields into the HTML of the form, even if fields are protected
# and not visible to the user (i.e. user has no right to read.) The values of
# all protected fields are encrypted with the given key.
# YOU SHOULD CHANGE THIS KEY AND KEEP IT SECRET.
if ($haclgEncryptionKey === NULL)
    $haclgEncryptionKey = "Es war einmal ein Hase.";

# load global functions
require_once('HACL_GlobalFunctions.php');

###
# If you already have custom namespaces on your site, insert
#    $haclgNamespaceIndex = ???;
# into your LocalSettings.php *before* including this file. The number ??? must
# be the smallest even namespace number that is not in use yet. However, it
# must not be smaller than 100.
##
if (!$haclgNamespaceIndex)
    $haclgNamespaceIndex = 102;
haclfInitNamespaces();

// mediawiki-groups that may change Quick ACL of other users
if ($haclCrossTemplateAccess === NULL)
    $haclCrossTemplateAccess = array('bureaucrat');

// add rights that are newly available with the haloACL
$wgAvailableRights[] = 'propertyread';
$wgAvailableRights[] = 'propertyformedit';
$wgAvailableRights[] = 'propertyedit';
$wgGroupPermissions['*']['propertyread'] = true;
$wgGroupPermissions['*']['propertyformedit'] = true;
$wgGroupPermissions['*']['propertyedit'] = true;
