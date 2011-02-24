<?php

/* Copyright 2010+, Vitaliy Filippov <vitalif[d.o.g]mail.ru>
 *                  Stas Fomin <stas.fomin[d.o.g]yandex.ru>
 * This file is part of IntraACL MediaWiki extension. License: GPLv3.
 * http://wiki.4intra.net/IntraACL
 * $Id: $
 *
 * Based on HaloACL
 * Copyright 2009, ontoprise GmbH
 *
 * The IntraACL-Extension is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The IntraACL-Extension is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Internationalization file for IntraACL
 * @author Vitaliy Filippov
 * Based on HaloACL HACL_Messages.php
 */

$messages = array();

/**
 * English
 */
$messages['en'] = array(

    // General
    'haloacl'                           => 'HaloACL',
    'hacl_special_page'                 => 'HaloACL',  // Name of the special page for administration
    'hacl_unknown_user'                 => 'The user "$1" is unknown.',
    'hacl_unknown_group'                => 'The group "$1" is unknown.',
    'hacl_missing_parameter'            => 'The parameter "$1" is missing.',
    'hacl_missing_parameter_values'     => 'There are no valid values for parameter "$1".',
    'hacl_invalid_predefined_right'     => 'A rights template with the name "$1" does not exist or it contains no valid rights definition.',
    'hacl_invalid_action'               => '"$1" is an invalid value for an action.',
    'hacl_wrong_namespace'              => 'Articles with rights or group definitions must belong to the namespace "ACL".',
    'hacl_group_must_have_members'      => 'A group must have at least one member (group or user).',
    'hacl_group_must_have_managers'     => 'A group must have at least one manager (group or user).',
    'hacl_invalid_parser_function'      => 'The use of the "#$1" function in this article is not allowed.',
    'hacl_right_must_have_rights'       => 'A right or security descriptor must contain rights or reference other rights.',
    'hacl_right_must_have_managers'     => 'A right or security descriptor must have at least one manager (group or user).',
    'hacl_pf_rightname_title'           => "===$1===\n",
    'hacl_pf_rights_title'              => "===Right(s): $1===\n",
    'hacl_pf_rights'                    => ":;Right(s):\n:: $1\n",
    'hacl_pf_right_managers_title'      => "===Right managers===\n",
    'hacl_pf_predefined_rights_title'   => "===Included rights===\n",
    'hacl_pf_group_managers_title'      => "===Group managers===\n",
    'hacl_pf_group_members_title'       => "===Group members===\n",
    'hacl_assigned_user'                => 'Assigned users: ',
    'hacl_assigned_groups'              => 'Assigned groups:',
    'hacl_user_member'                  => 'Users who are member of this group:',
    'hacl_group_member'                 => 'Groups who are member of this group:',
    'hacl_description'                  => 'Description:',
    'hacl_error'                        => 'Errors:',
    'hacl_warning'                      => 'Warnings:',
    'hacl_consistency_errors'           => '<h2>There are errors in ACL definition</h2>',
    'hacl_definitions_will_not_be_saved' => '(The definitions in this article will not be saved and they will not be taken to effect due to the following errors.)',
    'hacl_will_not_work_as_expected'    => '(Because of the following warnings, the definition will not work as expected.)',
    'hacl_errors_in_definition'         => 'The definitions in this article have errors. Please refer to the details below!',
    'hacl_anonymous_users'              => 'anonymous users',
    'hacl_registered_users'             => 'registered users',
    'hacl_acl_element_not_in_db'        => 'No entry has been made in the ACL database about this article. Please re-save it again with all the articles that use it.',
    'hacl_unprotectable_namespace'      => 'This namespace cannot be protected. Please contact the wiki administrator.',
    'hacl_permission_denied'            => "You are not allowed to perform the requested action on this page.\n\nReturn to [[Main Page]].",

    // Messages for semantic protection (properties etc.)
    'hacl_sp_query_modified'            => "- The query was modified because it contains protected properties.\n",
    'hacl_sp_empty_query'               => "- Your query consists only of protected properties. It was not executed.\n",
    'hacl_sp_results_removed'           => "- Some results were removed due to access restrictions.\n",
    'hacl_sp_cant_save_article'         => "'''The article contains the following protected properties:'''\n$1'''You do not have the permission to set their values. Please remove these properties and save again.'''",

    /**** IntraACL: ****/

    // General
    'hacl_invalid_prefix'               =>
'This page does not protect anything, create any rights or right templates.
Either it is supposed to be included into other ACL definitions, or is created incorrectly.
If you want to protect some pages, ACL page must be named as one of: ACL:Page/*, ACL:Category/*, ACL:Namespace/*, ACL:Property/*, ACL:Right/*.',
    'hacl_pe_not_exists'                => 'The element supposed to be protected with this ACL does not exist.',
    'hacl_edit_with_special'            => '<p><a href="$1"><img src="$2" width="16" height="16" alt="Edit" /> Edit this definition with HaloACL editor.</a></p><hr />',
    'hacl_create_with_special'          => '<p><a href="$1"><img src="$2" width="16" height="16" alt="Create" /> Create this definition with HaloACL editor.</a></p><hr />',
    'hacl_tab_acl'                      => 'ACL',
    'hacl_tab_page'                     => 'Page',
    'hacl_tab_category'                 => 'Category',

    // Special:HaloACL actions
    'hacl_action_acllist'               => 'Manage ACL',
    'hacl_action_acl'                   => 'Create new ACL definition',
    'hacl_action_quickaccess'           => 'Manage Quick ACL',
    'hacl_action_grouplist'             => 'Manage groups',
    'hacl_action_group'                 => 'Create a group',

    // ACL Editor
    'hacl_autocomplete_no_users'        => 'No users found',
    'hacl_autocomplete_no_groups'       => 'No groups found',
    'hacl_autocomplete_no_pages'        => 'No pages found',
    'hacl_autocomplete_no_namespaces'   => 'No namespaces found',
    'hacl_autocomplete_no_categorys'    => 'No categories found',
    'hacl_autocomplete_no_propertys'    => 'No properties found',
    'hacl_autocomplete_no_sds'          => 'No security descriptors found',

    'hacl_login_first_title'            => 'Please login',
    'hacl_login_first_text'             => 'Please [[Special:Userlogin|login]] first to use HaloACL special page.',
    'hacl_acl_create'                   => 'Create ACL definition',
    'hacl_acl_create_title'             => 'Create ACL definition: $1',
    'hacl_acl_edit'                     => 'Editing ACL definition: $1',
    'hacl_edit_default_taken'           => '\'\'Initial default content for new ACLs taken from [[$1]].\'\'',
    'hacl_edit_default_is_here'         => '\'\'You can place default content for new ACLs here: [[$1]].\'\'',
    'hacl_edit_definition_text'         => 'Definition text:',
    'hacl_edit_definition_target'       => 'Definition target:',
    'hacl_edit_modify_definition'       => 'Modify definition:',
    'hacl_edit_include_right'           => 'Include other SD:',
    'hacl_edit_include_do'              => 'Include',
    'hacl_edit_save'                    => 'Save ACL',
    'hacl_edit_create'                  => 'Create ACL',
    'hacl_edit_delete'                  => 'Delete ACL',
    'hacl_edit_protect'                 => 'Protect:',
    'hacl_edit_define'                  => 'Define:',

    'hacl_indirect_grant'               => 'This right is granted through $1, cannot revoke.',
    'hacl_indirect_grant_all'           => 'all users right',
    'hacl_indirect_grant_reg'           => 'all registered users right',
    'hacl_indirect_through'             => '(through $1)',

    'hacl_edit_sd_exists'               => 'This definition already exists.',
    'hacl_edit_enter_name_first'        => 'Error: Enter name to save ACL!',
    'hacl_edit_define_rights'           => 'Error: ACL must include at least 1 right!',
    'hacl_edit_define_manager'          => 'Error: ACL must have at least 1 manager!',
    'hacl_edit_define_tmanager'         => 'Error: ACL template must have at least 1 template manager!',

    'hacl_start_typing_page'            => 'Start typing to display page list...',
    'hacl_start_typing_category'        => 'Start typing to display category list...',
    'hacl_start_typing_user'            => 'Start typing to display user list...',
    'hacl_start_typing_group'           => 'Start typing to display group list...',
    'hacl_edit_users_affected'          => 'Users affected:',
    'hacl_edit_groups_affected'         => 'Groups affected:',
    'hacl_edit_no_users_affected'       => 'No users affected.',
    'hacl_edit_no_groups_affected'      => 'No groups affected.',

    'hacl_edit_user'                    => 'User',
    'hacl_edit_group'                   => 'Group',
    'hacl_edit_all'                     => 'All users',
    'hacl_edit_reg'                     => 'Registered users',

    'hacl_edit_action_all'              => 'All',
    'hacl_edit_action_manage'           => 'Manage pages',
    'hacl_edit_action_template'         => 'Manage template',
    'hacl_edit_action_read'             => 'Read',
    'hacl_edit_action_edit'             => 'Edit',
    'hacl_edit_action_create'           => 'Create',
    'hacl_edit_action_delete'           => 'Delete',
    'hacl_edit_action_move'             => 'Move',

    'hacl_edit_ahint_all'               => 'All page access rights: read, edit, create, delete, move.',
    'hacl_edit_ahint_manage'            => 'This is the inheritable manage right. It allows changing other SDs, except right templates, which include this one, or SDs for pages belonging to given category/namespace. But, it DOES NOT allow changing this definition, if it is not a page right.',
    'hacl_edit_ahint_template'          => 'This is the non-inheritable manage right. It has no effect when included into some other SD, but always allows changing THIS definition.',
    'hacl_edit_ahint_read'              => 'This is the right to read pages.',
    'hacl_edit_ahint_edit'              => 'This is the right to edit pages.',
    'hacl_edit_ahint_create'            => 'This is the right to create new articles within given namespace.', // FIXME ( ... and category )
    'hacl_edit_ahint_delete'            => 'This is the right to delete existing pages.',
    'hacl_edit_ahint_move'              => 'This is the right to move (rename) existing pages.',

    'hacl_define_page'                  => 'Protect page:',
    'hacl_define_namespace'             => 'Protect namespace:',
    'hacl_define_category'              => 'Protect category:',
    'hacl_define_property'              => 'Protect property:',
    'hacl_define_right'                 => 'Define right:',
    'hacl_define_template'              => 'Define user template:',
    'hacl_define_default'               => 'Define global template:',

    // ACL list
    'hacl_acllist'                      => 'Halo Access Control Lists',
    'hacl_acllist_hello'                => 'Hi, this is \'\'\'HaloACL[http://4intra.net/ <sup>4</sup>IN]\'\'\', the best MediaWiki rights extension. You can get help [http://wiki.4intra.net/HaloACL here]. Select function below to start working:',
    'hacl_acllist_empty'                => '<span style="color:red;font-weight:bold">No matching ACL definitions found.</span>',
    'hacl_acllist_filter_name'          => 'Filter by name:',
    'hacl_acllist_filter_type'          => 'Filter by type:',
    'hacl_acllist_typegroup_all'        => 'All definitions',
    'hacl_acllist_typegroup_protect'    => 'Rights for:',
    'hacl_acllist_typegroup_define'     => 'Templates:',

    'hacl_acllist_type_page'            => 'Page',
    'hacl_acllist_type_namespace'       => 'Namespace',
    'hacl_acllist_type_category'        => 'Category',
    'hacl_acllist_type_property'        => 'Property',
    'hacl_acllist_type_right'           => 'Predefined rights',
    'hacl_acllist_type_template'        => 'User templates',

    'hacl_acllist_page'                 => 'Rights for pages:',
    'hacl_acllist_namespace'            => 'Rights for namespaces:',
    'hacl_acllist_category'             => 'Rights for categories:',
    'hacl_acllist_property'             => 'Rights for properties:',
    'hacl_acllist_right'                => 'Predefined rights:',
    'hacl_acllist_template'             => 'Templates for users:',
    'hacl_acllist_default'              => 'Default template for new ACLs:',
    'hacl_acllist_own_template'         => '<b>$1 (your default template)</b>',
    'hacl_acllist_edit'                 => 'Edit',
    'hacl_acllist_view'                 => 'View',

    // Quick ACL list editor
    'hacl_qacl_filter_sds'              => 'Filter templates by name',
    'hacl_qacl_filter'                  => 'Name starts with:',
    'hacl_qacl_filter_submit'           => 'Apply',
    'hacl_qacl_manage'                  => 'HaloACL: Manage Quick Access list',
    'hacl_qacl_manage_text'             => 'This is a list of all the ACL templates that you can use in your quick access list.<br />This list defines the ACLs that will be in the dropdown box that is on the top of every page in the edit or creation mode.',
    'hacl_qacl_save'                    => 'Save selections',
    'hacl_qacl_hint'                    => 'Select some ACL templates and then click Save selections:',
    'hacl_qacl_own_template'            => '(your default template is always present in Quick ACL list)',
    'hacl_qacl_empty'                   => 'There are no ACL templates available for Quick Access. Create one using <b>Create new ACL definition</b>.',
    'hacl_qacl_clear_default'           => 'Clear default',

    // Group list
    'hacl_grouplist'                    => 'HaloACL Groups',
    'hacl_grouplist_filter_name'        => 'Filter by name:',
    'hacl_grouplist_empty'              => '<span style="color:red;font-weight:bold">No matching HaloACL groups found.</span>',
    'hacl_grouplist_view'               => 'View',
    'hacl_grouplist_edit'               => 'Edit',

    // Group editor
    'hacl_grp_creating'                 => 'Create HaloACL group',
    'hacl_grp_editing'                  => 'Editing HaloACL group: $1',
    'hacl_grp_create'                   => 'Create group',
    'hacl_grp_save'                     => 'Save group',
    'hacl_grp_delete'                   => 'Delete group',
    'hacl_grp_name'                     => 'Group name:',
    'hacl_grp_definition_text'          => 'Group definition text:',
    'hacl_grp_member_all'               => 'All users',
    'hacl_grp_member_reg'               => 'All registered users',
    'hacl_grp_members'                  => 'Group members:',
    'hacl_grp_managers'                 => 'Group managers:',
    'hacl_grp_users'                    => 'Users:',
    'hacl_grp_groups'                   => 'Groups:',

    'hacl_grp_exists'                   => 'This group already exists.',
    'hacl_grp_enter_name_first'         => 'Error: Enter name to save group!',
    'hacl_grp_define_members'           => 'Error: Group must have at least 1 member!',
    'hacl_grp_define_managers'          => 'Error: Group must have at least 1 manager!',

    'hacl_no_member_user'               => 'No member users by now.',
    'hacl_no_member_group'              => 'No member groups by now.',
    'hacl_no_manager_user'              => 'No manager users by now.',
    'hacl_no_manager_group'             => 'No manager groups by now.',
    'hacl_current_member_user'          => 'Member users:',
    'hacl_current_member_group'         => 'Member groups:',
    'hacl_current_manager_user'         => 'Manager users:',
    'hacl_current_manager_group'        => 'Manager groups:',
    'hacl_regexp_user'                  => '(^|,\s*)Участник:',
    'hacl_regexp_group'                 => '(^|,\s*)Group:',

    // Toolbar and parts
    'hacl_toolbar_page_prot'            => 'Page protection:',
    'hacl_toolbar_advanced'             => 'Advanced rights definition',
    'hacl_toolbar_goto'                 => 'Go to $1.',
    'hacl_toolbar_global_acl'           => 'Additional ACL &darr;',
    'hacl_toolbar_global_acl_tip'       => 'These definitions also have effect on this page:',
    'hacl_toolbar_cannot_modify'        => 'You can not modify page protection.',
    'hacl_toolbar_no_right_templates'   => 'No custom page rights and no <a href="$1">Quick ACL</a> selected.',
    'hacl_toolbar_unprotected'          => 'No custom rights',
    'hacl_toolbar_used_on'              => 'used on <a href="$2">$1 pages</a>',
    'hacl_toolbar_protect_embedded'     => 'Protect linked images and templates with same SD (will overwrite any defined SD):',
    'hacl_toolbar_emb_custom_prot'      => '<a href="$1">custom SD</a> defined',
    'hacl_toolbar_qacl'                 => 'Manage Quick ACL',
    'hacl_toolbar_qacl_title'           => 'Manage the list of templates always available in the select box.',
    'hacl_comment_protect_with'         => 'Page protected with $1.',
    'hacl_comment_unprotect'            => 'Custom page rights removed.',
);

/**
 * German
 */
$messages['de'] = array(
    'haloacl'                             => 'HaloACL',
    'hacl_special_page'                   => 'HaloACL',
    'hacl_unknown_user'                   => 'Der Benutzer "$1" ist unbekannt.',
    'hacl_unknown_group'                  => 'Die Gruppe "$1" ist unbekannt.',
    'hacl_missing_parameter'              => 'Der Parameter "$1" fehlt.',
    'hacl_missing_parameter_values'       => 'Der Parameter "$1" hat keine gültigen Werte.',
    'hacl_invalid_predefined_right'       => 'Es existiert keine Rechtevorlage mit dem Namen "$1" oder sie enthält keine gültige Rechtedefinition.',
    'hacl_invalid_action'                 => '"$1" ist ein ungültiger Wert für eine Aktion.',
    'hacl_wrong_namespace'                => 'Artikel mit Rechte- oder Gruppendefinitionen müssen zum Namensraum "Rechte" gehören.',
    'hacl_group_must_have_members'        => 'Eine Gruppe muss mindestens ein Mitglied haben (Gruppe oder Benutzer).',
    'hacl_group_must_have_managers'       => 'Eine Gruppe muss mindestens einen Verwalter haben (Gruppe oder Benutzer).',
    'hacl_invalid_parser_function'        => 'Sie dürfen die Funktion "#$1" in diesem Artikel nicht verwenden.',
    'hacl_right_must_have_rights'         => 'Ein Recht oder eine Sicherheitsbeschreibung müssen Rechte oder Verweise auf Rechte enthalten.',
    'hacl_right_must_have_managers'       => 'Ein Recht oder eine Sicherheitsbeschreibung müssen mindestens einen Verwalter haben (Gruppe oder Benutzer).',
    'hacl_pf_rightname_title'             => '===$1==='."\n",
    'hacl_pf_rights_title'                => '===Recht(e): $1==='."\n",
    'hacl_pf_rights'                      => ':;Recht(e):
:: $1
',
    'hacl_pf_right_managers_title'        => '===Rechteverwalter==='."\n",
    'hacl_pf_predefined_rights_title'     => '===Rechtevorlagen==='."\n",
    'hacl_pf_group_managers_title'        => '===Gruppenverwalter==='."\n",
    'hacl_pf_group_members_title'         => '===Gruppenmitglieder==='."\n",
    'hacl_assigned_user'                  => 'Zugewiesene Benutzer: ',
    'hacl_assigned_groups'                => 'Zugewiesene Gruppen:',
    'hacl_user_member'                    => 'Benutzer, die Mitglied dieser Gruppe sind:',
    'hacl_group_member'                   => 'Gruppen, die Mitglied dieser Gruppe sind:',
    'hacl_description'                    => 'Beschreibung:',
    'hacl_error'                          => 'Fehler:',
    'hacl_warning'                        => 'Warnungen:',
    'hacl_consistency_errors'             => '<h2>Fehler in der Rechtedefinition</h2>',
    'hacl_definitions_will_not_be_saved'  => '(Wegen der folgenden Fehler werden die Definitionen dieses Artikel nicht gespeichert und haben keine Auswirkungen.)',
    'hacl_will_not_work_as_expected'      => '(Wegen der folgenden Warnungen wird die Definition nicht wie erwartet angewendet.)',
    'hacl_errors_in_definition'           => 'Die Definitionen in diesem Artikel sind fehlerhaft. Bitte schauen Sie sich die folgenden Details an!',
    'hacl_anonymous_users'                => 'anonyme Benutzer',
    'hacl_registered_users'               => 'registrierte Benutzer',
    'hacl_acl_element_not_in_db'          => 'Zu diesem Artikel gibt es keinen Eintrag in der Rechtedatenbank. Vermutlich wurde er gelöscht und wiederhergestellt. Bitte speichern Sie ihn und alle Artikel die ihn verwenden neu.',
    'hacl_unprotectable_namespace'        => 'Dieser Namensraum kann nicht geschützt werden. Bitte fragen Sie Ihren Wikiadministrator.',
    'hacl_permission_denied'              => 'Sie dürfen die gewünschte Aktion auf dieser Seite nicht durchführen.

Zurück zur [[Hauptseite]].',
    'hacl_sp_query_modified'              => '- Ihre Anfrage wurde modifiziert, das sie geschützte Attribute enthält.'."\n",
    'hacl_sp_empty_query'                 => '- Ihre Anfrage besteht nur aus geschützten Attributen und konnte deshalb nicht ausgeführt werden.of protected properties.'."\n",
    'hacl_sp_results_removed'             => '- Wegen Zugriffbeschränkungen wurden einige Resultate entfernt.'."\n",
    'hacl_sp_cant_save_article'           => '\'\'\'Der Artikel enthält die folgenden geschützten Attribute:\'\'\'
$1\'\'\'Sie haben nicht die Berechtigung, deren Werte zu setzen. Bitte entfernen Sie die Attribute und speichern Sie erneut.\'\'\'',
);
