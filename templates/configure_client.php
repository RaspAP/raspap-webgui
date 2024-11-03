<div class="row" id="wifiClientContent">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row align-items-center">
          <div class="col">
            <i class="fas fa-wifi me-2"></i><?php echo _("WiFi client"); ?>
          </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon"><i class="fas fa-circle service-status-<?php echo $ifaceStatus ?>"></i></span>
                <span class="text service-status"><?php echo strtolower($clientInterface) .' '. _($ifaceStatus) ?></span>
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
            <button type="button" class="btn btn-info float-end js-reload-wifi-stations"><?php echo _("Rescan"); ?></button>
          </div>
        </div>
        <div class="row" id="wpaConf">
          <div class="col">
            <form method="POST" action="wpa_conf" name="wpa_conf_form">
              <?php echo CSRFTokenFieldTag() ?>
              <input type="hidden" name="client_settings" ?>
              <div class="js-wifi-stations loading-spinner"></div>
            </form>
          </div>
        </div>
      </div><!-- ./ card-body -->
      <div class="card-footer"><?php echo _("<strong>Note:</strong> WEP access points appear as 'Open'. RaspAP does not currently support connecting to WEP"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<!-- Modal -->
<div class="modal fade" id="configureClientModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-sync-alt me-2"></i><?php echo _("Configuring WiFi Client"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1"><?php echo _("Configuring Wifi Client Interface..."); ?></div>
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-outline btn-primary" data-bs-dismiss="modal"><?php echo _("Close"); ?></button>
      </div>
    </div>
  </div>
</div>

