<!-- advanced tab -->
<div role="tabpanel" class="tab-pane" id="advanced">
  <h4 class="mt-3"><?php echo _("Advanced settings") ;?></h4>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <form action="?page=system_info" method="POST">
    <?php echo CSRFTokenFieldTag() ?>
      <div class="row">
        <div class="form-group col-md-6">
          <label for="code"><?php echo _("Web server port") ;?></label>
          <input type="text" class="form-control" name="serverPort" value="<?php echo htmlspecialchars($serverPort, ENT_QUOTES); ?>" />
        </div>
      </div>
      <div class="row">
        <div class="form-group col-md-6">
          <label for="code"><?php echo _("Web server bind address") ;?></label>
          <input type="text" class="form-control" name="serverBind" value="<?php echo htmlspecialchars($serverBind, ENT_QUOTES); ?>" />
        </div>
      </div>
      <input type="submit" class="btn btn-outline btn-primary" name="SaveServerSettings" value="<?php echo _("Save settings"); ?>" />
      <input type="submit" class="btn btn-warning" name="RestartLighttpd" value="<?php echo _("Restart lighttpd"); ?>" />
    </form>
    <?php endif ?>
</div>


