<div id="hacl_toolbar">
<?php if ($options) { ?>
 <span id="hacl_pagestate"><?= wfMsg('haloacl_toolbar_page_state') ?></span>
 <select <?= $canModify ? '' : ' disabled="disabled"' ?> id="haloacl_toolbar_pagestate">
  <option <?= $protected ? '' : ' selected="selected"' ?> value="unprotected"><?= wfMsg('hacl_unprotected_label') ?></option>
  <option <?= $protected ? ' selected="selected"' : '' ?> value="protected"><?= wfMsg('hacl_protected_label') ?></option>
 </select>
 <span id="haloacl_protectedwith"><?= wfMsg('haloacl_toolbar_with') ?></span>
 <select <?= $canModify ? '' : ' disabled="disabled"' ?> id="haloacl_template_protectedwith" onChange="">
  <?php foreach($options as $o) { ?>
   <option <?= $o['current'] ? ' selected="selected"' : '' ?> value="<?= htmlspecialchars($o['sdname']) ?>"><?= htmlspecialchars($o['sdname']) ?></option>
  <?php } ?>
 </select>
<?php } else { ?>
 <?= wfMsg('hacl_toolbar_no_right_templates') ?>
<?php }
if ($title->exists()) { ?>
 <a target="_blank" href="index.php?title=Special:HaloACL&action=acl&sd=<?= htmlspecialchars($title) ?>"><?= wfMsg('haloacl_toolbar_advanced') ?></a>
<? } ?>
</div>
