<!-- theme tab -->
<div role="tabpanel" class="tab-pane fade" id="theme">
  <h4 class="mt-3"><?php echo _("Theme settings") ;?></h4>
    <form action="system_info" method="POST">
    <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
    <div class="row">
      <div class="col-sm-6 col-md-3 mb-3">
        <label for="code"><?php echo _("Select a theme"); ?></label>
        <?php SelectorOptions("theme", $themes, $selectedTheme, "theme-select") ?>
      </div>
      <div class="col-sm-6 col-md-3 mb-3">
        <label for="code"><?php echo _("Color"); ?></label>
        <input class="form-control color-input" value="#2b8080" aria-label="color" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3 mb-3">
        <label><?php echo _("Dark Mode"); ?></label>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input dark-mode-toggle" id="settings-dark-mode" <?php echo getDarkMode() ? 'checked' : null ; ?>
            <?= (isset($_SESSION['theme']) && isset($_SESSION['theme']['modes']) && !in_array('dark', $_SESSION['theme']['modes'])) ? 'disabled' : null ?>
          >
          <label class="form-check-label" for="settings-dark-mode"><i class="far <?= !getDarkMode() ? 'fa-moon' : 'fa-sun' ?> mr-1"></i></label>
        </div>
        <span class="small text-muted"><?= _('Use the dark mode from the current theme') ?></span>
      </div>
      <div class="col-sm-3 mb-3">
        <label for="code"><?php echo _("System Theme"); ?></label>
        <div class="form-check form-switch">
          <input type="checkbox" class="form-check-input system-mode-toggle" id="settings-system-mode" <?= ($_COOKIE['use_system_color_scheme'] ?? false) === 'true' ? 'checked' : null ?>
            <?= (isset($_SESSION['theme']) && isset($_SESSION['theme']['modes']) && !in_array('dark', $_SESSION['theme']['modes'])) ? 'disabled' : null ?>
          />
          <label class="form-check-label" for="settings-system-mode"><i class="fas fa-laptop mr-1"></i></label>
        </div>
        <span class="small text-muted"><?= _('Uses the color mode of your system') ?></span>
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
    <div class="d-flex flex-wrap gap-2">
      <?php if (!RASPI_MONITOR_ENABLED) : ?>
      <input type="submit" class="btn btn-outline btn-primary" name="savethemeSettings" value="<?php echo _("Save settings"); ?>" />
      <?php endif; ?>
      <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></button>
    </div>
  </form>
</div>


