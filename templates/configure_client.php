<div class="row" id="wifiClientContent">
  <div class="col-lg-12">
    <div class="card shadow">
      <div class="card-header page-card-header">
        <div class="row align-items-center">
          <div class="col">
            <i class="fas fa-wifi me-2"></i><?php echo _("WiFi client"); ?>
          </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon"><i class="fas fa-circle service-status-<?php echo $hasConnection ? 'up' : 'down' ?>"></i></span>
                <span class="text service-status"><?php echo $hasConnection ? _('Connected') : _('Disconnected') ?></span>
              </button>
            </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <div class="row align-items-center">
          <div class="col">
            <h4 class="m-0 text-nowrap"><?php echo _("Client settings"); ?></h4>
          </div>
          <div class="col">
            <button type="button" class="btn btn-primary float-end js-reload-wifi-stations"><i class="fa-solid fa-magnifying-glass"></i> <?php echo _("Rescan"); ?></button>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <form method="POST" action="/ajax/page/client.php" class="live-form" data-modal-title="<?= _("WiFi Client Interface") ?>">
              <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
              <label for="cbxclientiface"><?php echo _("Interface"); ?></label>
              <div class="input-group">
                <?php SelectorOptions('wifiClientInterface', $interfaces, $initial_iface, 'cbxclientiface'); ?>
                <button type="submit" class="btn btn-primary"><?php echo _("Set"); ?></button>
              </div>
            </form>
          </div>
        </div>
        <form method="POST" action="/ajax/page/client.php" name="wpa_conf_form" class="live-form" data-modal-title="<?= _("Configuring WiFi Client"); ?>">
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
          <div class="row" id="wpaConf">
            <div class="col position-relative">
              <input type="hidden" name="client_settings" />
              <div class="js-wifi-stations loading-spinner"></div>
            </div>
          </div>
        </form>
      </div><!-- ./ card-body -->
      <div class="card-footer"><?php echo _("<strong>Note:</strong> WEP access points appear as 'Open'. RaspAP does not currently support connecting to WEP"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
