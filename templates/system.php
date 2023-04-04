<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-cube mr-2"></i><?php echo _("System"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="system_info" method="POST">
        <?php echo CSRFTokenFieldTag() ?>
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="nav-item"><a class="nav-link active" id="basictab" href="#basic" aria-controls="basic" role="tab" data-toggle="tab"><?php echo _("Basic"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="languagetab" href="#language" aria-controls="language" role="tab" data-toggle="tab"><?php echo _("Language"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="themetab" href="#theme" aria-controls="theme" role="tab" data-toggle="tab"><?php echo _("Theme"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="advancedtab" href="#advanced" aria-controls="advanced" role="tab" data-toggle="tab"><?php echo _("Advanced"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="resettab" href="#reset" aria-controls="reset" role="tab" data-toggle="tab"><?php echo _("Reset"); ?></a></li>
        </ul>
          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("system/basic", $__template_data) ?>
            <?php echo renderTemplate("system/language", $__template_data) ?>
            <?php echo renderTemplate("system/theme", $__template_data) ?>
            <?php echo renderTemplate("system/advanced", $__template_data) ?>
            <?php echo renderTemplate("system/reset", $__template_data) ?>
          </div><!-- /.tab-content -->
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<!-- modal confirm-reset-->
<div class="modal fade" id="system-confirm-reset" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-history mr-2"></i><?php echo _("System reset"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" data-message="<?php echo _("Reset complete. Restart the hotspot for the changes to take effect."); ?>" id="system-reset-message"><?php echo _("Reset RaspAP to its initial configuration? This action cannot be undone."); ?></div>
      </div>
      <div class="modal-footer">
      <button type="button" id="js-system-reset-cancel" data-message="<?php echo _("Close"); ?>" class="btn btn-outline-secondary" data-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" id="js-system-reset-confirm" data-message="<?php echo _("System reset in progress..."); ?>" class="btn btn-outline-danger btn-delete"><?php echo _("Reset"); ?></button>
      </div>
    </div>
  </div>
</div>

