<style type="text/css">
.acle p { margin: 0 0 8px 0; }
p.inactive, p.inactive input, p.inactive select { color: gray; }
#acl_pn { padding: 4px; border: 2px dashed orange; }
.hacl_tip { border: 1px solid gray; color: gray; width: 200px; background-color: white; z-index: 100; }
.hacl_tt { padding: 3px; }
.hacl_ti { color: black; cursor: pointer; padding: 1px 3px; }
.hacl_ti_a { color: white; background-color: #008; cursor: pointer; padding: 1px 3px; }
.act_disabled { color: gray; }
</style>

<form action="<?= $wgScript ?>?action=submit" method="POST">
<input type="hidden" name="wpEditToken" value="<?= htmlspecialchars($wgUser->editToken()) ?>" />
<input type="hidden" name="wpEdittime" value="<?= $aclArticle ? $aclArticle->getTimestamp() : '' ?>" />
<input type="hidden" name="wpStarttime" value="<?= wfTimestampNow() ?>" />
<input type="hidden" id="wpTitle" name="title" value="<?= $aclArticle ? htmlspecialchars($aclArticle->getTitle()->getPrefixedText()) : '' ?>" />
<table class="acle">
<tr>
 <td style="vertical-align: top; width: 500px">
  <p><b>Definition text:</b></p>
  <p><textarea id="acl_def" name="wpTextbox1" rows="6" style="width: 500px" onchange="parse_make_closure()"><?= $aclArticle ? htmlspecialchars($aclArticle->getContent()) : '' ?></textarea></p>
  <p id="p_sd">
   <input type="radio" id="acl_sd" name="protect_what" checked="checked" onchange="target_change()" onclick="target_change()" />
   <label for="acl_sd">Protect:</label>
   <select id="acl_what" onchange="target_change()">
    <?php foreach(explode(',', 'page,namespace,category'.(defined('SMW_NS_PROPERTY') ? ',property' : '')) as $k) { ?>
    <option id="acl_what_<?= $k ?>" value="<?= $haclgContLang->mPetPrefixes[$k] ?>"><?= $k ?></option>
    <?php } ?>
   </select>
   <input type="text" id="acl_name" onchange="target_change()" onkeyup="target_change()" />
  </p>
  <p id="p_right" class="inactive">
   <input type="radio" id="acl_right" name="protect_what" onchange="target_change()" onclick="target_change()" />
   <label for="acl_right">Define right:</label>
   <input type="text" id="acl_right_name" onchange="target_change()" onkeyup="target_change()" />
  </p>
  <p id="acl_pns"><span><a id="acl_pn" href="#">ACL:Page/</a></span> <input type="submit" name="wpSave" value="Create ACL" /></p>
 </td>
 <td style="vertical-align: top">
  <p><b>Modify definition:</b></p>
  <p>
   <select id="to_type" onchange="to_type_change()">
    <option value="user">User</option>
    <option value="group">Group</option>
    <option value="*">All users</option>
    <option value="#">Registered users</option>
   </select>
   <input type="text" id="to_name" style="width: 200px" autocomplete="off" />
  </p>
  <p>
   <?php foreach(explode(',', 'read,edit,create,delete,move,manage') as $k) { ?>
   <input type="checkbox" id="act_<?= $k ?>" onclick="act_change(this)" onchange="act_change(this)" />
   <label for="act_<?= $k ?>" id="act_label_<?= $k ?>"><?= $haclgContLang->mActionNames[$k] ?></label>
   <?php } ?>
  </p>
  <p>
   Include predefined right:
   <select id="inc_acl"><option value="ACL:Right/Test">Test</option></select>
   <input type="button" value="Include" onclick="include_acl()" />
  </p>
 </td>
</tr>
</table>
</form>

<div id="hacl_tip" style="display: none"></div>

<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/exAttach.js"></script>
<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/offsetRect.js"></script>
<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/SHint.js"></script>
<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/HACL_ACLEditor.js"></script>

<script language="JavaScript">
<?php if ($aclSD) {
list($t, $n) = explode('/', $aclSD->getSDName());
$title = Title::newFromId($aclSD->getSDID());
$aclType = $aclSD->getPEType();
switch ($aclType)
{
    case HACLSecurityDescriptor::PET_RIGHT: ?>
        document.getElementById('acl_right').checked = true;
        document.getElementById('acl_right_name').value = "<?= addslashes($n) ?>";
        aclRightPrefix = "<?= addslashes($t) ?>";
        <? break;
    case HACLSecurityDescriptor::PET_PAGE:
    case HACLSecurityDescriptor::PET_CATEGORY:
    case HACLSecurityDescriptor::PET_NAMESPACE:
    case HACLSecurityDescriptor::PET_PROPERTY: ?>
        document.getElementById('acl_name').value = "<?= addslashes($n) ?>";
        document.getElementById('acl_what_<?= $aclType ?>').value = "<?= addslashes($t) ?>";
        <? break;
}
?>
to_name_change();
<? } ?>
target_change();
</script>
