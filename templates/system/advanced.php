<!-- advanced tab -->
  <div role="tabpanel" class="tab-pane" id="advanced">
    <h4 class="mt-3"><?php echo _("Advanced settings") ;?></h4>
    <div class="row">
      <div class="form-group col-md-6">
        <label for="code"><?php echo _("Web server port") ;?></label>
        <form action="?page=system_info" method="POST">
          <?php echo CSRFTokenFieldTag() ?>
          <?php if (!RASPI_MONITOR_ENABLED) : ?>
            <input type="text" class="form-control" name="serverPort" value="<?php echo htmlspecialchars($ServerPort, ENT_QUOTES); ?>" />
          <?php endif ?>
      </div>
    </div>
    <input type="submit" class="btn btn-outline btn-primary" name="SaveServerPort" value="<?php echo _("Save settings"); ?>" />
    <input type="submit" class="btn btn-warning" name="RestartLighttpd" value="<?php echo _("Restart lighttpd"); ?>" />
  </div>


