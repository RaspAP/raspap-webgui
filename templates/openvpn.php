  <?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
        <input type="submit" class="btn btn-outline btn-primary" name="SaveOpenVPNSettings" value="Save settings" />
        <?php if ($openvpnstatus[0] == 0) {
            echo '<input type="submit" class="btn btn-success" name="StartOpenVPN" value="Start OpenVPN" />' , PHP_EOL;
          } else {
            echo '<input type="submit" class="btn btn-warning" name="StopOpenVPN" value="Stop OpenVPN" />' , PHP_EOL;
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
              <i class="fas fa-key fa-fw mr-2"></i><?php echo _("OpenVPN"); ?>
            </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status">openvpn <?php echo _($serviceStatus) ?></span>
              </button>
            </div>
          </div><!-- /.row -->
        </div><!-- /.card-header -->
        <div class="card-body">
        <?php $status->showMessages(); ?>
          <form role="form" action="openvpn_conf" enctype="multipart/form-data" method="POST">
            <?php echo CSRFTokenFieldTag() ?>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" id="clienttab" href="#openvpnclient" data-toggle="tab"><?php echo _("Client settings"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="configstab" href="#openvpnconfigs" data-toggle="tab"><?php echo _("Configurations"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="loggingtab" href="#openvpnlogging" data-toggle="tab"><?php echo _("Logging"); ?></a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
              <?php echo renderTemplate("openvpn/general", $__template_data) ?>
              <?php echo renderTemplate("openvpn/configs", $__template_data) ?>
              <?php echo renderTemplate("openvpn/logging", $__template_data) ?>
            </div><!-- /.tab-content -->

            <?php echo $buttons ?>
          </form>
        </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by openvpn"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<!-- modal confirm-delete-->
<div class="modal fade" id="ovpn-confirm-delete" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="far fa-trash-alt mr-2"></i><?php echo _("Delete OpenVPN client"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1"><?php echo _("Delete client configuration? This cannot be undone."); ?></div>
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" class="btn btn-outline-danger btn-delete"><?php echo _("Delete"); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- modal confirm-enable -->
<div class="modal fade" id="ovpn-confirm-activate" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="far fa-check-circle mr-2"></i><?php echo _("Activate OpenVPN client"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1"><?php echo _("Activate client configuration? This will restart the openvpn-client service."); ?></div>
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" class="btn btn-outline-success btn-activate"><?php echo _("Activate"); ?></button>
      </div>
    </div>
  </div>
</div>

