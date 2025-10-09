<?php ob_start() ?>
  <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <?php if ($state === "down") : ?>
      <input type="submit" class="btn btn-success mt-2" value="<?php echo _("Start").' '.$interface ?>" name="ifup_wlan0" />
    <?php else : ?>
      <input type="submit" class="btn btn-warning mt-2" value="<?php echo _("Stop").' '.$interface ?>"  name="ifdown_wlan0" />
    <?php endif ?>
  <?php endif ?>
  <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary mt-2"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
<?php $buttons = ob_get_clean(); ob_end_clean() ?>

<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-tachometer-alt fa-fw me-2"></i>
            <?php echo _("Dashboard"); ?>
          </div>
          <div class="col">
            <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
              <span class="icon"><i class="fas fa-circle hostapd-led service-status-<?php echo $state ?>"></i></span>
              <span class="text service-status"><?php echo strtolower($interface) .' '. _($state) ?></span>
            </button>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form action="wlan0_info" method="POST">
          <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>

          <!-- Nav tabs -->
          <ul class="nav nav-tabs">
            <li class="nav-item"><a class="nav-link active" id="statustab" href="#status" aria-controls="status" data-bs-toggle="tab"><?php echo _("Status"); ?></a></li>
            <li class="nav-item"><a class="nav-link" id="datatab" href="#data" data-bs-toggle="tab"><?php echo _("Data usage"); ?></a></li>
          </ul>

          <!-- Tab panes -->
          <div class="tab-content">
            <?php echo renderTemplate("dashboard/status", $__template_data) ?>
            <?php echo renderTemplate("dashboard/data", $__template_data) ?>
          </div><!-- /.tab-content -->

          <?php echo $buttons ?>
        </form>
      </div><!-- /.card-body -->

      <div class="card-footer"><?php echo _("Information provided by raspap.sysinfo"); ?></div>

    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->

