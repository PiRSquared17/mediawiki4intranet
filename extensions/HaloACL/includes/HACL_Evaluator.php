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
 * This is the main class for the evaluation of user rights for a protected object.
 * It implements the function "userCan" that is called from MW for granting or
 * denying access to articles.
 */
if ( !defined( 'MEDIAWIKI' ) ) {
    die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}

/**
 * @author Thomas Schweitzer
 */
class HACLEvaluator
{
    //---- Constants for the modes of the evaluator ----
    const NORMAL = 0;
    const DENY_DIFF = 1;
    const ALLOW_PROPERTY_READ = 2;

    //--- Private fields ---

    // The current mode of the evaluator
    static $mMode = HACLEvaluator::NORMAL;

    // Saving protected properties is allowed if the value did not change
    static $mSavePropertiesAllowed = false;

    // String with logging information
    static $mLog = "";

    // Is logging HaloACL's activities enabled?
    static $mLogEnabled = false;

    /**
     * Constructor for  HACLEvaluator
     *
     * @param type $param
     *         Name of the notification
     */
    function __construct() {
    }

    //--- Public methods ---

    /**
     * This function is called from the userCan-hook of MW. This method decides
     * if the article for the given title can be accessed.
     * See  further information at: http://www.mediawiki.org/wiki/Manual:Hooks/userCan
     *
     * @param Title $title
     *         The title object for the article that will be accessed.
     * @param User $user
     *         Reference to the current user.
     * @param string $action
     *         Action concerning the title in question
     * @param boolean $result
     *         Reference to the result propagated along the chain of hooks.
     *
     * @return boolean
     *         true
     */
    public static function userCan($title, $user, $action, &$result)
    {
        global $wgRequest, $wgTitle;

        self::startLog($title, $user, $action);

        if ($title == NULL) {
            $result = true;
            self::finishLog("Title is <null>.", $result, true);
            return true;
        }
        $etc = haclfDisableTitlePatch();

        // Check if property access is requested.
        global $haclgProtectProperties;
        if ($haclgProtectProperties) {
            self::log("Properties are protected.");
            $r = self::checkPropertyAccess($title, $user, $action);
            if ($r !== -1) {
                haclfRestoreTitlePatch($etc);
                $result = $r;
                self::finishLog("Right for property evaluated.", $result, true);
                return $r;
            }
        }

        $actionID = HACLRight::getActionID($action);
        if ($actionID == 0) {
            // unknown action => nothing can be said about this
            haclfRestoreTitlePatch($etc);
            self::finishLog("Unknown action.", true, true);
            return true;
        }

        // no access to the page "Permission denied" is allowed.
        // together with the TitlePatch which returns this page, this leads
        // to MediaWiki's "Permission error"
        global $haclgContLang;
        if ($title->getText() == $haclgContLang->getPermissionDeniedPage()) {
            $r = false;
            haclfRestoreTitlePatch($etc);
            self::finishLog('Special handling of "Permission denied" page.', $r, $r);
            $result = $r;
            return $r;
        }

        $articleID = (int) $title->getArticleID();
        if ($title->getText() === "")
            $articleID = haclfArticleID($wgTitle->getPrefixedText());
        if (!$articleID)
            $articleID = haclfArticleID($title->getPrefixedText());
        $userID = $user->getId();

        if (!$articleID)
        {
            // The article does not exist yet
            if ($actionID == HACLLanguage::RIGHT_CREATE || $actionID == HACLLanguage::RIGHT_EDIT)
            {
                self::log('Article does not exist yet. Checking right to create.');

                // Check right for creation of default SD template. Users
                // can only create their own template. Sysops and bureaucrats
                // can create them for everyone.
                list ($r, $sd) = HACLDefaultSD::userCanModify($title, $user);
                if ($sd)
                {
                    haclfRestoreTitlePatch($etc);
                    $result = $r;
                    self::finishLog("Checked right for creating the default user template.", $r, $r);
                    return $r;
                }

                // Check if the user is allowed to create an SD
                $allowed = self::checkSDCreation($title, $user);
                if ($allowed == false)
                {
                    haclfRestoreTitlePatch($etc);
                    $result = false;
                    self::finishLog("Checked right for creating a security descriptor.", $result, false);
                    return false;
                }
            }

            // Check if the article belongs to a namespace with an SD
            list($r, $sd) = self::checkNamespaceRight($title, $userID, $actionID);
            haclfRestoreTitlePatch($etc);
            $result = $r;
            self::finishLog("Checked if the user is allowed to create an article with in the given namespace.", $r, $r);
            return $r;
        }

        // Check rights for managing ACLs
        list($r, $sd) = self::checkACLManager($title, $user, $actionID);
        if ($sd)
        {
            // User tries to access an ACL article
            haclfRestoreTitlePatch($etc);
            $result = $r;
            self::finishLog("Checked if user can modify an access control entity (SD, right or group).", $r, $r);
            return $r;
        }

        $submit = $wgRequest->getText('action');
        $submit = $submit == 'submit';
        $savePage = $wgRequest->getCheck('wpSave');
        $edit = $wgRequest->getText('action');
        $edit = $edit == 'edit';
        $sameTitle = $wgRequest->getText('title');
        $sameTitle = str_replace(' ', '_', $sameTitle) == str_replace(' ', '_', $title->getFullText());
        // Check if the article contains protected properties that avert
        // editing the article
        // There is no need to check for protected properties if an edited article
        // is submitted. An article with protected properties may be saved if their
        // values are not changed. This is checked in method "onEditFilter" when
        // the article is about to be saved.
        if (($submit && !$savePage) || ($edit && $sameTitle)) {
            // First condition:
            // The article is submitted but not saved (preview). This causes, that
            // the wikitext will be displayed.
            // Second condition:
            // The requested article is edited. Nevertheless, the passed $action
            // might be "read" as MW tries to show the articles source
            // => prophibit this, if it contains properties without read-access
            $allowed = self::checkProperties($title, $userID, HACLLanguage::RIGHT_EDIT);
        } else {
            $allowed = $savePage || self::checkProperties($title, $userID, $actionID);
        }
        if (!$allowed) {
            haclfRestoreTitlePatch($etc);
            $result = false;
            self::finishLog("The article contains protected properties.", $result, false);
            return false;
        }

        // Check if there is a security descriptor for the article.
        $hasSD = HACLSecurityDescriptor::getSDForPE($articleID, HACLLanguage::PET_PAGE) !== false;

        // first check page rights
        if ($hasSD) {
            self::log("The article is protected with a security descriptor.");

            $r = self::hasRight($articleID, HACLLanguage::PET_PAGE,
                                $userID, $actionID);
            if ($r) {
                haclfRestoreTitlePatch($etc);
                $result = true;
                self::finishLog("Access allowed by page right.", $result, true);
                return true;
            }
        }

        // if the page is a category page, check the category right
        if ($title->getNamespace() == NS_CATEGORY)
        {
            $hasSD = HACLSecurityDescriptor::getSDForPE($articleID, HACLLanguage::PET_CATEGORY) !== false;
            if ($hasSD)
            {
                self::log("The article is a category page and this category is protected with a security descriptor.");

                $r = self::hasRight($articleID, HACLLanguage::PET_CATEGORY,
                                    $userID, $actionID);
                if ($r)
                {
                    haclfRestoreTitlePatch($etc);
                    $result = true;
                    self::finishLog("Access allowed for category page by category right.", $result, true);
                    return true;
                }
            }
        }

        // check namespace rights
        list($r, $sd) = self::checkNamespaceRight($title, $userID, $actionID);
        $hasSD = $hasSD ? true : $sd;
        if ($sd && $r) {
            haclfRestoreTitlePatch($etc);
            $result = true;
            self::finishLog("Action allowed by a namespace right.", $result, true);
            return true;
        }

        // check category rights
        list($r, $sd) = self::hasCategoryRight($title->getFullText(), $userID, $actionID);
        $hasSD = $hasSD ? true : $sd;
        if ($sd && $r) {
            haclfRestoreTitlePatch($etc);
            $result = true;
            self::finishLog("Action allowed by a category right.", $result, true);
            return true;
        }

        // check the whitelist
        if (HACLWhitelist::isInWhitelist($articleID)) {
            $r = $actionID == HACLLanguage::RIGHT_READ;
            // articles in the whitelist can be read
            haclfRestoreTitlePatch($etc);
            $result = $r;
            self::finishLog("Read access was determined by the Whitelist.", $result, true);
            return $r;
        }

        if (!$hasSD) {
            global $haclgOpenWikiAccess;
            // Articles with no SD are not protected if $haclgOpenWikiAccess is
            // true. Otherwise access is denied
            haclfRestoreTitlePatch($etc);
            if ($haclgOpenWikiAccess) {
                // Wiki is open for HaloACL but other extensions can still
                // prohibit access.
                self::finishLog("No security descriptor for article found. HaloACL is configured to Open Wiki Access.", true, true);
                return true;
            }
        }

        // permission denied
        haclfRestoreTitlePatch($etc);
        self::finishLog("No matching right for article found.", false, false);

        $result = false;
        return false;
    }


    /**
     * Checks, if the given user has the right to perform the given action on
     * the given title. The hierarchy of categories is not considered here.
     *
     * @param int $titleID
     *         ID of the protected object (which is the namespace index if the type
     *         is PET_NAMESPACE)
     * @param string $peType
     *         The type of the protection to check for the title. One of
     *         HACLLanguage::PET_PAGE
     *         HACLLanguage::PET_CATEGORY
     *         HACLLanguage::PET_NAMESPACE
     *         HACLLanguage::PET_PROPERTY
     * @param int $userID
     *         ID of the user who wants to perform an action
     * @param int $actionID
     *         The action, the user wants to perform. One of the constant defined
     *         in HACLRight: READ, FORMEDIT, WYSIWYG, EDIT, ANNOTATE, CREATE, MOVE and DELETE.
     * @return bool
     *         <true>, if the user has the right to perform the action
     *         <false>, otherwise
     */
    public static function hasRight($titleID, $type, $userID, $actionID) {
        // retrieve all appropriate rights from the database
        $rightIDs = HACLStorage::getDatabase()->getRights($titleID, $type, $actionID);

        // Check for all rights, if they are granted for the given user
        foreach ($rightIDs as $r) {
            $right = HACLRight::newFromID($r);
            if ($right->grantedForUser($userID)) {
                return true;
            }
        }

        return false;

    }

    /**
     * Checks, if the given user has the right to perform the given action on
     * the given property. (This happens only if protection of semantic properties
     * is enabled (see $haclgProtectProperties in HACL_Initialize.php))
     *
     * @param mixed(Title|int) $propertyTitle
     *         ID or title of the property whose rights are evaluated
     * @param int $userID
     *         ID of the user who wants to perform an action
     * @param int $actionID
     *         The action, the user wants to perform. One of the constant defined
     *         in HACLRight: READ, FORMEDIT, EDIT
     * @return bool
     *        <true>, if the user has the right to perform the action
     *         <false>, otherwise
     */
    public static function hasPropertyRight($propertyTitle, $userID, $actionID) {
        global $haclgProtectProperties;
        if (!$haclgProtectProperties) {
            // Protection of properties is disabled.
            return true;
        }

        if ($propertyTitle instanceof Title) {
            $propertyTitle = $propertyTitle->getArticleID();
        }

        $hasSD = HACLSecurityDescriptor::getSDForPE($propertyTitle, HACLLanguage::PET_PROPERTY) !== false;

        if (!$hasSD) {
            global $haclgOpenWikiAccess;
            // Properties with no SD are not protected if $haclgOpenWikiAccess is
            // true. Otherwise access is denied
            return $haclgOpenWikiAccess;
        }
        return self::hasRight($propertyTitle,
                              HACLLanguage::PET_PROPERTY,
                              $userID, $actionID);

    }

    /**
     * This function is called, before an article is saved.
     * If protection of properties is switched on, it checks if the article contains
     * properties that have been changed and for which the current user has no
     * access rights. In that case, saving the article is aborted and an error
     * message is displayed.
     *
     * @param EditPage $editor
     * @param string $text
     * @param $section
     * @param string $error
     *         If a property is not accessible, this error message is modified and
     *         displayed on the editor page.
     *
     * @return bool
     *         true
     */
     public static function onEditFilter($editor, $text, $section, &$error) {
        global $wgParser, $wgUser;
        $article = $editor->mArticle;
        $options = new ParserOptions;
    //    $options->setTidy( true );
        $options->enableLimitReport();
        self::$mMode = HACLEvaluator::ALLOW_PROPERTY_READ;
        $output = $wgParser->parse($article->preSaveTransform($text),
                                   $article->mTitle, $options);
        self::$mMode = HACLEvaluator::NORMAL;

        $protectedProperties = "";
        if (isset($output->mSMWData)) {
            foreach ($output->mSMWData->getProperties() as $name => $prop) {
                if (!$prop->userCan("propertyformedit")) {
                    // Access to property is restricted
                    if (!isset($oldPV)) {
                        // Get all old properties of the page from the semantic store
                        $oldPV = smwfGetStore()->getSemanticData($editor->mTitle);
                    }
                    if (self::propertyValuesChanged($prop, $oldPV, $output->mSMWData)) {
                        $protectedProperties .= "* $name\n";
                    }
                }
            }
        }
        if (empty($protectedProperties)) {
            self::$mSavePropertiesAllowed = true;
            return true;
        }

        self::$mSavePropertiesAllowed = false;
        $error = wfMsgForContent('hacl_sp_cant_save_article', $protectedProperties);

        // Special handling for semantic forms
        if (defined('SF_VERSION')) {
            include_once('includes/SpecialPage.php');
            $spt = SpecialPage::getTitleFor('EditData');
            $url = $spt->getFullURL();
            $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
            if (strpos($referer, $url) === 0) {
                // A semantic form was saved.
                // => abort with an error message
                global $wgOut;
                $wgOut->addWikiText($error);
                return false;
            }
        }
        return true;
    }

    /**
     * This method is called when the difference of two revisions of an article is
     * about to be displayed.
     * If one of the revisions contains a property that can not be read, the mode
     * for the ACL evaluator is set accordingly for following calls to the userCan
     * hook.
     *
     * @param DifferenceEngine $diffEngine
     * @param Revision $oldRev
     * @param Revision $newRev
     * @return boolean true
     */
    public static function onDiffViewHeader(DifferenceEngine &$diffEngine, $oldRev, $newRev) {

        $newText = $diffEngine->mNewtext;
        if (!isset($newText)) {
            $diffEngine->loadText();
        }
        $newText = $diffEngine->mNewtext;
        $oldText = $diffEngine->mOldtext;

        global $wgParser;
        $options = new ParserOptions;
        $output = $wgParser->parse($newText, $diffEngine->mTitle, $options);

        if (isset($output->mSMWData)) {
            foreach ($output->mSMWData->getProperties() as $name => $prop) {
                if (!$prop->userCan("propertyread")) {
                    HACLEvaluator::$mMode = HACLEvaluator::DENY_DIFF;
                    return true;
                }
            }
        }

        $output = $wgParser->parse($oldText, $diffEngine->mTitle, $options);

        if (isset($output->mSMWData)) {
            foreach ($output->mSMWData->getProperties() as $name => $prop) {
                if (!$prop->userCan("propertyread")) {
                    HACLEvaluator::$mMode = HACLEvaluator::DENY_DIFF;
                    return true;
                }
            }
        }

        return true;
    }

    /**
     * This method is important if the mode of the access control is
     * "closed wiki access" or if an SD for an instance of a protected category
     * of namespace is about to be created.
     * If the wiki access is open, articles without security
     * descriptor have full access. If it is closed, nobody can access the article
     * until a security descriptor is defined. Only the latest author of the article
     * can do this. This method checks, if a security descriptor can be created.
     *
     * If an article is an instance of a protected category or namespace, creating
     * an SD for it is restricted. The modification rights of the category's or
     * namespace's SD are applied.
     *
     * @param Title $title
     *         Title of the article that will be created
     * @param User $user
     *         User who wants to create the article
     * @return bool|string
     *         <true>, if the user can create the security descriptor
     *         <false>, if not
     *         "n/a", if this method is not applicable for the given article creation
     */
    public static function checkSDCreation($title, $user) {
        if ($title->getNamespace() != HACL_NS_ACL) {
            // The title is not in the ACL namespace => not applicable
            return "n/a";
        }

        list($peName, $peType) = HACLSecurityDescriptor::nameOfPE($title->getText());

        // Check if article belongs to a protected category
        // Only the users who can modify the SD of the protecting category can
        // create a new SD for the protected page.

        if ($peType == HACLLanguage::PET_PAGE ||
            $peType == HACLLanguage::PET_CATEGORY) {
            list ($r, $hasSD) = self::hasCategorySDCreationRight($peName, $user->getId());
            if ($r === false && $hasSD === true) {
                return false;
            }
        }
        $t = Title::newFromText($peName);

        // Check if article belongs to a protected namespace
        if ($peType == HACLLanguage::PET_PAGE) {
            list ($r, $hasSD) = self::checkNamespaceSDCreationRight($t, $user->getId());
            if ($r === false && $hasSD === true) {
                return false;
            }
        }

        global $haclgOpenWikiAccess;
        if ($haclgOpenWikiAccess) {
            // the wiki is open => not applicable
            return "n/a";
        }
        if ($peType != HACLLanguage::PET_PAGE &&
            $peType != HACLLanguage::PET_PROPERTY) {
            // only applicable to pages and properties
            return "n/a";
        }

        // get the latest author of the protected article
        $article = new Article($t);
        if (!$article->exists()) {
            // article does not exist => no applicable
            return "n/a";
        }
        $authors = $article->getLastNAuthors(1);

        return $authors[0] == $user->getName();

    }


    //--- Private methods ---

    /**
     * Checks, if the given user has the right to perform the given action on
     * the given title. The hierarchy of categories is evaluated.
     *
     * @param mixed string|array<string> $parents
     *         If a string is given, this is the name of an article whose parent
     *         categories are evaluated. Otherwise it is an array of parent category
     *         names
     * @param int $userID
     *         ID of the user who wants to perform an action
     * @param int $actionID
     *         The action, the user wants to perform. One of the constant defined
     *         in HACLRight: READ, FORMEDIT, EDIT, ANNOTATE, CREATE, MOVE and DELETE.
     * @param array<string> $visitedParents
     *         This array contains the names of all parent categories that were already
     *         visited.
     * @return array(bool rightGranted, bool hasSD)
     *         rightGranted:
     *             <true>, if the user has the right to perform the action
     *             <false>, otherwise
     *         hasSD:
     *             <true>, if there is an SD for the article
     *             <false>, if not
     */
    private static function hasCategoryRight($parents, $userID, $actionID,
                                            $visitedParents = array()) {
        if (is_string($parents)) {
            // The article whose parent categories shall be evaluated is given
            $t = Title::newFromText($parents);
            if (!$t)
                return true;
            return self::hasCategoryRight(array_keys($t->getParentCategories()),$userID, $actionID);
        } else if (is_array($parents)) {
            if (empty($parents)) {
                return array(false, false);
            }
        } else {
            return array(false, false);
        }

        // Check for each parent if the right is granted
        $parentTitles = array();
        $hasSD = false;
        foreach ($parents as $p) {
            $parentTitles[] = $t = Title::newFromText($p);

            if (!$hasSD) {
                $hasSD = (HACLSecurityDescriptor::getSDForPE($t->getArticleID(), HACLLanguage::PET_CATEGORY) !== false);
            }
            $r = self::hasRight($t->getArticleID(), HACLLanguage::PET_CATEGORY,
                                $userID, $actionID);
            if ($r) {
                return array(true, $hasSD);
            }
        }

        // No parent category has the required right
        // => check the next level of parents
        $parents = array();
        foreach ($parentTitles as $pt) {
            $ptParents = array_keys($pt->getParentCategories());
            foreach ($ptParents as $p) {
                if (!in_array($p, $visitedParents)) {
                    $parents[] = $p;
                    $visitedParents[] = $p;
                }
            }
        }

        // Recursively check all parents
        list($r, $sd) = self::hasCategoryRight($parents, $userID, $actionID, $visitedParents);
        return array($r, $sd ? true : $hasSD);

    }

    /**
     * Checks, if the given user has the right to create an SD for the given
     * category (as category right) or page.
     * Assume that a category or page is protected by the SD of its super
     * category. If every user could create a new SD for this page, the protection
     * by categories could not be granted. Consequently the set of users who can
     * create such an SD is restricted:
     * Pages which are protected by a category inherit the SD of the category, thus
     * the modification rights of the category's SD are inherited as well. So only
     * users who can modify the SD of the category can create a new SD for the page.
     *
     * Only the SDs of the first parent categories that are found are evaluated
     * as these inherit the modification rights of their parents. So there is no
     * need to crawl all parents recursively.
     *
     * @param mixed string|array<string> $parents
     *         If a string is given, this is the name of an article whose parent
     *         categories are evaluated. Otherwise it is an array of parent category
     *         names
     * @param int $userID
     *         ID of the user who wants to perform an action
     * @param array<string> $visitedParents
     *         This array contains the names of all parent categories that were already
     *         visited.
     * @return array(bool rightGranted, bool hasSD)
     *         rightGranted:
     *             <true>, if the user has the right to create the SD
     *             <false>, otherwise
     *         hasSD:
     *             <true>, if there is an SD for the article
     *             <false>, if not
     *      */
    private static function hasCategorySDCreationRight($parents, $userID,
                                            $visitedParents = array()) {
        if (is_string($parents)) {
            // The article whose parent categories shall be evaluated is given
            $t = Title::newFromText($parents);
            return self::hasCategorySDCreationRight(array_keys($t->getParentCategories()),$userID);
        } else if (is_array($parents)) {
            if (empty($parents)) {
                // no parents => page/category is not protected
                return array(false, false);
            }
        } else {
            // Invalid parameter $parent
            return array(false, false);
        }

        // Check for each parent if the right is granted
        $parentTitles = array();
        $sdFound = false;
        foreach ($parents as $p) {
            $parentTitles[] = $t = Title::newFromText($p);

            $sd = HACLSecurityDescriptor::getSDForPE($t->getArticleID(),
                                                     HACLLanguage::PET_CATEGORY);
            if ($sd !== false) {
                $sd = HACLSecurityDescriptor::newFromID($sd);
                if ($sd->userCanModify($userID)) {
                    // User has modification rights for the category's SD.
                    return array(true, true);
                }
                $sdFound = true;
            }
        }
        if ($sdFound) {
            // The parent categories owned an SD, but the user is not allowed to
            // modify them.
            return array(false, true);
        }

        // No parent category has an SD
        // => check the next level of parents
        $parents = array();
        foreach ($parentTitles as $pt) {
            $ptParents = array_keys($pt->getParentCategories());
            foreach ($ptParents as $p) {
                if (!in_array($p, $visitedParents)) {
                    $parents[] = $p;
                    $visitedParents[] = $p;
                }
            }
        }

        // Go up one level of parents
        return self::hasCategorySDCreationRight($parents, $userID, $visitedParents);
    }


    /**
     * Checks if access is granted to the namespace of the given title.
     *
     * @param Title $t
     *         Title whose namespace is checked
     * @param int $userID
     *         ID of the user who want to access the namespace
     * @param int $actionID
     *         ID of the action the user wants to perform
     *
     * @return array(bool rightGranted, bool hasSD)
     *         rightGranted:
     *             <true>, if the user has the right to perform the action
     *             <false>, otherwise
     *         hasSD:
     *             <true>, if there is an SD for the article
     *             <false>, if not
     *
     */
    private static function checkNamespaceRight(Title $t, $userID, $actionID) {
        $nsID = $t->getNamespace();
        $hasSD = HACLSecurityDescriptor::getSDForPE($nsID, HACLLanguage::PET_NAMESPACE) !== false;

        if (!$hasSD) {
            global $haclgOpenWikiAccess;
            // Articles with no SD are not protected if $haclgOpenWikiAccess is
            // true. Otherwise access is denied
            return array($haclgOpenWikiAccess, false);
        }

        return array(self::hasRight($nsID, HACLLanguage::PET_NAMESPACE,
                                    $userID, $actionID), $hasSD);

    }

    /**
     * Checks if the user can create an SD for an article in the given namespace.
     * If a namespace is protected by an SD, only the managers of this SD have the
     * right to create new SDs for articles in this namespace. Otherwise every
     * user could overwrite the security settings of the namespace for single
     * articles.
     *
     * @param Title $t
     *         Title whose namespace is checked
     * @param int $userID
     *         ID of the user who want to access the namespace
     *
     * @return array(bool rightGranted, bool hasSD)
     *         rightGranted:
     *             <true>, if the user has the right to create a new SD for the article
     *             <false>, otherwise
     *         hasSD:
     *             <true>, if there is an SD for the article
     *             <false>, if not
     *
     */
    private static function checkNamespaceSDCreationRight(Title $t, $userID) {
        $nsID = $t->getNamespace();
        $sd = HACLSecurityDescriptor::getSDForPE($nsID, HACLLanguage::PET_NAMESPACE);
        if ($sd !== false) {
            $sd = HACLSecurityDescriptor::newFromID($sd);
            return array($sd->userCanModify($userID), true);
        }

        return array(false, false);

    }

    /**
     * This method checks if a user wants to modify an article in the namespace
     * ACL.
     *
     * @param Title $t
     *         The title.
     * @param User $user
     *         User-object of the user.
     * @param int $actionID
     *         ID of the action. The actions FORMEDIT, WYSIWYG, EDIT, ANNOTATE,
     *      CREATE, MOVE and DELETE are relevant for managing an ACL object.
     *
     * @return array(bool rightGranted, bool hasSD)
     *         rightGranted:
     *             <true>, if the user has the right to perform the action
     *             <false>, otherwise
     *         hasSD:
     *             <true>, if there is an SD for the article
     *             <false>, if not
     */
    private static function checkACLManager(Title $t, $user, $actionID)
    {
        // Require ACL namespace
        if ($t->getNamespace() != HACL_NS_ACL)
            return array(true, false);

        $userID = $user->getId();
        // No access for anonymous users to ACL pages
        if (!$userID)
            return array(false, true);

        // Read access for all registered users
        if ($actionID == HACLLanguage::RIGHT_READ)
            return array(true, true);

        // Sysops and bureaucrats can modify anything
        $groups = $user->getGroups();
        if (in_array('sysop', $groups) || in_array('bureaucrat', $groups))
            return array(true, true);

        try
        {
            switch (self::hacl_type($t))
            {
                // Group
                case 'group':
                    $group = HACLGroup::newFromID($t->getArticleID());
                    return array($group->userCanModify($userID), true);
                // SD, right, template
                case 'right':
                case 'sd':
                    $sd = HACLSecurityDescriptor::newFromID($t->getArticleID());
                    return array($sd->userCanModify($userID), true);
                // Whitelist
                case 'whitelist':
                    global $haclgContLang;
                    if ($t->getText() == $haclgContLang->getWhitelist(false))
                        return array(HACLWhitelist::userCanModify($userID), true);
                    break;
            }
        }
        catch (HACLSDException $e)
        {
            if ($e->getCode() == HACLSDException::NO_PE_ID ||
                $e->getCode() == HACLSDException::UNKNOWN_SD)
            {
                // Always allow to modify non-saved SDs, because
                // we can't check access yet
                return array(true, true);
            }
        }

        return array(false, true);
    }

    /* Check is $title corresponds to some HaloACL definition page
       Returns 'group', 'sd', 'whitelist' or FALSE */
    static function hacl_type($title)
    {
        global $haclgContLang;
        $text = is_object($title) ? $title->getText() : $title;
        if ($text == $haclgContLang->getWhitelist())
            return 'whitelist';
        elseif (($p = strpos($text, '/')) === false)
            return false;
        $prefix = substr($text, 0, $p);
        if ($t = $haclgContLang->getPrefix($prefix))
            return $t;
        return false;
    }

    /**
     * This method checks if a user wants to edit an article with protected
     * properties. (This happens only if protection of semantic properties
     * is enabled (see $haclgProtectProperties in HACL_Initialize.php))
     *
     * @param Title $t
     *         The title.
     * @param int $userID
     *         ID of the user.
     * @param int $actionID
     *         ID of the action. The actions FORMEDIT, WYSIWYG, EDIT, ANNOTATE,
     *      CREATE, MOVE and DELETE are relevant for managing an ACL object.
     *
     * @return bool
     *         rightGranted:
     *             <true>, if the user has the right to perform the action
     *             <false>, otherwise
     */
    private static function checkProperties(Title $t, $userID, $actionID) {
        global $haclgProtectProperties;
        global $wgRequest;
        if (!$haclgProtectProperties) {
            // Properties are not protected.
            return true;
        }

        if ($actionID == HACLLanguage::RIGHT_READ) {
            // The article is only read but not edited => action is allowed
            return true;
        }
        // Articles with protected properties are protected if an unauthorized
        // user wants to edit it
        if ($actionID != HACLLanguage::RIGHT_EDIT) {

            $a = @$wgRequest->data['action'];
            if (isset($a)) {
                // Some web request are translated to other actions before they
                // are passed to the userCan hook. E.g. action=history is passed
                // as action=read.
                // Articles with protected properties can be viewed, because the
                // property values are replaced by dummy text but showing the wikitext
                // (e.g. in the history) must be prohibited.

                // Define exceptions for actions that display only rendered text
                static $actionExceptions = array("purge","render","raw");
                if (in_array($a,$actionExceptions)) {
                    return true;
                }

            } else {
                return true;
            }

        }

        if (function_exists('smwfGetStore'))
            return true;
        // Get all properties of the page
        $semdata = smwfGetStore()->getSemanticData($t);
        $props = $semdata->getProperties();
        foreach ($props as $p) {
//            if (!$p->isShown()) {
//                // Ignore invisible(internal) properties
//                continue;
//            }
            // Check if a property is protected
            $wpv = $p->getWikiPageValue();
            if (!$wpv) {
                // no page for property
                continue;
            }
            $t = $wpv->getTitle();

            if (!self::hasPropertyRight($t, $userID, $actionID)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks if access to a property should be evaluated. This is the case if
     * the string $action is one of 'propertyread', 'propertyformedit' or
     * 'propertyedit'.
     *
     * @param Title $title
     *         Title object for the property whose rights are checked.
     * @param User $user
     *         User who wants to access the property
     * @param string $action
     *         If this is one of 'propertyread', 'propertyformedit' or 'propertyedit'
     *         property rights are checked
     * @return bool / int
     *         <true>:  Access to the property is granted.
     *         <false>: Access to the property is denied.
     *      -1: $action is not concerned with properties.
     */
    private static function checkPropertyAccess(Title $title, User $user, $action)
    {
        if (self::$mMode == HACLEvaluator::DENY_DIFF)
            return false;
        if (self::$mMode == HACLEvaluator::ALLOW_PROPERTY_READ && $action == 'propertyread')
            return true;

        switch ($action)
        {
            case 'propertyread':
                $actionID = HACLLanguage::RIGHT_READ;
                break;
            case 'propertyformedit':
                $actionID = HACLLanguage::RIGHT_EDIT;
                break;
            case 'propertyedit':
                $actionID = HACLLanguage::RIGHT_EDIT;
                break;
            default:
                // No property access requested
                return -1;
        }
        if (self::$mSavePropertiesAllowed)
            return true;
        return self::hasPropertyRight($title, $user->getId(), $actionID);
    }

    /**
     * This function checks if the values of the property $property have changed
     * in the comparison of the semantic database ($oldValues) and the wiki text
     * that is about to be stored ($newValues).
     *
     * @param SMWPropertyValue $property
     *         The property whose old and new values are compared.
     * @param SMWSemanticData $oldValues
     *         The semantic data object with the old values
     * @param SMWSemanticData $newValues
     *         The semantic data object with the new values
     * @return boolean
     *         <true>, if values have been added, removed or changed,
     *         <false>, if values are exactly the same.
     */
    private static function propertyValuesChanged(
        SMWPropertyValue $property, SMWSemanticData $oldValues,
        SMWSemanticData $newValues)
    {
        // Get all old values of the property
        $oldPV = $oldValues->getPropertyValues($property);
        $oldValues = array();
        self::$mMode = HACLEvaluator::ALLOW_PROPERTY_READ;
        foreach ($oldPV as $v)
            $oldValues[$v->getHash()] = false;
        self::$mMode = HACLEvaluator::NORMAL;

        // Get all new values of the property
        $newPV = $newValues->getPropertyValues($property);
        foreach ($newPV as $v)
        {
            self::$mMode = HACLEvaluator::ALLOW_PROPERTY_READ;
            $wv = $v->getWikiValue();
            if (empty($wv))
            {
                // A property has an empty value => can be ignored
                continue;
            }

            $nv = $v->getHash();
            self::$mMode = HACLEvaluator::NORMAL;
            if (array_key_exists($nv, $oldValues))
            {
                // Old value was not changed
                $oldValues[$nv] = true;
            }
            else
            {
                // A new value was added
                return true;
            }
        }

        foreach ($oldValues as $stillThere)
        {
            if (!$stillThere)
            {
                // A property value has been deleted
                return true;
            }
        }

        // Property values have not changed.
        return false;
    }

    /**
     * Starts the log for an evaluation. The log string is assembled in self::mLog.
     *
     * @param Title $title
     * @param User $user
     * @param string $action
     */
    private static function startLog($title, $user, $action) {
        global $wgRequest, $haclgEvaluatorLog;

        self::$mLogEnabled = $haclgEvaluatorLog
                             && $wgRequest->getVal('hacllog', 'false') == 'true';

        if (!self::$mLogEnabled) {
            // Logging is disabled
            return;
        }
        self::$mLog = "";

        self::$mLog .= "HaloACL Evaluation Log\n";
        self::$mLog .= "======================\n\n";
        self::$mLog .= "Article: ". (is_null($title) ? "null" : $title->getFullText()). "\n";
        self::$mLog .= "User: ". $user->getName(). "\n";
        self::$mLog .= "Action: $action\n";

    }

    /**
     * Adds a message to the evaluation log.
     *
     * @param string $msg
     *         The message to add.
     */
    private static function log($msg) {
        if (!self::$mLogEnabled) {
            // Logging is disabled
            return;
        }
        self::$mLog .= "$msg\n";
    }

    /**
     * Finishes the log for an evaluation.
     *
     * @param string $msg
     *         This message is added to the log.
     * @param bool $result
     *         The result of the evaluation:
     *             true - action is allowed
     *          false - action is forbidden
     * @param bool $returnVal
     *         Return value of the userCan-hook:
     *             true - HaloACL may have no opinion about the requested right. Other
     *                 extensions must decide.
     *             false - HaloACL found a right and stops the chain of userCan-hooks
     */
    private static function finishLog($msg, $result, $returnVal) {
        if (!self::$mLogEnabled) {
            // Logging is disabled
            return;
        }

        self::$mLog .= "$msg\n";
        self::$mLog .= "The action is ". ($result ? "allowed.\n" : "forbidden.\n");
        if ($returnVal) {
            // HaloACL has no opinion about the requested right.
            self::$mLog .= "The system and other extensions can still decide if this action is allowed.\n";
        } else {
            self::$mLog .= "The right is determined by HaloACL. No other extensions can influence this.\n";
        }
        self::$mLog .= "\n\n";

        echo self::$mLog;
    }

}