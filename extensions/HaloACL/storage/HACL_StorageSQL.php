<?php
/*  Copyright 2009, ontoprise GmbH
 *   This file is part of the HaloACL-Extension.
 *
 *   The HaloACL-Extension is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   The HaloACL-Extension is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This file provides the access to the SQL database tables that are
 * used by HaloACL.
 *
 * @author Thomas Schweitzer
 *
 */

global $haclgIP;
require_once $haclgIP . '/storage/HACL_DBHelper.php';

/**
 * This class encapsulates all methods that care about the database tables of
 * the HaloACL extension. This is the implementation for the SQL database.
 *
 */
class HACLStorageSQL {

    /**
     * Initializes the database tables of the HaloACL extensions.
     * These are:
     * - halo_acl_pe_rights:
     *         table of materialized inline rights for each protected element
     * - halo_acl_rights:
     *         description of each inline right
     * - halo_acl_rights_hierarchy:
     *         hierarchy of predefined rights
     * - halo_acl_security_descriptors:
     *         table for security descriptors and predefined rights
     * - halo_acl_groups:
     *         stores the ACL groups
     * - halo_acl_group_members:
     *         stores the hierarchy of groups and their users
     *
     */
    public function initDatabaseTables() {

        $dbw =& wfGetDB( DB_MASTER );

        $verbose = true;
        HACLDBHelper::reportProgress("Setting up HaloACL ...\n",$verbose);

        // halo_acl_rights:
        //        description of each inline right
        $table = $dbw->tableName('halo_acl_rights');

        HACLDBHelper::setupTable($table, array(
            'right_id'         => 'INT(8) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'actions'          => 'INT(8) NOT NULL',
            'groups'           => 'Text CHARACTER SET utf8 COLLATE utf8_bin',
            'users'            => 'Text CHARACTER SET utf8 COLLATE utf8_bin',
            'description'      => 'Text CHARACTER SET utf8 COLLATE utf8_bin',
            'name'             => 'Text CHARACTER SET utf8 COLLATE utf8_bin',
            'origin_id'        => 'INT(8) UNSIGNED NOT NULL'),
        $dbw, $verbose);
        HACLDBHelper::reportProgress("   ... done!\n",$verbose);

        // halo_acl_pe_rights:
        //         table of materialized inline rights for each protected element
        $table = $dbw->tableName('halo_acl_pe_rights');

        HACLDBHelper::setupTable($table, array(
            'pe_id'        => 'INT(8) NOT NULL',
            'type'         => 'ENUM(\'category\', \'page\', \'namespace\', \'property\', \'whitelist\') DEFAULT \'page\' NOT NULL',
            'right_id'     => 'INT(8) UNSIGNED NOT NULL'),
        $dbw, $verbose, "pe_id,type,right_id");
        HACLDBHelper::reportProgress("   ... done!\n",$verbose);

        // halo_acl_rights_hierarchy:
        //        hierarchy of predefined rights
        $table = $dbw->tableName('halo_acl_rights_hierarchy');

        HACLDBHelper::setupTable($table, array(
            'parent_right_id'     => 'INT(8) UNSIGNED NOT NULL',
            'child_id'            => 'INT(8) UNSIGNED NOT NULL'),
        $dbw, $verbose, "parent_right_id,child_id");
        HACLDBHelper::reportProgress("   ... done!\n",$verbose, "parent_right_id, child_id");

        // halo_acl_security_descriptors:
        //        table for security descriptors and predefined rights
        $table = $dbw->tableName('halo_acl_security_descriptors');

        HACLDBHelper::setupTable($table, array(
            'sd_id'     => 'INT(8) UNSIGNED NOT NULL PRIMARY KEY',
            'pe_id'     => 'INT(8)',
            'type'      => 'ENUM(\'category\', \'page\', \'namespace\', \'property\', \'right\') DEFAULT \'page\' NOT NULL',
            'mr_groups' => 'TEXT CHARACTER SET utf8 COLLATE utf8_bin',
            'mr_users'  => 'TEXT CHARACTER SET utf8 COLLATE utf8_bin'),
        $dbw, $verbose);
        HACLDBHelper::reportProgress("   ... done!\n",$verbose);

        // halo_acl_groups:
        //        stores the ACL groups
        $table = $dbw->tableName('halo_acl_groups');

        HACLDBHelper::setupTable($table, array(
            'group_id'   => 'INT(8) UNSIGNED NOT NULL PRIMARY KEY',
            'group_name' => 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL',
            'mg_groups'  => 'TEXT CHARACTER SET utf8 COLLATE utf8_bin',
            'mg_users'   => 'TEXT CHARACTER SET utf8 COLLATE utf8_bin'),
        $dbw, $verbose);
        HACLDBHelper::reportProgress("   ... done!\n",$verbose);

        // halo_acl_group_members:
        //        stores the hierarchy of groups and their users
        $table = $dbw->tableName('halo_acl_group_members');

        HACLDBHelper::setupTable($table, array(
            'parent_group_id'     => 'INT(8) UNSIGNED NOT NULL',
            'child_type'          => 'ENUM(\'group\', \'user\') DEFAULT \'user\' NOT NULL',
            'child_id'            => 'INT(8) NOT NULL'),
        $dbw, $verbose, "parent_group_id,child_type,child_id");
        HACLDBHelper::reportProgress("   ... done!\n",$verbose, "parent_group_id, child_type, child_id");

        // halo_acl_special_pages:
        //        stores the IDs of special pages that have no article ID
        $table = $dbw->tableName('halo_acl_special_pages');

        HACLDBHelper::setupTable($table, array(
            'id'     => 'INT(8) NOT NULL AUTO_INCREMENT',
            'name'   => 'VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL'),
        $dbw, $verbose, "id,name");
        HACLDBHelper::reportProgress("   ... done!\n",$verbose, "id,name");

        // setup quickacl-table
        $table = $dbw->tableName('halo_acl_quickacl');

        HACLDBHelper::setupTable($table, array(
            'sd_id'     => 'INT(8) NOT NULL',
            'user_id'     => 'INT(10) NOT NULL'),
        $dbw, $verbose, "sd_id,user_id");
        HACLDBHelper::reportProgress("   ... done!\n",$verbose, "sd_id,user_id");

        return true;
    }

    public function dropDatabaseTables() {
        global $wgDBtype;
        $verbose = true;

        HACLDBHelper::reportProgress("Deleting all database content and tables generated by HaloACL ...\n\n",$verbose);
        $dbw =& wfGetDB( DB_MASTER );
        $tables = array(
            'halo_acl_rights',
            'halo_acl_pe_rights',
            'halo_acl_rights_hierarchy',
            'halo_acl_security_descriptors',
            'halo_acl_groups',
            'halo_acl_group_members',
            'halo_acl_special_pages',
            'halo_acl_quickacl');
        foreach ($tables as $table) {
            $name = $dbw->tableName($table);
            $dbw->query('DROP TABLE ' . ($wgDBtype == 'postgres' ? '' : ' IF EXISTS') . $name, __METHOD__);
            HACLDBHelper::reportProgress(" ... dropped table $name.\n", $verbose);
        }
        HACLDBHelper::reportProgress("All data removed successfully.\n",$verbose);
    }

    /***************************************************************************
     *
     * Functions for groups
     *
     **************************************************************************/

    /**
     * Returns the name of the group with the ID $groupID.
     *
     * @param int $groupID
     *         ID of the group whose name is requested
     *
     * @return string
     *         Name of the group with the given ID or <NULL> if there is no such
     *         group defined in the database.
     */
    public function groupNameForID($groupID) {
        $dbr =& wfGetDB( DB_SLAVE );
        $groupName = $dbr->selectField('halo_acl_groups', 'group_name', array('group_id' => $groupID), __METHOD__);
        return $groupName;
    }

    /**
     * Saves the given group in the database.
     *
     * @param HACLGroup $group
     *         This object defines the group that wil be saved.
     *
     * @throws
     *         Exception
     *
     */
    public function saveGroup(HACLGroup $group) {
        $dbw =& wfGetDB( DB_MASTER );
        $mgGroups = implode(',', $group->getManageGroups());
        $mgUsers  = implode(',', $group->getManageUsers());
        $dbw->replace('halo_acl_groups', NULL, array(
            'group_id'    =>  $group->getGroupID() ,
            'group_name'  =>  $group->getGroupName() ,
            'mg_groups'   =>  $mgGroups,
            'mg_users'    =>  $mgUsers), __METHOD__);
    }

    /**
     * Retrieves all groups from
     * the database.
     *
     *
     * @return Array
     *         Array of Group Objects
     *
     */
    public function getGroups() {
        $dbr =& wfGetDB( DB_SLAVE );
        $gt = $dbr->tableName('halo_acl_groups');
        $gmt = $dbr->tableName('halo_acl_group_members');

        $sql = "SELECT * FROM $gt LEFT JOIN $gmt on $gt.group_id = $gmt.child_id
                WHERE $gmt.parent_group_id is NULL OR $gmt.parent_group_id = $gmt.child_id";

        $groups = array();

        $res = $dbr->query($sql, __METHOD__);

        while ($row = $dbr->fetchObject($res)) {

            $groupID = $row->group_id;
            $groupName = $row->group_name;
            $mgGroups = self::strToIntArray($row->mg_groups);
            $mgUsers  = self::strToIntArray($row->mg_users);

            $groups[] = new HACLGroup($groupID, $groupName, $mgGroups, $mgUsers);
        }

        $dbr->freeResult($res);

        return $groups;
    }


    /**
     * Retrieves all users and the groups they are attached to
     *
     *
     * @return Array
     *         Array of Group Objects
     *
     */
    public function getUsersWithGroups() {
        $dbr =& wfGetDB( DB_SLAVE );
        $ut = $dbr->tableName('user');
        $gt = $dbr->tableName('halo_acl_groups');
        $gmt = $dbr->tableName('halo_acl_group_members');
        $sql = "SELECT user_id, group_id, group_name
                FROM user
                LEFT JOIN $gmt ON $gmt.child_id = user.user_id
                LEFT JOIN $gt ON $gt.group_id = $gmt.parent_group_id";

        $users = array();

        $res = $dbr->query($sql, __METHOD__);

        $curUser = NULL;

        while ($row = $dbr->fetchObject($res)) {

            if ($curUser != $row->user_id) {

                $curGroupArray = array();
                $curUser = $row->user_id;
            }
            $curGroupArray[] = array("id"=>$row->group_id, "name"=>$row->group_name);
            $users[$row->user_id] = $curGroupArray;
        }

        $dbr->freeResult($res);

        return $users;
    }


    /**
     * Retrieves the description of the group with the name $groupName from
     * the database.
     *
     * @param string $groupName
     *         Name of the requested group.
     *
     * @return HACLGroup
     *         A new group object or <NULL> if there is no such group in the
     *         database.
     *
     */
    public function getGroupByName($groupName) {
        $dbr =& wfGetDB( DB_SLAVE );
        $gt = $dbr->tableName('halo_acl_groups');
        $group = NULL;

        $res = $dbr->select('halo_acl_groups', '*', array('group_name' => $groupName), __METHOD__);

        if ($dbr->numRows($res) == 1) {
            $row = $dbr->fetchObject($res);
            $groupID = $row->group_id;
            $mgGroups = self::strToIntArray($row->mg_groups);
            $mgUsers  = self::strToIntArray($row->mg_users);

            $group = new HACLGroup($groupID, $groupName, $mgGroups, $mgUsers);
        }
        $dbr->freeResult($res);

        return $group;
    }

    /**
     * Retrieves the description of the group with the ID $groupID from
     * the database.
     *
     * @param int $groupID
     *         ID of the requested group.
     *
     * @return HACLGroup
     *         A new group object or <NULL> if there is no such group in the
     *         database.
     *
     */
    public function getGroupByID($groupID) {
        $dbr =& wfGetDB( DB_SLAVE );
        $group = NULL;

        $res = $dbr->select('halo_acl_groups', '*', array('group_id' => $groupID), __METHOD__);

        if ($dbr->numRows($res) == 1) {
            $row = $dbr->fetchObject($res);
            $groupID = $row->group_id;
            $groupName = $row->group_name;
            $mgGroups = self::strToIntArray($row->mg_groups);
            $mgUsers  = self::strToIntArray($row->mg_users);

            $group = new HACLGroup($groupID, $groupName, $mgGroups, $mgUsers);
        }
        $dbr->freeResult($res);

        return $group;
    }

    /**
     * Adds the user with the ID $userID to the group with the ID $groupID.
     *
     * @param int $groupID
     *         The ID of the group to which the user is added.
     * @param int $userID
     *         The ID of the user who is added to the group.
     *
     */
    public function addUserToGroup($groupID, $userID) {
        $dbw =& wfGetDB( DB_MASTER );

        $dbw->replace('halo_acl_group_members', NULL, array(
            'parent_group_id' => $groupID,
            'child_type'      => 'user',
            'child_id '       => $userID), __METHOD__);
    }

    /**
     * Adds the group with the ID $childGroupID to the group with the ID
     * $parentGroupID.
     *
     * @param $parentGroupID
     *         The group with this ID gets the new child with the ID $childGroupID.
     * @param $childGroupID
     *         The group with this ID is added as child to the group with the ID
     *      $parentGroup.
     *
     */
    public function addGroupToGroup($parentGroupID, $childGroupID) {
        $dbw =& wfGetDB( DB_MASTER );

        $dbw->replace('halo_acl_group_members', NULL, array(
            'parent_group_id' => $parentGroupID,
            'child_type'      => 'group',
            'child_id '       => $childGroupID), __METHOD__);
    }

    /**
     * Removes the user with the ID $userID from the group with the ID $groupID.
     *
     * @param $groupID
     *         The ID of the group from which the user is removed.
     * @param int $userID
     *         The ID of the user who is removed from the group.
     *
     */
    public function removeUserFromGroup($groupID, $userID) {
        $dbw =& wfGetDB( DB_MASTER );

        $dbw->delete('halo_acl_group_members', array(
            'parent_group_id' => $groupID,
            'child_type'      => 'user',
            'child_id '       => $userID), __METHOD__);
    }

    /**
     * Removes all members from the group with the ID $groupID.
     *
     * @param $groupID
     *         The ID of the group from which the user is removed.
     *
     */
    public function removeAllMembersFromGroup($groupID) {
        $dbw =& wfGetDB( DB_MASTER );
        $dbw->delete('halo_acl_group_members', array('parent_group_id' => $groupID), __METHOD__);
    }


    /**
     * Removes the group with the ID $childGroupID from the group with the ID
     * $parentGroupID.
     *
     * @param $parentGroupID
     *         This group loses its child $childGroupID.
     * @param $childGroupID
     *         This group is removed from $parentGroupID.
     *
     */
    public function removeGroupFromGroup($parentGroupID, $childGroupID) {
        $dbw =& wfGetDB( DB_MASTER );
        $dbw->delete('halo_acl_group_members', array(
            'parent_group_id' => $parentGroupID,
            'child_type'      => 'group',
            'child_id '       => $childGroupID), __METHOD__);
    }

    /**
     * Returns the IDs of all users or groups that are a member of the group
     * with the ID $groupID.
     *
     * @param string $memberType
     *         'user' => ask for all user IDs
     *      'group' => ask for all group IDs
     * @return array(int)
     *         List of IDs of all direct users or groups in this group.
     *
     */
    public function getMembersOfGroup($groupID, $memberType) {
        $dbr =& wfGetDB( DB_SLAVE );
        $res = $dbr->select('halo_acl_group_members', 'child_id', array(
            'parent_group_id' => $groupID,
            'child_type'      => $memberType), __METHOD__);

        $members = array();
        while ($row = $dbr->fetchObject($res)) {
            $members[] = (int) $row->child_id;
        }

        $dbr->freeResult($res);

        return $members;

    }

    /**
     * Returns all groups the user is member of
     *
     * @param string $memberType
     *         'user' => ask for all user IDs
     *      'group' => ask for all group IDs
     * @return array(int)
     *         List of IDs of all direct users or groups in this group.
     *
     */
    public function getGroupsOfMember($userID) {

        $dbr =& wfGetDB( DB_SLAVE );
        $ut = $dbr->tableName('user');
        $gt = $dbr->tableName('halo_acl_groups');
        $gmt = $dbr->tableName('halo_acl_group_members');
        $sql = "SELECT DISTINCT user_id, group_id, group_name
                FROM user
                LEFT JOIN $gmt ON  $gmt.child_id = user.user_id
                LEFT JOIN $gt ON $gt.group_id = $gmt.parent_group_id
                WHERE user.user_id = $userID";

        $res = $dbr->query($sql, __METHOD__);

        $curGroupArray = array();
        while ($row = $dbr->fetchObject($res)) {
            $curGroupArray[] = array(
                'id' => $row->group_id,
                'name' => $row->group_name
            );
        }

        $dbr->freeResult($res);

        return $curGroupArray;


    }

    /**
     * Checks if the given user or group with the ID $childID belongs to the
     * group with the ID $parentID.
     *
     * @param int $parentID
     *         ID of the group that is checked for a member.
     *
     * @param int $childID
     *         ID of the group or user that is checked for membership.
     *
     * @param string $memberType
     *         HACLGroup::USER  : Checks for membership of a user
     *         HACLGroup::GROUP : Checks for membership of a group
     *
     * @param bool recursive
     *         <true>, checks recursively among all children of this $parentID if
     *                 $childID is a member
     *         <false>, checks only if $childID is an immediate member of $parentID
     *
     * @return bool
     *         <true>, if $childID is a member of $parentID
     *         <false>, if not
     *
     */
    public function hasGroupMember($parentID, $childID, $memberType, $recursive) {
        $dbr =& wfGetDB( DB_SLAVE );

        // Ask for the immediate parents of $childID
        $res = $dbr->select('halo_acl_group_members', 'parent_group_id', array(
            'child_id'   => $childID,
            'child_type' => $memberType,
        ), __METHOD__);

        $parents = array();
        while ($row = $dbr->fetchObject($res)) {
            if ($parentID == (int) $row->parent_group_id) {
                $dbr->freeResult($res);
                return true;
            }
            $parents[] = (int) $row->parent_group_id;
        }
        $dbr->freeResult($res);

        // $childID is not an immediate child of $parentID
        if (!$recursive || empty($parents)) {
            return false;
        }

        // Check recursively, if one of the parent groups of $childID is $parentID

        $ancestors = array();
        while (true) {
            // Check if one of the parent's parent is $parentID
            $res = $dbr->select('halo_acl_group_members', 'parent_group_id', array(
                'parent_group_id' => $parentID,
                'child_id'        => $parents,
                'child_type'      => 'group',
            ), __METHOD__);
            if ($dbr->numRows($res) == 1) {
                // The request parent was found
                $dbr->freeResult($res);
                return true;
            }

            // Parent was not found => retrieve all parents of the current set of
            // parents.
            $where = array(
                'child_id'   => $parents,
                'child_type' => 'group',
            );
            if ($ancestors)
                $where['parent_group_id'] = $ancestors;

            $res = $dbr->select('halo_acl_group_members', 'parent_group_id', $where, __METHOD__, array('DISTINCT'));
            if ($dbr->numRows($res) == 0) {
                // The request parent was found
                $dbr->freeResult($res);
                return false;
            }

            $ancestors = array_merge($ancestors, $parents);
            $parents = array();
            while ($row = $dbr->fetchObject($res)) {
                if ($parentID == (int) $row->parent_group_id) {
                    $dbr->freeResult($res);
                    return true;
                }
                $parents[] = (int) $row->parent_group_id;
            }
            $dbr->freeResult($res);
        }

    }

    /**
     * Deletes the group with the ID $groupID from the database. All references
     * to the group in the hierarchy of groups are deleted as well.
     *
     * However, the group is not removed from any rights, security descriptors etc.
     * as this would mean that articles will have to be changed.
     *
     *
     * @param int $groupID
     *         ID of the group that is removed from the database.
     *
     */
    public function deleteGroup($groupID) {
        $dbw =& wfGetDB( DB_MASTER );

        // Delete the group from the hierarchy of groups (as parent and as child)
        $dbw->delete('halo_acl_group_members', array('parent_group_id' => $groupID), __METHOD__);
        $dbw->delete('halo_acl_group_members', array('child_type' => 'group', 'child_id' => $groupID), __METHOD__);

        // Delete the group's definition
        $dbw->delete('halo_acl_groups', array('group_id' => $groupID), __METHOD__);
    }

    /**
     * Checks if the group with the ID $groupID exists in the database.
     *
     * @param int $groupID
     *         ID of the group
     *
     * @return bool
     *         <true> if the group exists
     *         <false> otherwise
     */
    public function groupExists($groupID) {
        $dbr =& wfGetDB( DB_SLAVE );

        $obj = $dbr->selectRow('halo_acl_groups', 'group_id', array('group_id' => $groupID), __METHOD__);
        return ($obj !== false);
    }


    /***************************************************************************
     *
     * Functions for security descriptors (SD)
     *
     **************************************************************************/



    /**
     * Retrieves all SDs from
     * the database.
     *
     *
     * @return Array
     *         Array of SD Objects
     *
     */
    public function getSDs($types)
    {
        $dbr =& wfGetDB( DB_SLAVE );

        $where = array();
        foreach ($types as $type)
        {
            switch($type)
            {
                case "all":
                    break;
                case "category":
                case "property":
                case "namespace":
                case "page":
                    $where['type'] = $type;
                    break;
                case "standardacl":
                    $where['type'] = array('namespace', 'property', 'category', 'page');
                    break;
                case "acltemplate":
                case "defusertemplate":
                    $where['pe_id'] = 0;
                    // strip leading "Template/"
                    $u = $dbr->tableName('user');
                    $where[] =
                        "SUBSTRING(page_title FROM 10) " .
                        ($type == 'acltemplate' ? "NOT " : "") . 
                        "IN (SELECT user_name FROM $u)";
                    break;
            }
        }

        $sds = array();
        $res = $dbr->select(
            array('halo_acl_security_descriptors', 'page'), '*', $where, __METHOD__,
            array('ORDER BY' => 'page_title'),
            array('page' => array('LEFT JOIN', array('page_id=sd_id')))
        );
        while ($row = $dbr->fetchObject($res)) {
            $sds[] = HACLSecurityDescriptor::newFromID($row->sd_id);
        }
        $dbr->freeResult($res);

        return $sds;
    }

    /**
     * Saves the given SD in the database.
     *
     * @param HACLSecurityDescriptor $sd
     *         This object defines the SD that wil be saved.
     *
     * @throws
     *         Exception
     *
     */
    public function saveSD(HACLSecurityDescriptor $sd) {
        $dbw =& wfGetDB( DB_MASTER );

        $mgGroups = implode(',', $sd->getManageGroups());
        $mgUsers  = implode(',', $sd->getManageUsers());
        $dbw->replace('halo_acl_security_descriptors', NULL, array(
            'sd_id'       =>  $sd->getSDID() ,
            'pe_id'       =>  $sd->getPEID(),
            'type'        =>  $sd->getPEType(),
            'mr_groups'   =>  $mgGroups,
            'mr_users'    =>  $mgUsers), __METHOD__);

    }

    /**
     * Adds a predefined right to a security descriptor or a predefined right.
     *
     * The table "halo_acl_rights_hierarchy" stores the hierarchy of rights. There
     * is a tuple for each parent-child relationship.
     *
     * @param int $parentRightID
     *         ID of the parent right or security descriptor
     * @param int $childRightID
     *         ID of the right that is added as child
     * @throws
     *         Exception
     *         ... on database failure
     */
    public function addRightToSD($parentRightID, $childRightID) {
        $dbw =& wfGetDB( DB_MASTER );

        $dbw->replace('halo_acl_rights_hierarchy', NULL, array(
            'parent_right_id' => $parentRightID,
            'child_id'        => $childRightID), __METHOD__);
    }

    /**
     * Adds the given inline rights to the protected elements of the given
     * security descriptors.
     *
     * The table "halo_acl_pe_rights" stores for each protected element (e.g. a
     * page) its type of protection and the IDs of all inline rights that are
     * assigned.
     *
     * @param array<int> $inlineRights
     *         This is an array of IDs of inline rights. All these rights are
     *         assigned to all given protected elements.
     * @param array<int> $securityDescriptors
     *         This is an array of IDs of security descriptors that protect elements.
     * @throws
     *         Exception
     *         ... on database failure
     */
    public function setInlineRightsForProtectedElements($inlineRights, $securityDescriptors) {
        $dbw =& wfGetDB( DB_MASTER );

        foreach ($securityDescriptors as $sd) {
            // retrieve the protected element and its type
            $obj = $dbw->selectRow('halo_acl_security_descriptors', 'pe_id, type', array('sd_id' => $sd), __METHOD__);
            if (!$obj) {
                continue;
            }
            foreach ($inlineRights as $ir) {
                $dbw->replace('halo_acl_pe_rights', NULL, array(
                    'pe_id'    => $obj->pe_id,
                    'type'     => $obj->type,
                    'right_id' => $ir), __METHOD__);
            }
        }
    }

    /**
     * Returns the IDs of all direct inline rights of all given security
     * descriptor IDs.
     *
     * @param array<int> $sdIDs
     *         Array of security descriptor IDs.
     *
     * @return array<int>
     *         An array of inline right IDs without duplicates.
     */
    public function getInlineRightsOfSDs($sdIDs) {
        if (empty($sdIDs)) {
            return array();
        }
        $dbr =& wfGetDB( DB_SLAVE );
        $res = $dbr->select(
            'halo_acl_rights', 'right_id',
            array('origin_id' => $sdIDs), __METHOD__,
            array('DISTINCT')
        );

        $irs = array();
        while ($row = $dbr->fetchObject($res)) {
            $irs[] = (int) $row->right_id;
        }
        $dbr->freeResult($res);
        return $irs;
    }

    /**
     * Returns the IDs of all predefined rights of the given security
     * descriptor ID.
     *
     * @param int $sdID
     *         ID of the security descriptor.
     * @param bool $recursively
     *         <true>: The whole hierarchy of rights is returned.
     *         <false>: Only the direct rights of this SD are returned.
     *
     * @return array<int>
     *         An array of predefined right IDs without duplicates.
     */
    public function getPredefinedRightsOfSD($sdID, $recursively) {
        $dbr =& wfGetDB( DB_SLAVE );

        $parentIDs = array($sdID);
        $childIDs = array();
        $exclude = array();
        while (true) {
            if (empty($parentIDs)) {
                break;
            }
            $res = $dbr->select(
                'halo_acl_rights_hierarchy', 'child_id',
                array('parent_right_id' => $parentIDs), __METHOD__,
                array('DISTINCT')
            );

            $exclude = array_merge($exclude, $parentIDs);
            $parentIDs = array();

            while ($row = $dbr->fetchObject($res)) {
                $cid = (int) $row->child_id;
                if (!in_array($cid, $childIDs)) {
                    $childIDs[] = $cid;
                }
                if (!in_array($cid, $exclude)) {
                    // Add a new parent for the next level in the hierarchy
                    $parentIDs[] = $cid;
                }
            }
            $numRows = $dbr->numRows($res);
            $dbr->freeResult($res);
            if ($numRows == 0 || !$recursively) {
                // No further children found
                break;
            }
        }
        return $childIDs;
    }

    /**
     * Finds all (real) security descriptors that are related to the given
     * predefined right. The IDs of all SDs that include this right (via the
     * hierarchy of rights) are returned.
     *
     * @param int $prID
     *         IDs of the protected right
     *
     * @return array<int>
     *         An array of IDs of all SD that include the PR via the hierarchy
     *      of PRs.
     */
    public function getSDsIncludingPR($prID) {
        $dbr =& wfGetDB( DB_SLAVE );

        $parentIDs = array();
        $childIDs = array($prID);
        $exclude = array();
        while (true) {
            $res = $dbr->select(
                'halo_acl_rights_hierarchy', 'parent_right_id',
                array('child_id' => $childIDs), __METHOD__,
                array('DISTINCT')
            );

            $exclude = array_merge($exclude, $childIDs);
            $childIDs = array();

            while ($row = $dbr->fetchObject($res)) {
                $prid = (int) $row->parent_right_id;
                if (!in_array($prid, $parentIDs)) {
                    $parentIDs[] = $prid;
                }
                if (!in_array($prid, $exclude)) {
                    // Add a new child for the next level in the hierarchy
                    $childIDs[] = $prid;
                }
            }
            $dbr->freeResult($res);
            if (empty($childIDs)) {
                // No further children found
                break;
            }
        }

        // $parentIDs now contains all SDs/PRs that include $prID
        // => select only the SDs

        $sdIDs = array();
        if (empty($parentIDs)) {
            return $sdIDs;
        }
        $res = $dbr->select('halo_acl_security_descriptors', 'sd_id',
            array("type != 'right'", 'sd_id' => $parentIDs), __METHOD__);

        while ($row = $dbr->fetchObject($res)) {
            $sdIDs[] = (int) $row->sd_id;
        }
        $dbr->freeResult($res);

        return $sdIDs;

    }

    /**
     * Retrieves the description of the SD with the ID $SDID from
     * the database.
     *
     * @param int $SDID
     *         ID of the requested SD.
     *
     * @return HACLSecurityDescriptor
     *         A new SD object or <NULL> if there is no such SD in the
     *         database.
     *
     */
    public function getSDByID($SDID) {
        $dbr =& wfGetDB( DB_SLAVE );
        $sd = NULL;

        $res = $dbr->select('halo_acl_security_descriptors', '*', array('sd_id' => $SDID), __METHOD__);
        if ($dbr->numRows($res) == 1) {
            $row = $dbr->fetchObject($res);
            $sdID = (int)$row->sd_id;
            $peID = (int)$row->pe_id;
            $type   = $row->type;
            $mgGroups = self::strToIntArray($row->mr_groups);
            $mgUsers  = self::strToIntArray($row->mr_users);

            $name = HACLSecurityDescriptor::nameForID($sdID);
            $sd = new HACLSecurityDescriptor($sdID, $name, $peID, $type, $mgGroups, $mgUsers);
        }
        $dbr->freeResult($res);

        return $sd;
    }

    /**
     * Deletes the SD with the ID $SDID from the database. The right remains as
     * child in the hierarchy of rights, as it is still defined as child in the
     * articles that define its parents.
     *
     * @param int $SDID
     *         ID of the SD that is removed from the database.
     * @param bool $rightsOnly
     *         If <true>, only the rights that $SDID contains are deleted from
     *         the hierarchy of rights, but $SDID is not removed.
     *         If <false>, the complete $SDID is removed (but remains as child
     *         in the hierarchy of rights).
     *
     */
    public function deleteSD($SDID, $rightsOnly = false) {
        $dbw =& wfGetDB( DB_MASTER );

        // Delete all inline rights that are defined by the SD (and the
        // references to them)
        $res = $dbw->select('halo_acl_rights', 'right_id', array('origin_id' => $SDID), __METHOD__);

        while ($row = $dbw->fetchObject($res)) {
            $this->deleteRight($row->right_id);
        }
        $dbw->freeResult($res);

        // Remove all inline rights from the hierarchy below $SDID from their
        // protected elements. This may remove too many rights => the parents
        // of $SDID must materialize their rights again
        $prs = $this->getPredefinedRightsOfSD($SDID, true);
        $irs = $this->getInlineRightsOfSDs($prs);

        if (!empty($irs)) {
            $sds = $this->getSDsIncludingPR($SDID);
            $sds[] = $SDID;
            foreach ($sds as $sd) {
                // retrieve the protected element and its type
                $obj = $dbw->selectRow('halo_acl_security_descriptors', 'pe_id, type',
                    array('sd_id' => $sd), __METHOD__);
                if (!$obj) {
                    continue;
                }

                foreach ($irs as $ir) {
                    $dbw->delete('halo_acl_pe_rights', array(
                        'right_id' => $ir,
                        'pe_id' => $obj->pe_id,
                        'type' => $obj->type), __METHOD__);
                }
            }
        }

        // Get all direct parents of $SDID
        $res = $dbw->select('halo_acl_rights_hierarchy', 'parent_right_id',
            array('child_id' => $SDID), __METHOD__);
        $parents = array();
        while ($row = $dbw->fetchObject($res)) {
            $parents[] = $row->parent_right_id;
        }
        $dbw->freeResult($res);

        // Delete the SD from the hierarchy of rights in halo_acl_rights_hierarchy
        //if (!$rightsOnly) {
        //    $dbw->delete('halo_acl_rights_hierarchy', array('child_id' => $SDID));
        //}
        $dbw->delete('halo_acl_rights_hierarchy', array('parent_right_id' => $SDID), __METHOD__);

        // Rematerialize the rights of the parents of $SDID
        foreach ($parents as $p) {
            $sd = HACLSecurityDescriptor::newFromID($p);
            $sd->materializeRightsHierarchy();
        }

        // Delete the SD from the definition of SDs in halo_acl_security_descriptors
        if (!$rightsOnly) {
            $dbw->delete('halo_acl_security_descriptors', array('sd_id' => $SDID), __METHOD__);
        }
    }

    /***************************************************************************
     *
     * Functions for inline rights
     *
     **************************************************************************/

    /**
     * Saves the given inline right in the database.
     *
     * @param HACLRight $right
     *         This object defines the inline right that wil be saved.
     *
     * @return int
     *         The ID of an inline right is determined by the database (AUTO INCREMENT).
     *         The new ID is returned.
     *
     * @throws
     *         Exception
     *
     */
    public function saveRight(HACLRight $right) {
        $dbw =& wfGetDB( DB_MASTER );

        $groups = implode(',', $right->getGroups());
        $users  = implode(',', $right->getUsers());
        $rightID = $right->getRightID();
        $setValues = array(
            'actions'     => $right->getActions(),
            'groups'      => $groups,
            'users'       => $users,
            'description' => $right->getDescription(),
            'name'        => $right->getName(),
            'origin_id'   => $right->getOriginID());
        if ($rightID == -1) {
            // right does not exist yet in the DB.
            $dbw->insert('halo_acl_rights', $setValues);
            // retrieve the auto-incremented ID of the right
            $rightID = $dbw->insertId();
        } else {
            $setValues['right_id'] = $rightID;
            $dbw->replace('halo_acl_rights', NULL, $setValues);
        }

        return $rightID;
    }

    /**
     * Retrieves the description of the inline right with the ID $rightID from
     * the database.
     *
     * @param int $rightID
     *         ID of the requested inline right.
     *
     * @return HACLRight
     *         A new inline right object or <NULL> if there is no such right in the
     *         database.
     *
     */
    public function getRightByID($rightID) {
        $dbr =& wfGetDB( DB_SLAVE );

        $sd = NULL;
        $res = $dbr->select('halo_acl_rights', '*', array('right_id' => $rightID), __METHOD__);

        if ($dbr->numRows($res) == 1) {
            $row = $dbr->fetchObject($res);
            $rightID = $row->right_id;
            $actions = $row->actions;
            $groups = self::strToIntArray($row->groups);
            $users  = self::strToIntArray($row->users);
            $description = $row->description;
            $name        = $row->name;
            $originID    = $row->origin_id;

            $sd = new HACLRight($actions, $groups, $users, $description, $name, $originID);
            $sd->setRightID($rightID);
        }
        $dbr->freeResult($res);

        return $sd;
    }

    /**
     * Returns the IDs of all inline rights for the protected element with the
     * ID $peID that have the protection type $type and match the action $actionID.
     *
     * @param int $peID
     *         ID of the protected element
     * @param strint $type
     *         Type of the protected element: One of
     *        HACLSecurityDescriptor::PET_PAGE
     *         HACLSecurityDescriptor::PET_CATEGORY
     *         HACLSecurityDescriptor::PET_NAMESPACE
     *         HACLSecurityDescriptor::PET_PROPERTY
     *
     * @param int $actionID
     *         ID of the action. One of
     *         HACLRight::READ
     *         HACLRight::FORMEDIT
     *         HACLRight::WYSIWYG
     *         HACLRight::EDIT
     *         HACLRight::ANNOTATE
     *         HACLRight::CREATE
     *         HACLRight::MOVE
     *         HACLRight::DELETE;
     *
     * @return array<int>
     *         An array of IDs of rights that match the given constraints.
     */
    public function getRights($peID, $type, $actionID) {
        $dbr =& wfGetDB( DB_SLAVE );
        $rt = $dbr->tableName('halo_acl_rights');
        $rpet = $dbr->tableName('halo_acl_pe_rights');

        $sql = "SELECT rights.right_id FROM $rt AS rights, $rpet AS pe ".
            "WHERE pe.pe_id = $peID AND pe.type = '$type' AND ".
            "rights.right_id = pe.right_id AND".
            "(rights.actions & $actionID) != 0;";
        $sd = NULL;

        $res = $dbr->query($sql, __METHOD__);

        $rightIDs = array();
        while ($row = $dbr->fetchObject($res)) {
            $rightIDs[] = $row->right_id;
        }
        $dbr->freeResult($res);

        return $rightIDs;

    }

    /**
     * Deletes the inline right with the ID $rightID from the database. All
     * references to the right (from protected elements) are deleted as well.
     *
     * @param int $rightID
     *         ID of the right that is removed from the database.
     *
     */
    public function deleteRight($rightID) {
        $dbw =& wfGetDB( DB_MASTER );

        // Delete the right from the definition of rights in halo_acl_rights
        $dbw->delete('halo_acl_rights', array('right_id' => $rightID), __METHOD__);

        // Delete all references to the right from protected elements
        $dbw->delete('halo_acl_pe_rights', array('right_id' => $rightID), __METHOD__);
    }

    /**
     * Checks if the SD with the ID $sdID exists in the database.
     *
     * @param int $sdID
     *         ID of the SD
     *
     * @return bool
     *         <true> if the SD exists
     *         <false> otherwise
     */
    public function sdExists($sdID) {
        $dbr =& wfGetDB( DB_SLAVE );
        $obj = $dbr->selectRow('halo_acl_security_descriptors', 'sd_id',
            array('sd_id' => $sdID), __METHOD__);
        return ($obj !== false);
    }

    /**
     * Tries to find the ID of the security descriptor for the protected element
     * with the ID $peID.
     *
     * @param int $peID
     *         ID of the protected element
     * @param int $peType
     *         Type of the protected element
     *
     * @return mixed int|bool
     *         int: ID of the security descriptor
     *         <false>, if there is no SD for the protected element
     */
    public static function getSDForPE($peID, $peType) {
        $dbr =& wfGetDB( DB_SLAVE );

        $obj = $dbr->selectRow('halo_acl_security_descriptors', 'sd_id',
            array('pe_id' => $peID, 'type' => $peType), __METHOD__);
        return ($obj === false) ? false : $obj->sd_id;
    }


    /***************************************************************************
     *
     * Functions for the whitelist
     *
     **************************************************************************/

    /**
     * Stores the whitelist that is given in an array of page IDs in the database.
     * All previous whitelist entries are deleted before the new list is inserted.
     *
     * @param array(int) $pageIDs
     *         An array of page IDs of all articles that are part of the whitelist.
     */
    public function saveWhitelist($pageIDs) {
        $dbw =& wfGetDB( DB_MASTER );

        // delete old whitelist entries
        $dbw->delete('halo_acl_pe_rights', array('type' => 'whitelist'), __METHOD__);

        $setValues = array();
        foreach ($pageIDs as $pid) {
            $setValues[] = array(
                'pe_id'     => $pid,
                'type'      => 'whitelist',
                'right_id'  => 0);
        }
        $dbw->insert('halo_acl_pe_rights', $setValues, __METHOD__);
    }

    /**
     * Returns the IDs of all pages that are in the whitelist.
     *
     * @return array(int)
     *         Article-IDs of all pages in the whitelist
     *
     */
    public function getWhitelist() {
        $dbr =& wfGetDB( DB_SLAVE );

        $res = $dbr->select('halo_acl_pe_rights', 'pe_id', array('type' => 'whitelist'), __METHOD__);
        $pageIDs = array();
        while ($row = $dbr->fetchObject($res)) {
            $pageIDs[] = (int)$row->pe_id;
        }
        $dbr->freeResult($res);

        return $pageIDs;
    }

    /**
     * Checks if the article with the ID <$pageID> is part of the whitelist.
     *
     * @param int $pageID
     *         IDs of the page which is checked for membership in the whitelist
     *
     * @return bool
     *         <true>, if the article is part of the whitelist
     *         <false>, otherwise
     */
    public function isInWhitelist($pageID) {
        $dbr =& wfGetDB( DB_SLAVE );

        $obj = $dbr->selectRow('halo_acl_pe_rights', 'pe_id',
            array('type' => 'whitelist', 'pe_id' => $pageID), __METHOD__);
        return $obj !== false;

    }

    /***************************************************************************
     *
     * Functions for special page IDs
     *
     **************************************************************************/

    /**
     * Special pages do not have an article ID, however access control relies
     * on IDs. This method assigns a (negative) ID to each Special Page whose ID
     * is requested. If no ID is stored yet for a given name, a new one is created.
     *
     * @param string $name
     *         Full name of the special page
     *
     * @return int id
     *         The ID of the page. These IDs are negative, so they do not collide
     *         with normal page IDs.
     */
    public static function idForSpecial($name) {
        $dbw =& wfGetDB( DB_MASTER );

        $obj = $dbw->selectRow('halo_acl_special_pages', 'id', array('name' => $name), __METHOD__);
        if ($obj === false) {
            // ID not found => create a new one
            $dbw->insert('halo_acl_special_pages', array('name' => $name), __METHOD__);
            // retrieve the auto-incremented ID of the right
            return -$dbw->insertId();
        } else {
            return -$obj->id;
        }
    }

    /**
     * Special pages do not have an article ID, however access control relies
     * on IDs. This method retrieves the name of a special page for its ID.
     *
     * @param int $id
     *         ID of the special page
     *
     * @return string name
     *         The name of the page if the ID is valid. <0> otherwise
     */
    public static function specialForID($id) {
        $dbw =& wfGetDB( DB_MASTER );
        $obj = $dbw->selectRow('halo_acl_special_pages', 'name', array('id' => -$id), __METHOD__);
        return ($obj === false) ? 0 : $obj->name;
    }

    /**
     * Lists of users and groups are stored as comma separated string of IDs.
     * This function converts the string to an array of integers. Non-numeric
     * elements in the list are skipped.
     *
     * @param string $values
     *         comma separated string of integer values
     * @return array(int)
     *         Array of integers or <NULL> if the string was empty.
     */
    private static function strToIntArray($values) {
        if (!is_string($values) || strlen($values) == 0) {
            return NULL;
        }
        $values = explode(',', $values);
        $intValues = array();
        foreach ($values as $v) {
            if (is_numeric($v)) {
                $intValues[] = (int) trim($v);
            }
        }
        return (count($intValues) > 0 ? $intValues : NULL);
    }

    /**
     * Returns all Articles names and ids
     *
     * @param string $subName
     * @return array(int, string)
     *         List of IDs of all direct users or groups in this group.
     *
     */
    public function getArticles($subName, $noACLs = false, $type = NULL) {
        global $haclgNamespaceIndex;
        $dbr =& wfGetDB( DB_SLAVE );

        $where = array('lower(page_title) LIKE lower('.$dbr->addQuotes("%$subName%").')');
        if ($type == "property")
            $where['page_namespace'] = SMW_NS_PROPERTY;
        elseif ($type == "category")
            $where['page_namespace'] = NS_CATEGORY;
        if ($noACLs)
            $where[] = 'page_namespace != '.$haclgNamespaceIndex;

        $res = $dbr->select('page', 'page_id, page_title', $where, __METHOD__, array('ORDER BY' => 'page_title'));
        $articleArray = array();
        while ($row = $dbr->fetchObject($res)) {
            $articleArray[] = array("id"=>$row->page_id, "name"=>$row->page_title);
        }
        $dbr->freeResult($res);
        return $articleArray;
    }

    /***************************************************************************
     *
     * Functions for quickacls
     *
     **************************************************************************/

    public function saveQuickAcl($user_id, $sd_ids) {
        $dbw =& wfGetDB( DB_MASTER );
        // delete old quickacl entries
        $dbw->delete('halo_acl_quickacl', array('user_id' => $user_id), __METHOD__);

        $setValues = array();
        foreach ($sd_ids as $sd_id) {
            $setValues[] = array(
                'sd_id'     => $sd_id,
                'user_id'  => $user_id);
        }
        $dbw->insert('halo_acl_quickacl', $setValues, __METHOD__);
    }


    public function getQuickacl($user_id) {
        $dbr =& wfGetDB( DB_SLAVE );

        $res = $dbr->select('halo_acl_quickacl', 'sd_id', array('user_id' => $user_id), __METHOD__);

        $sd_ids = array();
        while ($row = $dbr->fetchObject($res)) {
            $sd_ids[] = (int)$row->sd_id;
        }
        $dbr->freeResult($res);

        $quickacl = new HACLQuickacl($user_id,$sd_ids);
        return $quickacl;
    }

    public function deleteQuickaclForSD($sdid){
        $dbw =& wfGetDB( DB_MASTER );
        // delete old quickacl entries
        $dbw->delete('halo_acl_quickacl', array('sd_id' => $sdid), __METHOD__);
        return true;
    }

}