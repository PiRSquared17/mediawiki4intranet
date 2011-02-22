<?= wfMsg('hacl_qacl_manage_text') ?>
<fieldset style="margin: 0 0 16px 0">
 <legend><?= wfMsg('hacl_qacl_filter_sds') ?></legend>
 <form action="<?= $wgScript ?>">
  <label for="hacl_qafilter"><?= wfMsg('hacl_qacl_filter') ?></label>
  <input type="hidden" name="title" value="Special:HaloACL" />
  <input type="hidden" name="action" value="quickaccess" />
  <input type="text" name="like" id="hacl_qafilter" value="<?= htmlspecialchars($args['like']) ?>" />
  <input type="submit" value="<?= wfMsg('hacl_qacl_filter_submit') ?>" />
 </form>
</fieldset>
<?php if ($templates) { ?>
<p><?= wfMsg('hacl_qacl_hint') ?></p>
<form action="<?= $wgScript ?>?title=Special:HaloACL&action=quickaccess&save=1" method="POST">
 <input type="hidden" name="like" value="<?= htmlspecialchars($args['like']) ?>" />
 <table class="wikitable">
  <tr>
   <th>Select</th>
   <th>Default</th>
   <th>Name</th>
   <th>Actions</th>
  </tr>
  <?php foreach ($templates as $sd) { ?>
   <tr>
    <td style="text-align: center">
     <?php if (!$sd->owntemplate) { ?>
      <input type="checkbox" name="qa_<?= $sd->getSDId() ?>" id="qa_<?= $sd->getSDId() ?>" <?= $sd->selected ? ' checked="checked"' : '' ?> />
     <?php } ?>
    </td>
    <td style="text-align: center">
     <input onchange="set_checked(<?= $sd->getSDId() ?>)" type="radio" name="qa_default" id="qd_<?= $sd->getSDId() ?>" value="<?= $sd->getSDId() ?>" <?= $sd->default ? ' checked="checked"' : '' ?> />
    </td>
    <td><a title="<?= $sd->getSDName() ?>" href="<?= $sd->viewlink ?>"><?= $sd->getSDName() ?></a></td>
    <td style="text-align: center">
     <a title="<?= wfMsg('hacl_acllist_edit') ?>" href="<?= $sd->editlink ?>">
      <img src="<?= $haclgHaloScriptPath ?>/skins/images/edit.png" />
     </a>
    </td>
   </tr>
  <?php } ?>
 </table>
 <p>
  <input type="submit" value="<?= wfMsg('hacl_qacl_save') ?>" style="font-weight: bold" />
  <input type="button" value="<?= wfMsg('hacl_qacl_clear_default') ?>" onclick="clear_default()" />
 </p>
</form>
<script language="JavaScript">
var curDefault = <?= $quickacl->getDefaultSD_ID() ?>;
var clear_default = function() {
  var d = document.getElementById('qd_'+curDefault);
  if (d)
  {
    d.checked = false;
    curDefault = 0;
  }
};
var set_checked = function(x) {
  if (document.getElementById('qd_'+x).checked)
  {
    curDefault = x;
    document.getElementById('qa_'+x).checked = true;
  }
};
</script>
<?php } else { ?>
 <?= wfMsg('hacl_qacl_empty') ?>
<?php } ?>
