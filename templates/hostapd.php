<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-outline-primary" id="btnSaveHostapd" name="SaveHostAPDSettings" value="<?php echo _("Save settings"); ?>" />
  <?php endif ?>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row">
  <div class="col-lg-12">
    <div class="card shadow">

      <div class="card-header page-card-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <i class="fas fa-bullseye me-2"></i><?php echo _("Hotspot"); ?>
          </div>
          <div>
            <form method="POST" action="/ajax/page/hostapd.php" class="live-form" data-modal-title="<?php echo _("Hotspot Service Control") ?>">
              <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
              <div class="btn-group" role="group">
                <?php if (!RASPI_MONITOR_ENABLED) : ?>
                  <?php if ($hostapdstatus[0] == 0) : ?>
                    <button type="submit" class="btn btn-sm btn-light" title="<?php echo  _("Start hotspot"); ?>" name="StartHotspot">
                      <i class="fas fa-play"></i>
                    </button>
                  <?php else : ?>
                    <button type="submit" class="btn btn-sm btn-danger" title="<?php echo _("Stop hotspot") ?>" name="StopHotspot" >
                      <i class="fas fa-stop"></i>
                    </button>
                    <button type="submit" class="btn btn-sm btn-warning" title="<?php echo _("Restart hotspot"); ?>" name="RestartHotspot">
                      <i class="fas fa-sync-alt"></i>
                    </button>
                  <?php endif ?>
                <?php endif ?>
                <button type="button" class="btn btn-light btn-icon-split btn-sm service-status float-end">
                  <span class="icon text-gray-600"><i class="fas fa-circle hostapd-led service-status-<?php echo $serviceStatus ?>"></i></span>
                  <span class="text service-status">hostapd <?php echo _($serviceStatus) ?></span>
                </button>
              </div>
            </form>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" method="POST" action="/ajax/page/hostapd.php" class="needs-validation live-form" novalidate data-modal-title="<?php echo _("Hotspot Settings") ?>">
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>

          <!-- Nav tabs -->
          <div class="nav-tabs-wrapper">
            <ul class="nav nav-tabs">
              <li class="nav-item"><a class="nav-link active" id="basictab" href="#basic" aria-controls="basic" data-bs-toggle="tab"><?php echo _("Basic"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="securitytab" href="#security" data-bs-toggle="tab"><?php echo _("Security"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="advancedtab" href="#advanced" data-bs-toggle="tab"><?php echo _("Advanced"); ?></a></li>
              <li class="nav-item"><a class="nav-link" id="logoutputtab" href="#logoutput" data-bs-toggle="tab"><?php echo _("Logging"); ?></a></li>
            </ul>
          </div>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("hostapd/basic", $__template_data) ?>
            <?php echo renderTemplate("hostapd/security", $__template_data) ?>
            <?php echo renderTemplate("hostapd/advanced", $__template_data) ?>
            <?php echo renderTemplate("hostapd/logging", $__template_data) ?>
          </div><!-- /.tab-content -->

          <div class="d-flex flex-wrap gap-2">
            <?php echo $buttons ?>
          </div>
        </form>
      </div><!-- /.card-body -->

      <div class="card-footer"><?php echo _("Information provided by hostapd"); ?></div>

    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->