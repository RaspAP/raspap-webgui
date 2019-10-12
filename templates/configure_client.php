<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header"><i class="fas fa-wifi"></i> <?php echo _("Configure WiFi client"); ?></div>
      <!-- /.card-heading -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <h4><?php echo _("Client settings"); ?></h4>

        <div class="btn-group btn-block">
          <button type="button" style="padding:10px;float: right;display: block;position: relative;margin-top: -55px;" class="col-md-2 btn btn-info js-reload-wifi-stations"><?php echo _("Rescan"); ?></button>
        </div>
        <form method="POST" action="?page=wpa_conf" name="wpa_conf_form" class="row">
            <?php echo CSRFTokenFieldTag() ?>
          <input type="hidden" name="client_settings" ?>
          <div class="js-wifi-stations loading-spinner w-100"></div>
        </form>
      </div><!-- ./ card-body -->
      <div class="card-footer"><?php echo _("<strong>Note:</strong> WEP access points appear as 'Open'. RaspAP does not currently support connecting to WEP"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
