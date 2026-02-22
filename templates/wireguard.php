<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-outline btn-primary" name="savewgsettings" value="<?php echo _("Save settings"); ?>">
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row">
  <div class="col-lg-12">
    <div class="card">

      <div class="card-header page-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="ra-wireguard me-2"></span><?php echo _("WireGuard"); ?>
          </div>
          <form method="POST" action="wg_conf">
            <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
            <div class="btn-group" role="group">
              <?php if (!RASPI_MONITOR_ENABLED) : ?>
                <?php if ($wg_state) : ?>
                  <button type="submit" class="btn btn-sm btn-danger" title="<?php echo _("Stop WireGuard"); ?>" name="stopwg" >
                    <i class="fas fa-stop"></i>
                  </button>
                <?php else : ?>
                  <button type="submit" class="btn btn-sm btn-light" title="<?php echo _("Start WireGuard"); ?>" name="startwg" >
                    <i class="fas fa-play"></i>
                  </button>
                <?php endif; ?>
              <?php endif ?>
              <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status">wg <?php echo _($serviceStatus) ?></span>
              </button>
            </div>
          </form>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="wg_conf" enctype="multipart/form-data" method="POST">
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
          <!-- Nav tabs -->
          <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" id="settingstab" href="#wgsettings" data-bs-toggle="tab"><?php echo _("Settings"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="peertab" href="#wgpeers" data-bs-toggle="tab"><?php echo _("Peer"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="loggingtab" href="#wglogging" data-bs-toggle="tab"><?php echo _("Logging"); ?></a></li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("wg/general", $__template_data) ?>
            <?php echo renderTemplate("wg/peers", $__template_data) ?>
            <?php echo renderTemplate("wg/logging", $__template_data) ?>
          </div><!-- /.tab-content -->

            <div class="d-flex flex-wrap gap-2">
              <?php echo $buttons ?>
            </div>
          </form>
        </div><!-- /.card-body -->
        <div class="card-footer"><?php echo _("Information provided by wireguard"); ?></div>
      </div><!-- /.card -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->

