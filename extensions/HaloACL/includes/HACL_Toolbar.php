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
 * Description of HACL_Toolbar
 *
 * @author hipath
 */

class HACLToolbar
{
static function haclGetHACLToolbar($articleTitle) {
    global $wgUser;
    global $wgRequest;
    global $haclgContLang;
    $ns = $haclgContLang->getNamespaces();
    $ns = $ns[HACL_NS_ACL];
    $pagePrefix = $haclgContLang->getPetPrefix(HACLSecurityDescriptor::PET_PAGE);
    $templatePrefix = $haclgContLang->getSDTemplateName();

    $isPageProtected = false;
    $toolbarEnabled = true;

    if (!is_object($articleTitle))
        $articleTitle = Title::newFromText($articleTitle);

    // does that article exist or is it a new article
    $newArticle = !$articleTitle->exists();

    // retrieving quickacl
    $quickacls = HACLQuickacl::newForUserId($wgUser->getId());
    $tpllist = array();
    $validTmpl = array();
    $protectedWith = "";

    // is it a new article?
    if(!$newArticle) {
    // trying to get assigned right
        try {
            $SD = HACLSecurityDescriptor::newFromName("$ns:$pagePrefix/".$articleTitle);
            $protectedWith = $SD->getSDName();
            $isPageProtected = true;
            if(!$SD->userCanModify($wgUser->getName())) {
                $toolbarEnabled = false;
            }
        }
        catch(Exception $e) {

        }
    }

    // adding quickaclpages to selectbox
    foreach($quickacls->getSD_IDs() as $sdid) {
        $sd = HACLSecurityDescriptor::nameForID($sdid);
        $tpllist[] = $sd;

        // Check if the template is valid or corrupted by missing groups, user,...
        try {
            $sd = HACLSecurityDescriptor::newFromID($sdid);
            $validTmpl[] = $sd->checkIntegrity() === true ? 'true' : 'false';
        } catch (HACLException $e) {
            $validTmpl[] = false;
        }
    }


    // does a default template exist?
    $defaultSD = null;
    try {

        $defaultSD = HACLSecurityDescriptor::newFromName("$ns:$templatePrefix/".$wgUser->getName());
        $defaultSDExists = true;
        // if no other right is assigned to that article the default will be used
        if(!$isPageProtected) {
            $protectedWith = "$templatePrefix/".$wgUser->getName();
            $isPageProtected = true;
        }
    } catch(Exception $e) {
        $defaultSDExists = false;
    }

    if ($toolbarEnabled)
    {
        $st = $wgRequest->getVal('haloacl_protect_with');
        if ($st)
        {
            if ($isPageProtected = ($st != 'unprotected'))
                $protectedWith = $st;
        }
    }

    // building quickacl / protected with-indicator

    if($protectedWith != "" && !in_array($protectedWith, $tpllist)) {
        $tpllist[] = $protectedWith;
        // Check if the template is valid or corrupted by missing groups, user,...
        $validTmpl[] = (is_null($defaultSD) || $defaultSD->checkIntegrity() === true)
                        ? 'true' : 'false';
    }

    global $haclgIP;
    $html = <<<EOF
        <script type="text/javascript">
            YAHOO.haloacl.toolbar_initToolbar();
        </script>

        <div id="hacl_toolbarcontainer" class="yui-skin-sam hacl_toolbar_validAcl">
        <div id="hacl_toolbarcontainer_section1">
        <span id="hacl_page_state" class="hacl_toolbar_validAclText">Page state:&nbsp;</span>
EOF;

    if($toolbarEnabled) {
        $html .= '<select id="haloacl_toolbar_pagestate" onChange="YAHOO.haloacl.toolbar_updateToolbar();">';
    }else {
        $html .= '<select disabled id="haloacl_toolbar_pagestate" onChange="YAHOO.haloacl.toolbar_updateToolbar();">';
    }
    // bulding protected state indicator

    $hacl_protected_label = wfMsg('hacl_protected_label');
    $hacl_unprotected_label = wfMsg('hacl_unprotected_label');

    if($isPageProtected) {
        $html .= "   <option value='unprotected'>$hacl_unprotected_label</option>
                     <option selected='selected' value='protected'>$hacl_protected_label</option>
                     </select>";
    } elseif (sizeof($tpllist) > 0) {
        $html .= "   <option selected='selected' value='unprotected'>$hacl_unprotected_label</option>
                     <option value='protected'>$hacl_protected_label</option>
                     </select>";
    }else {
        $html .= "   <option value='unprotected'>$hacl_unprotected_label</option>
                     </select>";
    }

    //    if(sizeof($tpllist) > 0) {
    $html .= "</div><span id='haloacl_template_protectedwith_desc' class='hacl_toolbar_validAclText'>&nbsp;with:&nbsp;</span>";
    if($toolbarEnabled) {
        $html .= "<select id='haloacl_template_protectedwith' onChange='YAHOO.haloacl.toolbar_templateChanged();'>";
    }else {
        $html .= "<select disabled id='haloacl_template_protectedwith'>";
    }
    foreach($tpllist as $idx => $tpl) {
        $validAttr = ' valid="'.$validTmpl[$idx].'" ';
        if($tpl == $protectedWith) {
            $html .= "<option selected='selected' $validAttr value='$tpl'>$tpl</option>";
        }else {
            $html .= "<option $validAttr value='$tpl'>$tpl</option>";
        }
    }
    $html .= "</select>";
    $html .= <<<EOF
<div id="haloacl_toolbar_popuplink" style="display:inline;float:right">
    <div id="anchorPopup_toolbar"
         class="haloacl_infobutton"
         onclick="javascript:
            var tpw = $('haloacl_template_protectedwith');
            var protectedWith = tpw[tpw.selectedIndex].text;
            YAHOO.haloacl.sDpopupByName(protectedWith)">&nbsp;
    </div>
</div>
EOF;
    //    }

    if(!$newArticle) {
        $tooltiptext = wfMsg('hacl_advancedToolbarTooltip');

        $html .= <<<EOF

        <div id="hacl_toolbarcontainer_section3">
            <a id="haloacl_toolbar_advrightlink" target="_blank" href="index.php?title=Special:HaloACL&articletitle={$articleTitle}">&raquo; Advanced access rights definition</a>
        </div>

    <script>
YAHOO.haloacl.toolbar_updateToolbar();
    var a = new YAHOO.widget.Tooltip("hacl_toolbarcontainer_section3_tooltip", {
        context:"haloacl_toolbar_advrightlink",
        text:"$tooltiptext",
        zIndex :10
    });
    </script>

EOF;
    }
    $html .= '<div style="clear:both"></div>';
    $html .= '<div id="popup_toolbar"></div>';
    $html .= '</div>';
    return $html;

}
}
