<div id="hacl_toolbar">
<?php if (count($options) > 1 && $canModify) { ?>
 <label for="hacl_protected_with"><?= wfMsg('hacl_toolbar_page_prot') ?></label>
 <select name="hacl_protected_with" id="hacl_protected_with" onchange="hacl_change_toolbar_goto(this, '<?= wfMsg('hacl_toolbar_goto') ?>')">
  <?php foreach($options as $o) { ?>
   <option title="<?= htmlspecialchars($o['title']) ?>" <?= $o['current'] ? ' selected="selected"' : '' ?> value="<?= htmlspecialchars($o['value']) ?>"><?= htmlspecialchars($o['name']) ?></option>
  <?php } ?>
 </select>
 <?php if ($options[$selectedIndex]['title']) { ?>
  <a id="hacl_toolbar_goto" href="<?= Title::newFromText($options[$selectedIndex]['title'])->getLocalUrl() ?>" target="_blank" title="<?= htmlspecialchars(wfMsg('hacl_toolbar_goto', $options[$selectedIndex]['title'])) ?>">
   <img src="<?= $wgScriptPath ?>/skins/monobook/external.png" width="10" height="10" alt="&rarr;" />
  </a>
 <?php } else { ?>
  <a id="hacl_toolbar_goto" href="#" target="_blank" style="display: none">
   <img src="<?= $wgScriptPath ?>/skins/monobook/external.png" width="10" height="10" alt="&rarr;" />
  </a>
 <?php } ?>
<?php } elseif (!$canModify) { ?>
 <?= wfMsg('hacl_toolbar_cannot_modify') ?>
<?php } else { ?>
 <?= wfMsg('hacl_toolbar_no_right_templates') ?>
<?php } if ($globalACL) { ?>
 <div id="hacl_toolbar_global_acl"><div style="position: absolute"><?= $globalACL ?></div></div>
 <span><?= wfMsg('hacl_toolbar_global_acl') ?></span>
<?php } if ($title->exists()) { ?>
 &nbsp; <a target="_blank" href="index.php?title=Special:HaloACL&action=acl&sd=<?= urlencode($haclgContLang->getPetPrefix(HACLLanguage::PET_PAGE).'/'.$title) ?>"><?= wfMsg('hacl_toolbar_advanced') ?></a>
<?php } ?>
</div>
