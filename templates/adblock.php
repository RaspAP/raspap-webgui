<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-outline btn-primary" name="saveadblocksettings" value="<?php echo _("Save settings"); ?>">
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row">
  <div class="col-lg-12">
    <div class="card">
      
      <div class="card-header page-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <i class="far fa-hand-paper me-2"></i><?php echo _("Ad Blocking"); ?>
          </div>
          <form method="POST" action="adblock_conf">
            <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
            <div class="btn-group" role="group">
              <?php if (!RASPI_MONITOR_ENABLED) : ?>
                <?php if ($dnsmasq_state) : ?>
                  <button type="submit" class="btn btn-sm btn-warning" title="<?php echo _("Restart Ad Blocking"); ?>" name="restartadblock" >
                    <i class="fas fa-sync-alt"></i>
                  </button>
                <?php else : ?>
                  <button type="submit" class="btn btn-sm btn-light" title="<?php echo _("Start Ad Blocking"); ?>" name="startadblock" >
                    <i class="fas fa-play"></i>
                  </button>
                <?php endif ?>
              <?php endif ?>
              <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status">adblock <?php echo _($serviceStatus) ?></span>
              </button>
            </div>
          </form>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="adblock_conf" enctype="multipart/form-data" method="POST">
          <?php echo \RaspAP\Tokens\CSRF::hiddenField();?>
          <!-- Nav tabs -->
          <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" id="blocklisttab" href="#adblocklistsettings" data-bs-toggle="tab"><?php echo _("Blocklist settings"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="customtab" href="#adblockcustom" data-bs-toggle="tab"><?php echo _("Custom blocklist"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="logoutputtab" href="#adblocklogfileoutput" data-bs-toggle="tab"><?php echo _("Logging"); ?></a></li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("adblock/general", $__template_data) ?>
            <?php echo renderTemplate("adblock/stats", $__template_data) ?>
            <?php echo renderTemplate("adblock/custom", $__template_data) ?>
            <?php echo renderTemplate("adblock/logging", $__template_data) ?>
          </div><!-- /.tab-content -->

          <div class="d-flex flex-wrap gap-2">
            <?php echo $buttons ?>
          </div>
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by adblock"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

