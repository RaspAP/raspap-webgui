<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <?php if ($__template_data['btnRefresh']) : ?>
      <button type="button" onClick="window.location.reload();" class="btn btn-outline-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a></button>
    <?php endif; ?>
    <?php if ($__template_data['btnBack']) : ?>
      <button type="submit" class="btn btn-outline-primary" name="revertSave"><i class="fa-solid fa-chevron-left mx-1"></i><?php echo _("Back") ?></a></button>
    <?php endif; ?>
    <?php if ($__template_data['btnNext']) : ?>
      <button type="button" onClick="window.location.reload();" class="btn btn-outline-primary"><?php echo _("Next") ?> <i class="fa-solid fa-chevron-right"></i></a></button>
    <?php endif; ?>
    <?php if ($__template_data['btnNextSave']) : ?>
      <button type="submit" class="btn btn-outline-primary" name="nextSave"><?php echo _("Next") ?> <i class="fa-solid fa-chevron-right"></i></a></button>
    <?php endif; ?>
    <?php if ($__template_data['btnSave']) : ?>
      <input type="submit" class="btn btn-outline-primary" name="saveSettings" value="<?php echo _("Save settings"); ?>" />
    <?php endif; ?>
    <?php if ($__template_data['btnRevokeNode']) : ?>
      <input type="submit" class="btn btn-warning" name="revokeNode" value="<?php echo _("Revoke Exit Node"); ?>" />
    <?php endif; ?>
    <?php if ($__template_data['btnLogout']) : ?>
      <input type="submit" class="btn btn-warning" name="logout" value="<?php echo _("Logout"); ?>" />
    <?php endif; ?>
    <?php if ($__template_data['btnStopUsing']) : ?>
      <input type="submit" class="btn btn-warning" name="stopUsingNode" value="<?php echo _("Stop Using Exit Node"); ?>" />
    <?php endif; ?>
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>
 
<div class="row">
  <div class="col-lg-12">
    <div class="card shadow">
      <div class="card-header page-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <i class="<?php echo $__template_data['icon']; ?> me-2"></i><?php echo htmlspecialchars($__template_data['title']); ?>
          </div>
          <form role="form" action="<?php echo $__template_data['action']; ?>" method="POST">
            <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
            <div class="btn-group" role="group">
              <?php if (!RASPI_MONITOR_ENABLED) : ?>
                <?php if ($__template_data['btnService']) : ?>
                  <?php if ($__template_data['serviceStatus'] == 'down') : ?>
                    <button type="submit" class="btn btn-sm btn-light" title="<?php echo _("Start Tailscale") ?>" name="startTailscale">
                      <i class="fas fa-play"></i>
                    </button>
                  <?php else : ?>
                    <button type="submit" class="btn btn-sm btn-danger" title="<?php echo _("Stop Tailscale") ?>" name="stopTailscale">
                      <i class="fas fa-stop"></i>
                    </button>
                  <?php endif; ?>
                <?php endif; ?>
              <?php endif ?>
              <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $__template_data['serviceStatus']; ?>"></i></span>
                <span class="text service-status"><?php echo $__template_data['serviceName'] . ' ' .$__template_data['serviceStatus']; ?></span>
              </button>
            </div>
          </form>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="<?php echo $__template_data['action']; ?>" method="POST" class="needs-validation" novalidate>
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
          <!-- Nav tabs -->
          <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs">
              <li class="nav-item"><a class="nav-link active" id="settingstab" href="#settings" data-bs-toggle="tab"><?php echo _("Settings"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="statustab" href="#status" data-bs-toggle="tab"><?php echo _("Status"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="abouttab" href="#about" data-bs-toggle="tab"><?php echo _("About"); ?></a></li>
            </ul>
          </div>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("tabs/basic", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/status", $__template_data, $__template_data['pluginName']) ?>
            <?php echo renderTemplate("tabs/about", $__template_data, $__template_data['pluginName']) ?>
          </div><!-- /.tab-content -->

          <div class="d-flex flex-wrap gap-2">
            <?php echo $buttons ?>
          </div>
        </form>
      </div><!-- /.card-body -->

      <div class="card-footer"><?php echo _("Information provided by ". $__template_data['serviceName']); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

