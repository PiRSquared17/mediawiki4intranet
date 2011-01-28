<style type="text/css">
.acle p { margin: 0 0 8px 0; }
p.inactive, p.inactive input, p.inactive select { color: gray; }
#acl_pn { padding: 4px; border: 2px dashed orange; }
#acl_exists_hint { padding: 4px 0; color: orange; font-weight: bold; }
.hacl_tip { border: 1px solid gray; color: gray; width: 200pt; background-color: white; }
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
  <p><b><?= wfMsg('hacl_edit_definition_text') ?></b></p>
  <p><textarea id="acl_def" name="wpTextbox1" rows="6" style="width: 500px" onchange="parse_make_closure()"><?= $aclArticle ? htmlspecialchars($aclArticle->getContent()) : '' ?></textarea></p>
  <p><b><?= wfMsg('hacl_edit_definition_target') ?></b></p>
  <p>
   <select id="acl_what" onchange="target_change(true)">
    <?php foreach($this->aclTargetTypes as $t => $l) { ?>
     <optgroup label="<?= wfMsg('hacl_edit_'.$t) ?>">
     <?php foreach($l as $k => $true) { ?>
      <option id="acl_what_<?= $k ?>" value="<?= $k ?>"><?= wfMsg("hacl_define_$k") ?></option>
     <?php } ?>
     </optgroup>
    <?php } ?>
   </select>
   <input type="text" autocomplete="off" id="acl_name" onchange="target_change(true)" onkeyup="target_change()" style="width: 200px" />
  </p>
 </td>
 <td style="vertical-align: top">
  <p><b><?= wfMsg('hacl_edit_modify_definition') ?></b></p>
  <p>
   <select id="to_type" onchange="to_type_change()">
    <option value="user"><?= wfMsg('hacl_edit_user') ?></option>
    <option value="group"><?= wfMsg('hacl_edit_group') ?></option>
    <option value="*"><?= wfMsg('hacl_edit_all') ?></option>
    <option value="#"><?= wfMsg('hacl_edit_reg') ?></option>
   </select>
   <input type="text" id="to_name" style="width: 200px" autocomplete="off" />
  </p>
  <p>
   <input type="checkbox" id="act_all" onclick="act_change(this)" onchange="act_change(this)" />
   <label for="act_all" id="act_label_all"><?= $haclgContLang->mActionNames['all'] ?></label>
   <input type="checkbox" id="act_manage" onclick="act_change(this)" onchange="act_change(this)" />
   <label for="act_manage" id="act_label_manage"><?= $haclgContLang->mActionNames['manage'] ?></label>
   <br />
   <?php foreach(explode(',', 'read,edit,create,delete,move') as $k) { ?>
   <input type="checkbox" id="act_<?= $k ?>" onclick="act_change(this)" onchange="act_change(this)" />
   <label for="act_<?= $k ?>" id="act_label_<?= $k ?>"><?= $haclgContLang->mActionNames[$k] ?></label>
   <?php } ?>
  </p>
  <?php if($predefinedRightsExist) { ?>
  <p>
   <?= wfMsg('hacl_edit_include_right') ?> <input type="text" id="inc_acl" />
   <input type="button" value="<?= wfMsg('hacl_edit_include_do') ?>" onclick="include_acl()" />
  </p>
  <?php } ?>
 </td>
</tr>
</table>
<p id="acl_pns">
 <span><a id="acl_pn" href="#"></a></span>
 <input type="submit" name="wpSave" value="<?= wfMsg($aclSD ? 'hacl_edit_save' : 'hacl_edit_create') ?>" id="wpSave" />
 <a id="acl_delete_link" href="<?= $aclSD ? Title::newFromText($aclSD->getSDName(), HACL_NS_ACL)->getLocalUrl(array('action' => 'delete')) : '' ?>"><?= wfMsg('hacl_edit_delete') ?></a>
</p>
<p id="acl_pnhint" style="display: none; color: red; font-style: italic"><?= wfMsg('hacl_edit_enter_name_first') ?></p>
<p id="acl_exists_hint"><?= wfMsg('hacl_edit_sd_exists') ?></p>
</form>

<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/exAttach.js"></script>
<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/offsetRect.js"></script>
<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/SHint.js"></script>

<script language="JavaScript">
var aclNsText = '<?= $wgContLang->getNsText(HACL_NS_ACL) ?>';
var msgStartTyping = {
    'page' : '<?= wfMsg('hacl_edit_prompt_page') ?>',
    'user' : '<?= wfMsg('hacl_edit_prompt_user') ?>',
    'group' : '<?= wfMsg('hacl_edit_prompt_group') ?>',
    'category' : '<?= wfMsg('hacl_edit_prompt_category') ?>'
};
var msgEditSave = '<?= wfMsg('hacl_edit_save') ?>';
var msgEditCreate = '<?= wfMsg('hacl_edit_create') ?>';
var msgAffected = {
    'user'  : '<?= wfMsg('hacl_edit_users_affected') ?>',
    'group' : '<?= wfMsg('hacl_edit_groups_affected') ?>'
};
var userNsRegexp = '(^|,\s*)Участник:';
var groupPrefixRegexp = '(^|,\s*)Group:';
var petPrefix = {
<?php
$i = 0;
foreach($haclgContLang->mPetPrefixes as $k => $v)
{
    if ($i++) print ",";
    $v = addslashes($v);
    print "'$k' : '$v'\n";
}
?>
};

exAttach(window, 'load', function() {
<?php if ($aclSD) {
list($t, $n) = explode('/', $aclSD->getSDName(), 2);
$title = Title::newFromId($aclSD->getSDID());
$aclType = $aclSD->getPEType();
?>
document.getElementById('acl_name').value = "<?= addslashes($n) ?>";
petPrefix['<?= $aclType ?>'] = "<?= addslashes($t) ?>";
document.getElementById('acl_what_<?= $aclType ?>').selected = true;
parse_make_closure();
<? } ?>
acl_init_editor();
<?php if ($aclSD) { ?>
document.getElementById('acl_exists_hint').style.display = '';
<? } ?>
});
</script>

<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/HACL_ACLEditor.js"></script>
