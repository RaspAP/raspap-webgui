<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-wifi mr-2"></i><?php echo _("Configure WiFi client"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <div class="row">
          <div class="col">
            <h4 class="mb-2"><?php echo _("Client settings"); ?></h4>
          </div>
          <div class="col-xs mr-3 mb-3">
            <button type="button" class="btn btn-info btn-block float-right js-reload-wifi-stations"><?php echo _("Rescan"); ?></button>
          </div>
        </div>
        <form method="POST" action="?page=wpa_conf" name="wpa_conf_form" class="row">
            <?php echo CSRFTokenFieldTag() ?>
          <input type="hidden" name="client_settings" ?>
          <div class="js-wifi-stations loading-spinner"></div>
        </form>
      </div><!-- ./ card-body -->
      <div class="card-footer"><?php echo _("<strong>Note:</strong> WEP access points appear as 'Open'. RaspAP does not currently support connecting to WEP"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
