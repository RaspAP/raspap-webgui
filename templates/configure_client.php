<div class="row">
  <div class="col-lg-12">
    <div class="panel panel-primary">
      <div class="panel-heading"><i class="fa fa-signal fa-fw"></i> <?php echo _("Configure client"); ?></div>
      <!-- /.panel-heading -->
      <div class="panel-body">
        <?php $status->showMessages(); ?>
        <h4><?php echo _("Client settings"); ?></h4>

        <div class="btn-group btn-block">
          <button type="button" style="padding:10px;float: right;display: block;position: relative;margin-top: -55px;" class="col-md-2 btn btn-info js-reload-wifi-stations"><?php echo _("Rescan"); ?></button>
        </div>

        <form method="POST" action="?page=wpa_conf" name="wpa_conf_form" class="row">
            <?php echo CSRFTokenFieldTag() ?>
          <input type="hidden" name="client_settings" ?>
          <div class="js-wifi-stations loading-spinner"></div>
        </form>
      </div><!-- ./ Panel body -->
      <div class="panel-footer"><?php echo _("<strong>Note:</strong> WEP access points appear as 'Open'. RaspAP does not currently support connecting to WEP"); ?></div>
    </div><!-- /.panel-primary -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
