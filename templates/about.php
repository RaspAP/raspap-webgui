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
      <div class="modal-title" id="ModalLabel"><i class="fas fa-sync-alt fa-spin mr-2"></i><?php echo _("Check for update"); ?></div>
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

<!-- modal update-cmd -->
<div class="modal fade" id="cmdupdateModal" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
      <div class="modal-title" id="ModalLabel"><i class="fas fa-terminal mr-2"></i><?php echo _("Update command"); ?></div>
      </div>
      <div class="modal-body">
        <div class="col-md-12 mb-3 mt-1" id="msg-check-update"><?php echo _("Copy the following and execute it in the terminal:"); ?></div>
        <textarea class="logoutput ml-2 cmd-copy" id="shellCmd"></textarea>
      </div>
      <div class="modal-footer">
      <button type="button" data-message="<?php echo _("Done"); ?>" class="btn btn-outline-secondary" id="cmdupdateCancel" data-dismiss="modal"><?php echo _("Cancel"); ?></button>
      <button type="button" class="btn btn-warning" id="js-cmd-copy" /><i class="far fa-copy ml-1 mr-2"></i><?php echo _("Copy"); ?></button>
      </div>
    </div>
  </div>
</div>
