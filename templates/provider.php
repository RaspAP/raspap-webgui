  <?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
        <input type="submit" class="btn btn-outline btn-primary" name="SaveProviderConfig" value="Save settings" />
        <?php if ($openvpnstatus[0] == 0) {
            echo '<input type="submit" class="btn btn-success" name="StartProviderVPN" value="Connect '.$providerName.'" />' , PHP_EOL;
          } else {
            echo '<input type="submit" class="btn btn-warning" name="StopProviderVPN" value="Disconnect '.$providerName.'" />' , PHP_EOL;
          }
        ?>
    <?php endif ?>
  <?php $buttons = ob_get_clean(); ob_end_clean() ?>
 
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <i class="fas fa-shield-alt fa-fw mr-2"></i><?php echo _($providerName); ?>
            </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status"><?php echo strtolower($providerName); ?> <?php echo _($serviceStatus) ?></span>
              </button>
            </div>
          </div><!-- /.row -->
        </div><!-- /.card-header -->
        <div class="card-body">
        <?php $status->showMessages(); ?>
          <form role="form" action="provider_conf" enctype="multipart/form-data" method="POST">
            <?php echo CSRFTokenFieldTag() ?>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" id="clienttab" href="#providerclient" data-toggle="tab"><?php echo _("Client settings"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="loggingtab" href="#providerstatus" data-toggle="tab"><?php echo _("Status"); ?></a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
              <?php echo renderTemplate("provider/general", $__template_data) ?>
              <?php echo renderTemplate("provider/status", $__template_data) ?>
            </div><!-- /.tab-content -->

            <?php echo $buttons ?>
          </form>
        </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by " .strtolower($providerName)); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->


