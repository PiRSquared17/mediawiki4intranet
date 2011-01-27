<?= wfMsg('hacl_quickaccess_manage_text') ?>
<fieldset style="margin: 0 0 16px 0">
 <legend><?= wfMsg('hacl_quickaccess_filter_sds') ?></legend>
 <form action="<?= $wgScript ?>">
  <label for="hacl_qafilter"><?= wfMsg('hacl_quickaccess_filter') ?></label>
  <input type="hidden" name="title" value="Special:HaloACL" />
  <input type="hidden" name="action" value="quickaccess" />
  <input type="text" name="like" id="hacl_qafilter" value="<?= htmlspecialchars($args['like']) ?>" />
  <input type="submit" value="<?= wfMsg('hacl_quickaccess_filter_submit') ?>" />
 </form>
</fieldset>
<?php if ($templates) { ?>
<p><?= wfMsg('hacl_quickaccess_hint') ?></p>
<form action="<?= $wgScript ?>?title=Special:HaloACL&action=quickaccess&save=1" method="POST">
 <ul>
  <?php foreach ($templates as $sd) { ?>
   <li>
    <?php if (!$sd->owntemplate) { ?>
     <input type="checkbox" name="qa_<?= $sd->getSDId() ?>" <?= $sd->selected ? ' checked="checked"' : '' ?> />
    <?php } ?>
    <a title="<?= $sd->getSDName() ?>" href="<?= $sd->viewlink ?>"><?= $sd->getSDName() ?></a>&nbsp;
    <a title="<?= wfMsg('hacl_acllist_edit') ?>" href="<?= $sd->editlink ?>"><img src="<?= $haclgHaloScriptPath ?>/skins/images/edit.png" /></a>
    <?php if ($sd->owntemplate) print wfMsg('hacl_quickaccess_own_template'); ?>
   </li>
  <?php } ?>
 </ul>
 <p><input type="submit" value="<?= wfMsg('hacl_quickaccess_save') ?>" /></p>
</form>
<?php } else { ?>
 <?= wfMsg('hacl_empty_list') ?>
<?php } ?>
