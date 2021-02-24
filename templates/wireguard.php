  <?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
      <input type="submit" class="btn btn-outline btn-primary" name="savewgsettings" value="<?php echo _("Save settings"); ?>">
      <?php if ($wg_state) : ?>
        <input type="submit" class="btn btn-warning" name="stopwg" value="<?php echo _("Stop WireGuard"); ?>">
      <?php else : ?>
        <input type="submit" class="btn btn-success" name="startwg" value="<?php echo _("Start WireGuard"); ?>">
      <?php endif ?>
    <?php endif ?>
  <?php $buttons = ob_get_clean(); ob_end_clean() ?>

  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <span class="ra-wireguard mr-2"></span><?php echo _("WireGuard"); ?>
            </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status">wg <?php echo _($serviceStatus) ?></span>
              </button>
            </div>
          </div><!-- /.row -->
        </div><!-- /.card-header -->
        <div class="card-body">
        <?php $status->showMessages(); ?>
          <form role="form" action="/wg_conf" enctype="multipart/form-data" method="POST">
            <?php echo CSRFTokenFieldTag() ?>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" id="settingstab" href="#wgsettings" data-toggle="tab"><?php echo _("Settings"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="peertab" href="#wgpeers" data-toggle="tab"><?php echo _("Peer"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="loggingtab" href="#wglogging" data-toggle="tab"><?php echo _("Logging"); ?></a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
              <?php echo renderTemplate("wg/general", $__template_data) ?>
              <?php echo renderTemplate("wg/peers", $__template_data) ?>
              <?php echo renderTemplate("wg/logging", $__template_data) ?>
            </div><!-- /.tab-content -->

          <?php echo $buttons ?>
          </form>
        </div><!-- /.card-body -->
        <div class="card-footer"><?php echo _("Information provided by wireguard"); ?></div>
      </div><!-- /.card -->
    </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->

