<!-- theme tab -->
<div role="tabpanel" class="tab-pane" id="theme">
  <h4 class="mt-3"><?php echo _("Theme settings") ;?></h4>
    <form action="system_info" method="POST">
    <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
    <div class="row">
      <div class="mb-3 col-xs-3 col-sm-3">
        <label for="code"><?php echo _("Select a theme"); ?></label>
        <?php SelectorOptions("theme", $themes, $selectedTheme, "theme-select") ?>
      </div>
      <div class="col-xs-3 col-sm-3">
        <label for="code"><?php echo _("Color"); ?></label>
        <input class="form-control color-input" value="#2b8080" aria-label="color" />
      </div>
    </div>
      <div class="row">
        <div class="col-md-6 mb-2">
        <h5 class="mt-1"><?php echo _("Alert messages"); ?></h5>          
          <div class="form-check form-switch">
            <?php $checked = $optAutoclose == 1 ? 'checked="checked"' : '' ?>
            <input class="form-check-input" id="chxautoclose" name="autoClose" type="checkbox" value="1" <?php echo $checked ?> />
            <label class="form-check-label" for="chxautoclose"><?php echo _("Automatically close alerts after a specified timeout"); ?></label>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="code"><?php echo _("Alert close timeout (milliseconds)") ;?></label>
          <input type="text" class="form-control" name="alertTimeout" value="<?php echo htmlspecialchars($alertTimeout, ENT_QUOTES); ?>" />
        </div>
      </div>
      <?php if (!RASPI_MONITOR_ENABLED) : ?>
      <input type="submit" class="btn btn-outline btn-primary" name="savethemeSettings" value="<?php echo _("Save settings"); ?>" />
      <?php endif; ?>
      <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
    </form>
</div>


