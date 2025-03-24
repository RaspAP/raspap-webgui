<?php

require_once 'app/lib/Parsedown.php';

?>
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-info-circle me-2"></i><?php echo _("About RaspAP"); ?>
          </div>
        </div><!-- ./row -->
      </div><!-- ./card-header -->
      <div class="card-body">

        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
          <li class="nav-item"><a class="nav-link active" href="#aboutgeneral" data-bs-toggle="tab"><?php echo _("About"); ?></a></li>
          <li class="nav-item"><a class="nav-link" href="#aboutsponsors" data-bs-toggle="tab"><?php echo _("Insiders"); ?></a></li>
          <li class="nav-item"><a class="nav-link" href="#aboutcontrib" data-bs-toggle="tab"><?php echo _("Contributing"); ?></a></li>
        </ul>
        <!-- /.nav-tabs -->

        <!-- Tab panes -->
        <div class="tab-content">
          <?php echo renderTemplate("about/general", $__template_data) ?>
          <?php echo renderTemplate("about/insiders", $__template_data) ?>
          <?php echo renderTemplate("about/contributing", $__template_data) ?>
        </div>
        <!-- /.tab-content -->

      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

<!-- modal check-update-->
<div class="modal fade" id="chkupdateModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="performUpdate" class="needs-validation" novalidate>
        <div class="modal-header">
          <div class="modal-title" id="ModalLabel"><i class="fas fa-sync-alt fa-spin me-2" id="updateSync"></i><?php echo _("Check for update"); ?></div>
        </div>
        <div class="modal-body">
          <div class="col-md-12 mb-3 mt-1" id="msg-check-update"><?php echo _("New release check in progress..."); ?></div>
        </div>
        <div class="modal-footer">
          <div id="msgUpdate" data-message="<?php echo _("A new release is available: Version"); ?>"></div>
          <div id="msgLatest" data-message="<?php echo _("Installed version is the latest release."); ?>"></div>
          <div id="msgInstall" data-message="<?php echo _("Install this update now?"); ?>"></div>
          <button type="button" data-message="<?php echo _("OK"); ?>" id="js-check-dismiss" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo _("Cancel"); ?></button>
          <button type="submit" id="js-sys-check-update" class="btn btn-outline btn-primary collapse"><?php echo _("OK"); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- modal update-cmd -->
<div class="modal fade" id="performupdateModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-sync-alt fa-spin me-2" id="updateSync2"></i><?php echo _("Update in progress"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" id="msg-check-update"><?php echo _("Application is being updated..."); ?></div>
        <div class="ms-5"><i class="fas fa-check me-2" id="updateStep1"></i><?php echo _("Configuring update"); ?></div>
        <div class="ms-5"><i class="fas fa-check me-2 invisible" id="updateStep2"></i><?php echo _("Updating sources"); ?></div>
        <div class="ms-5"><i class="fas fa-check me-2 invisible" id="updateStep3"></i><?php echo _("Installing package updates"); ?></div>
        <div class="ms-5"><i class="fas fa-check me-2 invisible" id="updateStep4"></i><?php echo _("Downloading latest files"); ?></div>
        <div class="ms-5"><i class="fas fa-check me-2 invisible" id="updateStep5"></i><?php echo _("Installing application"); ?></div>
        <div class="ms-5 mb-1"><i class="fas fa-check me-2 invisible" id="updateStep6"></i><?php echo _("Update complete"); ?></div>
        <div class="ms-5 mb-3"><i class="fas me-2 invisible" id="updateMsg"></i></div>
        <div id="errorMsg" data-message="<?php echo _("An error occurred. Check the log at <code>/tmp/raspap_install.log</code>"); ?>"></div>
        <div id="successMsg" data-message="<?php echo _("Success. Refresh this page to confirm the new version."); ?>"></div>
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-outline btn-primary" data-bs-dismiss="modal" disabled id="updateOk" /><?php echo _("OK"); ?></button>
      </div>
    </div>
  </div>
</div>

