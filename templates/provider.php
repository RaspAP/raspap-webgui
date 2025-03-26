  <?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" <?php echo $ctlState; ?> class="btn btn-outline btn-primary <?php echo $ctlState; ?>" name="SaveProviderSettings" value="<?php echo _("Save settings"); ?>" />
        <?php if ($serviceStatus == 'down') : ?>
        <input type="submit" <?php echo $ctlState; ?> class="btn btn-success <?php echo $ctlState; ?>" name="StartProviderVPN" value="<?php echo sprintf(_("Connect %s"), $providerName); ?>" />
        <?php else : ?>
        <input type="submit" <?php echo $ctlState; ?> class="btn btn-warning <?php echo $ctlState; ?>" name="StopProviderVPN" value="<?php echo sprintf(_("Disconnect %s"), $providerName); ?>" />
        <?php endif; ?>
    <?php endif ?>
  <?php $buttons = ob_get_clean(); ob_end_clean() ?>
 
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <i class="fas fa-shield-alt fa-fw me-2"></i><?php echo _($providerName); ?>
            </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status"><?php echo strtolower($providerName); ?> <?php echo _($statusDisplay) ?></span>
              </button>
            </div>
          </div><!-- /.row -->
        </div><!-- /.card-header -->
        <div class="card-body">
        <?php $status->showMessages(); ?>
          <form role="form" action="provider_conf" enctype="multipart/form-data" method="POST">
            <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" id="clienttab" href="#providerclient" data-bs-toggle="tab"><?php echo _("Settings"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="loggingtab" href="#providerstatus" data-bs-toggle="tab"><?php echo _("Status"); ?></a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
              <?php echo renderTemplate("provider/general", $__template_data) ?>
              <?php echo renderTemplate("provider/status", $__template_data) ?>
            </div><!-- /.tab-content -->

            <?php echo $buttons ?>
          </form>
        </div><!-- /.card-body -->
      <div class="card-footer"><?php echo sprintf( _("Information provided by %s"), strtolower($providerName)); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

