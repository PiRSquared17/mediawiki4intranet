<form action="<?= $wgScript ?>?action=submit" method="POST">
<input type="hidden" name="wpEditToken" value="<?= htmlspecialchars($wgUser->editToken()) ?>" />
<input type="hidden" name="wpEdittime" value="<?= $groupTitle ? $groupArticle->getTimestamp() : '' ?>" />
<input type="hidden" name="wpStarttime" value="<?= wfTimestampNow() ?>" />
<input type="hidden" id="wpTitle" name="title" value="<?= $groupTitle ? htmlspecialchars($groupTitle()->getPrefixedText()) : '' ?>" />
<table class="acle">
<tr>
 <td style="vertical-align: top; width: 400px">
  <p><b><?= wfMsg('hacl_grp_definition_text') ?></b></p>
  <p><textarea id="group_def" name="wpTextbox1" rows="6" style="width: 400px" onchange="parse_make_closure()"><?= $groupTitle ? htmlspecialchars($groupArticle->getContent()) : '' ?></textarea></p>
 </td>
 <td style="vertical-align: top">
  <table>
   <tr>
    <th colspan="2"><?= wfMsg('hacl_grp_members') ?></th>
    <th colspan="2"><?= wfMsg('hacl_grp_managers') ?></th>
   </tr>
   <tr>
    <th><?= wfMsg('hacl_grp_users') ?></th>
    <td><input type="text" id="member_users" style="width: 200px" /></td>
    <th><?= wfMsg('hacl_grp_users') ?></th>
    <td><input type="text" id="manager_users" style="width: 200px" /></td>
   </tr>
   <tr>
    <th><?= wfMsg('hacl_grp_groups') ?></th>
    <td><input type="text" id="member_groups" style="width: 200px" /></td>
    <th><?= wfMsg('hacl_grp_groups') ?></th>
    <td><input type="text" id="manager_groups" style="width: 200px" /></td>
   </tr>
  </table>
 </td>
</tr>
</table>
<p id="group_pns">
 <span><a id="group_pn" href="#"></a></span>
 <input type="submit" name="wpSave" value="<?= wfMsg($groupArticle ? 'hacl_grp_save' : 'hacl_grp_create') ?>" id="wpSave" />
 <a id="group_delete_link" href="<?= $groupTitle ? $groupTitle->getLocalUrl(array('action' => 'delete')) : '' ?>"><?= wfMsg('hacl_grp_delete') ?></a>
</p>
<p id="group_pnhint" style="display: none"><?= wfMsg('hacl_edit_enter_name_first') ?></p>
<p id="group_exists_hint" style="display: none"><?= wfMsg('hacl_edit_sd_exists') ?></p>
<p id="group_define_members" style="display: none"><?= wfMsg('hacl_grp_define_members') ?></p>
<p id="group_define_manager" style="display: none"><?= wfMsg('hacl_grp_define_manager') ?></p>
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
var msgEditSave = '<?= wfMsg('hacl_grp_save') ?>';
var msgEditCreate = '<?= wfMsg('hacl_grp_create') ?>';
var msgAffected = {
    'user'      : '<?= wfMsg('hacl_edit_users_affected') ?>',
    'group'     : '<?= wfMsg('hacl_edit_groups_affected') ?>',
    'nouser'    : '<?= wfMsg('hacl_edit_no_users_affected') ?>',
    'nogroup'   : '<?= wfMsg('hacl_edit_no_groups_affected') ?>'
};
var userNsRegexp = '(^|,\s*)Участник:';
var groupPrefixRegexp = '(^|,\s*)Group:';

exAttach(window, 'load', function() {
<?php if ($groupTitle) {
list($t, $n) = explode('/', $groupTitle->getText(), 2); ?>
document.getElementById('group_name').value = "<?= addslashes($n) ?>";
parse_make_closure();
<? } ?>
group_init_editor();
<?php if ($groupTitle) { ?>
document.getElementById('group_exists_hint').style.display = '';
<? } ?>
});
</script>

<script language="JavaScript" src="<?= $haclgHaloScriptPath ?>/scripts/HACL_GroupEditor.js"></script>
