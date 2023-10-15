  <?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
        <input type="submit" <?php echo $ctlState; ?> class="btn btn-outline btn-primary <?php echo $ctlState; ?>" name="SaveProviderConfig" value="Save settings" />
        <?php if ($serviceStatus[0] == 0) : ?>
        <input type="submit" <?php echo $ctlState; ?> class="btn btn-success <?php echo $ctlState; ?>" name="StartProviderVPN" value="Connect <?php echo $providerName; ?>" />
        <?php else : ?>
        <input type="submit" <?php echo $ctlState; ?> class="btn btn-warning <?php echo $ctlState; ?>" name="StopProviderVPN" value="Disconnect <?php echo $providerName; ?>" />
        <?php endif; ?>
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
                <li class="nav-item"><a class="nav-link active" id="clienttab" href="#providerclient" data-toggle="tab"><?php echo _("Provider settings"); ?></a></li>
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

<!-- modal confirm-logout-->
<div class="modal fade" id="provider-confirm-logout" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-sync mr-2"></i><?php echo sprintf(_("Logout %s"), $providerName); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" id="system-reboot-message"><?php echo sprintf(_("Logout now? This will disconnect the %s connection."), $providerName); ?></div>
      </div>
      <div class="modal-footer">
      <button type="button" data-message="<?php echo _("Close"); ?>" class="btn btn-outline-secondary" data-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" id="js-provider-logout" data-action="logout" class="btn btn-outline-danger btn-delete"><?php echo _("Logout"); ?></button>
      </div>
    </div>
  </div>
</div>

