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

if (!defined('MEDIAWIKI')) die();

/* Russian language labels for important HaloACL labels (namespaces, ,...) */
class HACLLanguageRu extends HACLLanguage
{
    public $mNamespaces = array(
        HACL_NS_ACL       => 'ACL',
        HACL_NS_ACL_TALK  => 'ACL_talk',
    );

    public $mPermissionDeniedPage = 'Доступ запрещён';

    public $mParserFunctions = array(
        HACLLanguage::PF_ACCESS             => 'access',
        HACLLanguage::PF_MANAGE_RIGHTS      => 'manage rights',
        HACLLanguage::PF_MANAGE_GROUP       => 'manage group',
        HACLLanguage::PF_PREDEFINED_RIGHT   => 'predefined right',
        HACLLanguage::PF_PROPERTY_ACCESS    => 'property access',
        HACLLanguage::PF_WHITELIST          => 'whitelist',
        HACLLanguage::PF_MEMBER             => 'member'
    );

    public $mParserFunctionsParameters = array(
        HACLLanguage::PFP_ASSIGNED_TO   => 'assigned to',
        HACLLanguage::PFP_ACTIONS       => 'actions',
        HACLLanguage::PFP_DESCRIPTION   => 'description',
        HACLLanguage::PFP_RIGHTS        => 'rights',
        HACLLanguage::PFP_PAGES         => 'pages',
        HACLLanguage::PFP_MEMBERS       => 'members',
        HACLLanguage::PFP_NAME          => 'name',
    );

    public $mActionNames = array(
        HACLLanguage::RIGHT_READ         => 'read',
        HACLLanguage::RIGHT_EDIT         => 'edit',
        HACLLanguage::RIGHT_CREATE       => 'create',
        HACLLanguage::RIGHT_DELETE       => 'delete',
        HACLLanguage::RIGHT_MOVE         => 'move',
        HACLLanguage::RIGHT_ALL_ACTIONS  => '*',
        'read'      => 'Read',
        'edit'      => 'Edit',
        'create'    => 'Create',
        'delete'    => 'Delete',
        'move'      => 'Move',
        'manage'    => 'Manage',
        'all'       => 'Full Access',
    );

    public $mPetPrefixes = array(
        'page'      => 'Page',
        'category'  => 'Category',
        'namespace' => 'Namespace',
        'property'  => 'Property',
        'right'     => 'Right',
        'template'  => 'Template',
    );

    public $mPredefinedRightName = 'Right';
    public $mSDTemplateName = 'Template';
    public $mGroupPrefix = 'Group';

    public $mWhitelist = 'Whitelist';

    public $mPrefixes = array(
        'group'     => 'group',
        'template'  => 'right',
        'page'      => 'sd',
        'category'  => 'sd',
        'namespace' => 'sd',
        'property'  => 'sd',
        'right'     => 'right',
        'категория' => 'sd',
    );

    public $mPetAliases = array(
        'page'      => 'page',
        'category'  => 'category',
        'namespace' => 'namespace',
        'property'  => 'property',
        'right'     => 'right',
        'template'  => 'template',
        'категория' => 'category',
    );
}
