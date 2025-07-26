<!-- advanced tab -->
<div role="tabpanel" class="tab-pane" id="advanced">
  <h4 class="mt-3"><?php echo _("Advanced settings") ;?></h4>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <form action="system_info" method="POST">
    <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="code"><?php echo _("Web server port") ;?></label>
          <input type="text" class="form-control" name="serverPort" value="<?php echo htmlspecialchars($serverPort, ENT_QUOTES); ?>" />
        </div>
      </div>
      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="code"><?php echo _("Web server bind address") ;?></label>
          <input type="text" class="form-control" name="serverBind" value="<?php echo htmlspecialchars($serverBind, ENT_QUOTES); ?>" />
        </div>
      </div>
      <div class="row">
        <div class="mb-3 col-md-6">
          <label for="code"><?php echo _("Diagnostic log size limit (KB)") ;?></label>
          <input type="text" class="form-control" name="logLimit" value="<?php echo htmlspecialchars($logLimit, ENT_QUOTES); ?>" />
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-2">
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

      <input type="submit" class="btn btn-outline btn-primary" name="SaveServerSettings" value="<?php echo _("Save settings"); ?>" />
      <input type="submit" class="btn btn-warning" name="RestartLighttpd" value="<?php echo _("Restart lighttpd"); ?>" />
    </form>
    <?php endif ?>
</div>

