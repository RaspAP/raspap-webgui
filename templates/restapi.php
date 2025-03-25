  <?php ob_start() ?>
    <?php if (!RASPI_MONITOR_ENABLED) : ?>
    <input type="submit" class="btn btn-outline btn-primary" name="SaveAPIsettings" value="<?php echo _("Save settings"); ?>" />
        <?php if ($serviceStatus == 'down') : ?>
        <input type="submit" class="btn btn-success" name="StartRestAPIservice" value="<?php echo _("Start RestAPI service"); ?>" />
        <?php else : ?>
        <input type="submit" class="btn btn-warning" name="StopRestAPIservice" value="<?php echo _("Stop RestAPI service"); ?>" />
        <?php endif; ?>
    <?php endif ?>
  <?php $buttons = ob_get_clean(); ob_end_clean() ?>
 
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <i class="fas fa-puzzle-piece me-2"></i><?php echo _("RestAPI"); ?>
            </div>
            <div class="col">
              <button class="btn btn-light btn-icon-split btn-sm service-status float-end">
                <span class="icon text-gray-600"><i class="fas fa-circle service-status-<?php echo $serviceStatus ?>"></i></span>
                <span class="text service-status">restapi.service <?php echo _($serviceStatus) ?></span>
              </button>
            </div>
          </div><!-- /.row -->
        </div><!-- /.card-header -->
        <div class="card-body">
        <?php $status->showMessages(); ?>
          <form role="form" action="restapi_conf" method="POST" class="needs-validation" novalidate>
            <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
            <!-- Nav tabs -->
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" id="restapisettingstab" href="#restapisettings" data-bs-toggle="tab"><?php echo _("Settings"); ?></a></li>
                <li class="nav-item"><a class="nav-link" id="restapistatustab" href="#restapistatus" data-bs-toggle="tab"><?php echo _("Status"); ?></a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
              <?php echo renderTemplate("restapi/general", $__template_data) ?>
              <?php echo renderTemplate("restapi/status", $__template_data) ?>
            </div><!-- /.tab-content -->

            <?php echo $buttons ?>
          </form>
        </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by restapi.service"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->


