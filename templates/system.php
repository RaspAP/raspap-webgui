<?php

include('includes/sysstats.php');

?>
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-cube mr-2"></i><?php echo _("System"); ?>
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <?php $status->showMessages(); ?>
        <form role="form" action="?page=system_info" method="POST">
        <?php echo CSRFTokenFieldTag() ?>
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="nav-item"><a class="nav-link active" id="systemtab" href="#system" aria-controls="system" role="tab" data-toggle="tab"><?php echo _("System"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="languagetab" href="#language" aria-controls="language" role="tab" data-toggle="tab"><?php echo _("Language"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="advancedtab" href="#advanced" aria-controls="advanced" role="tab" data-toggle="tab"><?php echo _("Advanced"); ?></a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="consoletab" href="#console" aria-controls="console" role="tab" data-toggle="tab"><?php echo _("Console"); ?></a></li>
        </ul>

        <div class="systemtabcontent tab-content">
          <div role="tabpanel" class="tab-pane active" id="system">
            <div class="row">
              <div class="col-lg-6">
                <h4 class="mt-3"><?php echo _("System Information"); ?></h4>
                <div class="info-item"><?php echo _("Hostname"); ?></div><div><?php echo htmlspecialchars($hostname, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("Pi Revision"); ?></div><div><?php echo htmlspecialchars(RPiVersion(), ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("Uptime"); ?></div><div><?php echo htmlspecialchars($uptime, ENT_QUOTES); ?></div>
                <div class="mb-1"><?php echo _("Memory Used"); ?></div>
                <div class="progress mb-2" style="height: 20px;">
                  <div class="progress-bar bg-<?php echo htmlspecialchars($memused_status, ENT_QUOTES); ?>"
                                              role="progressbar" aria-valuenow="<?php echo htmlspecialchars($memused, ENT_QUOTES); ?>" aria-valuemin="0" aria-valuemax="100"
                                              style="width: <?php echo htmlspecialchars($memused, ENT_QUOTES); ?>%"><?php echo htmlspecialchars($memused, ENT_QUOTES); ?>%
                  </div>
                </div>
                <div class="mb-1"><?php echo _("CPU Load"); ?></div>
                <div class="progress mb-2" style="height: 20px;">
                  <div class="progress-bar bg-<?php echo htmlspecialchars($cpuload_status, ENT_QUOTES); ?>"
                                              role="progressbar" aria-valuenow="<?php echo htmlspecialchars($cpuload, ENT_QUOTES); ?>" aria-valuemin="0" aria-valuemax="100"
                                              style="width: <?php echo htmlspecialchars($cpuload, ENT_QUOTES); ?>%"><?php echo htmlspecialchars($cpuload, ENT_QUOTES); ?>%
                  </div>
                </div>
                <div class="mb-1"><?php echo _("CPU Temp"); ?></div>
                <div class="progress mb-4" style="height: 20px;">
                  <div class="progress-bar bg-<?php echo htmlspecialchars($cputemp_status, ENT_QUOTES); ?>"
                                              role="progressbar" aria-valuenow="<?php echo htmlspecialchars($cputemp, ENT_QUOTES); ?>" aria-valuemin="0" aria-valuemax="100"
                                              style="width: <?php echo htmlspecialchars(($cputemp*1.2), ENT_QUOTES); ?>%"><?php echo htmlspecialchars($cputemp, ENT_QUOTES); ?>°C
                  </div>
                </div>

                <form action="?page=system_info" method="POST">
                  <?php echo CSRFTokenFieldTag() ?>
                      <a href="?page=<?php echo $_GET['page'] ?>" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
 
                  <?php if (!RASPI_MONITOR_ENABLED) : ?>
                      <input type="submit" class="btn btn-warning" name="system_reboot"   value="<?php echo _("Reboot"); ?>" />
                      <input type="submit" class="btn btn-warning" name="system_shutdown" value="<?php echo _("Shutdown"); ?>" />
                  <?php endif ?>
               </form>
                </div>
              </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="language">
              <h4 class="mt-3"><?php echo _("Language settings") ;?></h4>
              <div class="row">
                <div class="form-group col-md-6">
                  <label for="code"><?php echo _("Select a language"); ?></label>
                  <?php SelectorOptions('locale', $arrLocales, $_SESSION['locale']); ?>
                </div>
              </div>
              <input type="submit" class="btn btn-outline btn-primary" name="SaveLanguage" value="<?php echo _("Save settings"); ?>" />
              <a href="?page=<?php echo $_GET['page'] ?>" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
            </div>

            <div role="tabpanel" class="tab-pane" id="advanced">
              <h4 class="mt-3"><?php echo _("Advanced settings") ;?></h4>
              <div class="row">
                <div class="form-group col-md-6">
                  <label for="code"><?php echo _("Web server port") ;?></label>
                  <form action="?page=system_info" method="POST">
                    <?php echo CSRFTokenFieldTag() ?>
                    <?php if (!RASPI_MONITOR_ENABLED) : ?>
                      <input type="text" class="form-control" name="serverPort" value="<?php echo htmlspecialchars($ServerPort, ENT_QUOTES); ?>" />
                    <?php endif ?>
                </div>
              </div>
              <input type="submit" class="btn btn-outline btn-primary" name="SaveServerPort" value="<?php echo _("Save settings"); ?>" />
              <input type="submit" class="btn btn-warning" name="RestartLighttpd" value="<?php echo _("Restart lighttpd"); ?>" />

            </div>

            <div role="tabpanel" class="tab-pane" id="console">
              <div class="row">
                <div class="col-lg-12 mt-3">
                <?php if (!RASPI_MONITOR_ENABLED) : ?>
                  <iframe src="includes/webconsole.php" class="webconsole"></iframe>
                <?php endif ?>
                </div>
              </div>
            </div>
          </div><!-- /.systemtabcontent -->
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
