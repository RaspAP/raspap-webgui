<!-- advanced tab -->
<div role="tabpanel" class="tab-pane fade" id="advanced">
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
      <div class="mb-3 col-md-6">
        <h5><?php echo _('SSL Certificate (HTTPS)') ?></h5>
        <?php if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/rootCA.pem')): ?>
          <p><?php echo sprintf(_('An SSL Certificate has been created for this system. To download and install it on your system, use the button below and the <a href="%s" target="_blank">documentation</a> to install the certificate on your device.'), 'https://docs.raspap.com/features-core/ssl/#client-configuration') ?></p>
          <a href="/rootCA.pem" target="_blank" class="btn btn-primary"><?php echo _('Download rootCA.pem') ?></a>
        <?php else: ?>
          <p><?php echo sprintf(_('To install an SSL Certificate and access the RaspAP Admin Panel via HTTPS, please refer to the <a href="%s" target="_blank">documentation</a>.'), 'https://docs.raspap.com/features-core/ssl/') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
      <input type="submit" class="btn btn-outline-primary" name="SaveServerSettings" value="<?php echo _("Save settings"); ?>" />
      <input type="submit" class="btn btn-warning" name="RestartLighttpd" value="<?php echo _("Restart lighttpd"); ?>" />
    </div>
  </form>
  <?php endif ?>
</div>

