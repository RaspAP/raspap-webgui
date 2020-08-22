<?php

include('includes/sysstats.php');

?>
<!-- basic tab -->
<div role="tabpanel" class="tab-pane active" id="basic">
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

