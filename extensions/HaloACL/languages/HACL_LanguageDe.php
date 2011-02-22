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

    public $mNamespaceAliases = array(
        'ACL'       => HACL_NS_ACL,
        'ACL_talk'  => HACL_NS_ACL_TALK,
    );

    public $mPermissionDeniedPage = "Zugriff verweigert";

    public $mPetPrefixes = array(
        'page'      => 'Seite',
        'category'  => 'Kategorie',
        'namespace' => 'Namensraum',
        'property'  => 'Attribut',
        'right'     => 'Recht',
    );

    public $mGroupPrefix = 'Group';

    public $mPetAliases = array(
        'page'      => self::PET_PAGE,
        'category'  => self::PET_CATEGORY,
        'namespace' => self::PET_NAMESPACE,
        'property'  => self::PET_PROPERTY,
        'right'     => self::PET_RIGHT,
    );
}
