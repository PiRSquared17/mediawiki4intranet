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

/**
 * German language labels for important HaloACL labels (namespaces, ,...).
 *
 * @author Thomas Schweitzer
 */
class HACLLanguageDe extends HACLLanguage
{
    public $mNamespaces = array(
        HACL_NS_ACL       => 'Rechte',
        HACL_NS_ACL_TALK  => 'Rechte_Diskussion'
    );

    public $mPermissionDeniedPage = "Zugriff verweigert";

    public $mParserFunctions = array(
        HACLLanguage::PF_ACCESS             => 'Zugriff',
        HACLLanguage::PF_MANAGE_RIGHTS      => 'Rechte verwalten',
        HACLLanguage::PF_MANAGE_GROUP       => 'Gruppe verwalten',
        HACLLanguage::PF_PREDEFINED_RIGHT   => 'vordefiniertes Recht',
        HACLLanguage::PF_PROPERTY_ACCESS    => 'Attributzugriff',
        HACLLanguage::PF_WHITELIST          => 'Whitelist',
        HACLLanguage::PF_MEMBER             => 'Mitglied'
    );

    public $mParserFunctionsParameters = array(
        HACLLanguage::PFP_ASSIGNED_TO   => 'zugewiesen',
        HACLLanguage::PFP_ACTIONS       => 'Aktionen',
        HACLLanguage::PFP_DESCRIPTION   => 'Beschreibung',
        HACLLanguage::PFP_RIGHTS        => 'Rechte',
        HACLLanguage::PFP_PAGES         => 'Seiten',
        HACLLanguage::PFP_MEMBERS       => 'Mitglieder',
        HACLLanguage::PFP_NAME          => 'Name',
    );

    public $mActionNames = array(
        HACLLanguage::RIGHT_READ         => 'lesen',
        HACLLanguage::RIGHT_FORMEDIT     => 'formulareditieren',
        HACLLanguage::RIGHT_WYSIWYG      => 'wysiwyg',
        HACLLanguage::RIGHT_EDIT         => 'editieren',
        HACLLanguage::RIGHT_CREATE       => 'erzeugen',
        HACLLanguage::RIGHT_MOVE         => 'verschieben',
        HACLLanguage::RIGHT_ANNOTATE     => 'annotieren',
        HACLLanguage::RIGHT_DELETE       => 'löschen',
        HACLLanguage::RIGHT_ALL_ACTIONS  => '*',
    );

    public $mCategories = array(
        HACLLanguage::CAT_GROUP => 'Kategorie:Rechte/Gruppe',
        HACLLanguage::CAT_RIGHT => 'Kategorie:Rechte/Recht',
        HACLLanguage::CAT_SECURITY_DESCRIPTOR => 'Kategorie:Rechte/Sicherheitsbeschreibung',
    );

    public $mWhitelist = 'Positivliste';

    public $mPetPrefixes = array(
        HACLSecurityDescriptor::PET_PAGE      => 'Seite',
        HACLSecurityDescriptor::PET_CATEGORY  => 'Kategorie',
        HACLSecurityDescriptor::PET_NAMESPACE => 'Namensraum',
        HACLSecurityDescriptor::PET_PROPERTY  => 'Attribut',
        HACLSecurityDescriptor::PET_RIGHT     => 'Recht',
    );

    public $mSDTemplateName = 'Vorlage';
    public $mPredefinedRightName = 'Recht';

    public $mPrefixes = array(
        'group'     => 'group',
        'template'  => 'right',
        'page'      => 'sd',
        'category'  => 'sd',
        'namespace' => 'sd',
        'property'  => 'sd',
        'right'     => 'right',
        'vorlage'   => 'right',
        'seite'     => 'sd',
        'kategorie' => 'sd',
        'namensraum' => 'sd',
        'attribut'  => 'sd',
        'recht'     => 'right',
    );

    public $mPetAliases = array(
        'page'      => HACLSecurityDescriptor::PET_PAGE,
        'category'  => HACLSecurityDescriptor::PET_CATEGORY,
        'namespace' => HACLSecurityDescriptor::PET_NAMESPACE,
        'property'  => HACLSecurityDescriptor::PET_PROPERTY,
        'right'     => HACLSecurityDescriptor::PET_RIGHT,
        'seite'     => HACLSecurityDescriptor::PET_PAGE,
        'kategorie' => HACLSecurityDescriptor::PET_CATEGORY,
        'namensraum' => HACLSecurityDescriptor::PET_NAMESPACE,
        'attribut'  => HACLSecurityDescriptor::PET_PROPERTY,
        'recht'     => HACLSecurityDescriptor::PET_RIGHT,
    );
}
