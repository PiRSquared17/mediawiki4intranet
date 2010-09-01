<?php
/*  Copyright 2009, ontoprise GmbH
*  This file is part of the HaloACL-Extension.
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
 * This file contains the class HACLDefaultSD.
 *
 * @author Thomas Schweitzer
 * Date: 22.05.2009
 *
 */
if ( !defined( 'MEDIAWIKI' ) ) {
    die( "This file is part of the HaloACL extension. It is not a valid entry point.\n" );
}


 //--- Includes ---
 global $haclgIP;
//require_once("$haclgIP/...");

/**
 * This class manages the default security descriptor for users.
 *
 * What happens when a user creates a new article? Does the user have to create
 * the corresponding security descriptor or is it created automatically?
 * And if so, what is its initial content?
 *
 * "Default security descriptors" satisfy three scenarios:
 *    1. The wiki is by default an open wiki i.e. all new articles are accessible
 *       by all users. Only if a page should be protected explicitly a security
 *       descriptor must be provided.
 *    2. New articles are automatically protected and belong to the author until
 *       he releases it. In this case a security descriptor must be created
 *       automatically with an ACL that permits only access for the author.
 *    3. New articles are automatically protected and belong to users and groups
 *       that can be freely defined. In this case a security descriptor must be
 *       created automatically with an ACL that can be configured.
 *
 * The solution for this is simple. Every user can define a template
 * (not a MediaWiki template) for his default ACL. There is a special article
 * with the naming scheme ACL:Template/<username> e.g. ACL:Template/Peter. This
 * template article can contain any kind of valid ACL as described above. It can
 * define rights for the author alone or arbitrary combinations of users and
 * groups.
 *
 * If the user creates a new article, the system checks, if he has defined an
 * ACL template. If not, no security descriptor is created. This solves the
 * problem of the first scenario, the open wiki. Otherwise, if the template
 * exists, a security descriptor is created and filled with the content of the
 * template. This serves the latter two scenarios.
 *
 * This class registers the hook "ArticleSaveComplete", which checks for each
 * saved article, if a default SD has to be created.
 *
 * @author Thomas Schweitzer
 *
 */
class  HACLDefaultSD  {

    //--- Constants ---
//    const XY= 0;        // the result has been added since the last time

    //--- Private fields ---
    private $mXY;            //string: comment

    /**
     * Constructor for  HACLDefaultSD
     *
     * @param type $param
     *         Name of the notification
     */
    function __construct() {
//        $this->mXY = $xy;
    }


    //--- getter/setter ---
//    public function getXY()           {return $this->mXY;}

//    public function setXY($xy)               {$this->mXY = $xy;}

    //--- Public methods ---


    /**
     * This method is called, after an article has been saved. If the article
     * belongs to the namespace ACL (i.e. a right, SD, group or whitelist)
     * it is ignored. Otherwise the following happens:
     * - Check the namespace of the article (must not be ACL)
     * - Check if $user is a registered user
     * - Check if the article already has an SD
     * - Check if the user has defined a default SD
     * - Create the default SD for the article.
     *
     * @param Article $article
     *         The article which was saved
     * @param User $user
     *         The user who saved the article
     * @param string $text
     *         The content of the article
     *
     * @return true
     */
    public static function articleSaveComplete(&$article, &$user, $text) {
        global $wgUser, $wgRequest;

        if ($article->getTitle()->getNamespace() == HACL_NS_ACL) {
            // No default SD for articles in the namespace ACL
            return true;
        }

        if ($user->isAnon()) {
            // Don't create default SDs for anonymous users
            return true;
        }

        $articleID = $article->getTitle()->getArticleID();

        $sdAlreadyDefinied = false;
        $createCustomSD = false;
        if (HACLSecurityDescriptor::getSDForPE($articleID, HACLSecurityDescriptor::PET_PAGE) !== false) {
            // There is already an SD for the article
            $sdAlreadyDefinied = true;
        }

        // has user defined anohter template than default sd
        $articleContent = $article->getContent();

        $prot = $wgRequest->getVal('haloacl_protect_with');
        if ($prot)
        {
            $templateToProtectWith = $prot;
            if (strpos($prot, 'Right/') !== false || $prot == 'unprotected')
                $createCustomSD = true;
        }

        // Did the user define a default SD
        // adding default sd to article

        if(!$sdAlreadyDefinied && !$createCustomSD) {
            global $haclgContLang;

            $ns = $haclgContLang->getNamespaces();
            $ns = $ns[HACL_NS_ACL];
            $template = $haclgContLang->getSDTemplateName();
            $defaultSDName = "$ns:$template/{$user->getName()}";
            $etc = haclfDisableTitlePatch();
            $defaultSD = Title::newFromText($defaultSDName);
            haclfRestoreTitlePatch($etc);
            if (!$defaultSD->exists()) {
                // No default SD defined
                return true;
            }

            // Create the default SD for the saved article
            // Get the content of the default SD
            $defaultSDArticle = new Article($defaultSD);
            $content = $defaultSDArticle->getContent();

            // Create the new SD
            $newSDName = HACLSecurityDescriptor::nameOfSD($article->getTitle()->getFullText(),
            HACLSecurityDescriptor::PET_PAGE);

            $etc = haclfDisableTitlePatch();
            $newSD = Title::newFromText($newSDName);
            haclfRestoreTitlePatch($etc);

            $newSDArticle = new Article($newSD);
            $newSDArticle->doEdit($content, "Default security descriptor.", EDIT_NEW);

            return true;
        }

        if($createCustomSD) {
            // now we create an new securitydescriptor
            if($templateToProtectWith != "unprotected") {
                global $haclgContLang;

                $ns = $haclgContLang->getNamespaces();
                $ns = $ns[HACL_NS_ACL];
                $defaultSDName = "$ns:$templateToProtectWith";
                $etc = haclfDisableTitlePatch();
                $defaultSD = Title::newFromText($defaultSDName);
                haclfRestoreTitlePatch($etc);
                if (!$defaultSD->exists()) {
                    // No default SD defined
                    return false;
                }

                // Create the default SD for the saved article
                // Get the content of the default SD

                                #$defaultSDArticle = new Article($defaultSD);
                #$content = $defaultSDArticle->getContent();

                // Create the new SD
                $newSDName = HACLSecurityDescriptor::nameOfSD($article->getTitle()->getFullText(),
                HACLSecurityDescriptor::PET_PAGE);

                #$etc = haclfDisableTitlePatch();
                $newSD = Title::newFromText($newSDName);
                #haclfRestoreTitlePatch($etc);
                                $content = "
{{#predefined right:rights=".$defaultSDName."}}
{{#manage rights:assigned to=User:".$wgUser->getName()."}}
[[Category:ACL/ACL]]
";

                $newSDArticle = new Article($newSD);
                $newSDArticle->doEdit($content, "Custom security descriptor.");

                return true;

                // we delete the actual assigned sd, if it exists
            }else {
                $newSDName = HACLSecurityDescriptor::nameOfSD($article->getTitle()->getFullText(),
                HACLSecurityDescriptor::PET_PAGE);

                $etc = haclfDisableTitlePatch();
                $newSD = Title::newFromText($newSDName);
                haclfRestoreTitlePatch($etc);

                $newSDArticle = new Article($newSD);
                if($newSDArticle->exists()) {
                    $newSDArticle->doDelete("securitydescriptor removed");
                }
            }
        }

        return true;
    }


    /**
     * This function is called when a user logs in.
     *
     * If $haclgNewUserTemplate is set, a default access rights template for new
     * articles is created, if it does not already exist.
     * Furthermore, the quick access list of the user is filled with all right
     * templates given in $haclgDefaultQuickAccessRightMasterTemplates.
     *
     * @param User $newUser
     *         User, whose default rights template is set.
     * @return boolean true
     */
    public static function newUser(User &$newUser, &$injectHTML) {

        // Get the content of the article with the master template in $haclgNewUserTemplate
        global $haclgNewUserTemplate, $haclgDefaultQuickAccessRightMasterTemplates;
        if (isset($haclgNewUserTemplate)) {
            // master template specified
            self::createUserDefaultTemplate($newUser);
        }
        if (isset($haclgDefaultQuickAccessRightMasterTemplates)) {
            self::setQuickAccessRights($newUser);
        }
        return true;
    }

    /**
     * Checks if the given user can modify the given title, if it is a
     * default security descriptor.
     *
     * @param Title $title
     *         The title that is checked.
     * @param User $user
     *         The user who wants to access the article.
     *
     * @return array(bool rightGranted, bool hasSD)
     *         rightGranted:
     *             <true>, if title is the name for a default SD and the user is
     *                     allowed to create it or if it no default SD
     *             <false>, if title is the name for a default SD and the user is
     *                      not allowed.
     *         hasSD:
     *             <true>, if the article is a default SD
     *             <false>, if not
     */
    public static function userCanModify($title, $user) {
        // Check for default rights template
        if ($title->getNamespace() !== HACL_NS_ACL) {
            // wrong namespace
            return array(true, false);
        }

        // Is this the master template for default templates of new users?
        global $haclgNewUserTemplate;
        if (isset($haclgNewUserTemplate)
            && $title->getFullText() == $haclgNewUserTemplate) {
            // User must be a sysop or bureaucrat
            $groups = $user->getGroups();
            $r = (in_array('sysop', $groups) || in_array('bureaucrat', $groups));
            return array($r, true);
        }

        global $haclgContLang;
        $prefix = $haclgContLang->getSDTemplateName();
        if (strpos($title->getText(), "$prefix/") !== 0) {
            // wrong prefix
            return array(true, false);
        }
        // article is a default rights template
        $userName = substr($title->getText(), strlen($prefix)+1);
        // Is this the template of another user?
        if ($user->getName() != $userName) {
            // no rights for other users but sysops and bureaucrats
            $groups = $user->getGroups();
            $r = (in_array('sysop', $groups) || in_array('bureaucrat', $groups));
            return array($r, true);
        }
        // user has all rights on the template
        return array(true, true);

    }

    //--- Private methods ---

    /**
     * If $haclgNewUserTemplate is set, a default access rights template for new
     * articles is created, if it does not already exist.
     *
     * @param User $newUser
     *         User, whose default rights template is set.
     */
    private static function createUserDefaultTemplate(User &$newUser) {
        // Check if the user already has a default template
        global $haclgContLang, $haclgNewUserTemplate;
        $ns = $haclgContLang->getNamespaces();
        $ns = $ns[HACL_NS_ACL];
        $template = $haclgContLang->getSDTemplateName();
        $defaultTemplateName = "$ns:$template/{$newUser->getName()}";
        self::copyTemplate($haclgNewUserTemplate, $defaultTemplateName, $newUser->getName());
    }

    /**
     * Copies the quick access right master templates for the current user and
     * adds them to his quick access list.
     *
     * @param User $newUser
     *     User, whose quick access rights are set.
     */
    private static function setQuickAccessRights(User &$newUser) {
        global $haclgContLang, $haclgDefaultQuickAccessRightMasterTemplates;

        $ns = $haclgContLang->getNamespaces();
        $ns = $ns[HACL_NS_ACL];
        $template = $haclgContLang->getSDTemplateName();
        $r = $haclgContLang->getPredefinedRightName();
        $rightPrefix = "$ns:$template/QARMT/";
        $userRightPrefix = "$ns:$r/";

        $uid = $newUser->getId();
        $quickACL = HACLQuickacl::newForUserId($uid);
        $sdAdded = false;
        foreach ($haclgDefaultQuickAccessRightMasterTemplates as $right) {
            // assemble the name of the right for the user
            if (strpos($right, $rightPrefix) !== 0) {
                // Rights must have a name like "ACL:Template/QARMT/<right name>"
                continue;
            }
            $destRight = $userRightPrefix
                         .$newUser->getName().'/'
                         .substr($right, strlen($rightPrefix));
            self::copyTemplate($right, $destRight, $newUser->getName());

            $sdID = HACLSecurityDescriptor::idForSD($destRight);
            if ($sdID) {
                $quickACL->addSD_ID($sdID);
                $sdAdded = true;
            }
        }
        if ($sdAdded) {
            $quickACL->save();
        }
    }

    /**
     * Copies the content of the right template with the name $source into the
     * article with the name $dest. If $source does not exist or if $dest already
     * exists, the operation is aborted. The source template may contain the
     * variable {{{user}}}. It will be replace with the given $username.
     *
     * @param string $source
     *         Name of the article that will be copied.
     * @param string $dest
     *         Name of the article that will be created as copy of $source.
     * @param string $username
     *         Name of the user that is inserted as {{{user}}}. The namespace
     *         for users (e.g. User:) will be prepended.
     *
     * @return bool
     *         <true> if the operation was successful or
     *         <false> if copying the articles failed
     */
    private static function copyTemplate($source, $dest, $username) {

        // Check if destination article already exists
        $etc = haclfDisableTitlePatch();
        $destTitle = Title::newFromText($dest);
        haclfRestoreTitlePatch($etc);
        if ($destTitle->exists()) {
            // The destination article already exists
            return true;
        }

        //-- Copy the content of the source article --
        // Get the content of the source article
        $etc = haclfDisableTitlePatch();
        $sourceTitle = Title::newFromText($source);
        haclfRestoreTitlePatch($etc);
        $sourceArticle = new Article($sourceTitle);
        if (!$sourceTitle->exists()) {
            // The source article does not exist
            return false;
        }
        $content = $sourceArticle->getContent();

        // Replace the variable {{{user}}} by the actual name of the user
        global $wgContLang;
        $userNs = $wgContLang->getNsText(NS_USER);
        $content = str_replace('{{{user}}}', $userNs.':'.$username, $content);

        HACLParserFunctions::getInstance()->reset();
        // Create the destination article
        $newArticle = new Article($destTitle);
        $newArticle->doEdit($content, "Default access control template.", EDIT_NEW);

        return true;
    }


}
