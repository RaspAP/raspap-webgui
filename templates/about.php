<?php

require_once 'app/lib/Parsedown.php';

?>
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-info-circle mr-2"></i><?php echo _("About RaspAP"); ?>
          </div>
        </div><!-- ./row -->
      </div><!-- ./card-header -->
      <div class="card-body">

        <!-- Nav tabs -->
        <ul class="nav nav-tabs">
          <li class="nav-item"><a class="nav-link active" href="#aboutgeneral" data-toggle="tab"><?php echo _("About"); ?></a></li>
          <li class="nav-item"><a class="nav-link" href="#aboutsponsors" data-toggle="tab"><?php echo _("Insiders"); ?></a></li>
          <li class="nav-item"><a class="nav-link" href="#aboutcontrib" data-toggle="tab"><?php echo _("Contributing"); ?></a></li>
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
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-sync-alt fa-spin mr-2" id="updateSync"></i><?php echo _("Check for update"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" id="msg-check-update"><?php echo _("New release check in progress..."); ?></div>
      </div>
      <div class="modal-footer">
      <div id="msgUpdate" data-message="<?php echo _("A new release is available: Version"); ?>"></div>
      <div id="msgLatest" data-message="<?php echo _("Installed version is the latest release."); ?>"></div>
      <div id="msgInstall" data-message="<?php echo _("Install this update now?"); ?>"></div>
      <button type="button" data-message="<?php echo _("OK"); ?>" id="js-check-dismiss" class="btn btn-outline-secondary" data-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" data-message="<?php echo _("OK"); ?>" id="js-sys-check-update" class="btn btn-outline btn-primary collapse"><?php echo _("OK"); ?></button>
      </div>
    </div>
  </div>
</div>

<!-- modal auth-credentials-->
<div class="modal fade" id="authupdateModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="authUpdate" class="needs-validation" novalidate>
        <div class="modal-header">
          <div class="modal-title" id="ModalLabel"><i class="fab fa-github mr-2"></i><?php echo _("GitHub authentication"); ?></div>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12 mb-2 mt-1 ml-4" id="msg-github-auth"><?php echo _("Updating Insiders requires GitHub authentication."); ?></div>
          </div>
          <div class="row">
            <div class="col-xs mb-3 ml-3 mt-3">
              <i class="fas fa-exclamation-circle" style="color: #f6c23e; font-size: 1.35em;"></i>
            </div>
            <div class="col-md mb-2 mt-1 mr-3">
              <small><?php echo _("Your credentials will be sent to GitHub securely with SSL. However, use caution if your RaspAP install is on a WLAN shared by untrusted users."); ?></small>
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-10 mt-2 ml-5">
              <label for="username"><?php echo _("Username"); ?></label>
              <input type="text" class="form-control" id="ghUser" required />
              <div class="invalid-feedback"><?php echo _("Please provide a valid username."); ?></div>
            </div>
          </div>
          <div class="row">
            <div class="form-group col-md-10 ml-5 mb-4">
              <div class="mb-2"><?php echo _("Personal Access Token"); ?></div>
              <div class="input-group">
                <input type="password" class="form-control" id="ghToken" required />
                <div class="input-group-append">
                  <button class="btn btn-light js-toggle-password" type="button" data-target="[id=ghToken]" data-toggle-with="fas fa-eye-slash"><i class="fas fa-eye mx-2"></i></button>
                </div>
                <div class="invalid-feedback"><?php echo _("Please provide a valid token."); ?></div>
              </div>
            </div>
          </div>
        </div><!-- /.modal-body -->
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal"><?php echo _("Cancel"); ?></button>
          <button type="submit" class="btn btn-outline btn-primary"><?php echo _("Perform update"); ?></button>
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
      <div class="modal-title" id="ModalLabel"><i class="fas fa-sync-alt fa-spin mr-2" id="updateSync2"></i><?php echo _("Update in progress"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" id="msg-check-update"><?php echo _("Application is being updated..."); ?></div>
        <div class="ml-5"><i class="fas fa-check mr-2 invisible" id="updateStep1"></i><?php echo _("Configuring update"); ?></div>
        <div class="ml-5"><i class="fas fa-check mr-2 invisible" id="updateStep2"></i><?php echo _("Updating sources"); ?></div>
        <div class="ml-5"><i class="fas fa-check mr-2 invisible" id="updateStep3"></i><?php echo _("Installing package updates"); ?></div>
        <div class="ml-5"><i class="fas fa-check mr-2 invisible" id="updateStep4"></i><?php echo _("Downloading latest files"); ?></div>
        <div class="ml-5"><i class="fas fa-check mr-2 invisible" id="updateStep5"></i><?php echo _("Installing application"); ?></div>
        <div class="ml-5 mb-1"><i class="fas fa-check mr-2 invisible" id="updateStep6"></i><?php echo _("Update complete"); ?></div>
        <div class="ml-5 mb-3"><i class="fas fa-times mr-2 invisible" id="updateErr"></i></div>
        <div id="errorMsg" data-message="<?php echo _("An error occurred. Check the log at <code>/tmp/raspap_install.log</code>"); ?>"></div>
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-outline btn-primary" data-dismiss="modal" /><?php echo _("OK"); ?></button>
      </div>
    </div>
  </div>
</div>

