<?php

include('includes/sysstats.php');

?>
<!-- basic tab -->
<div role="tabpanel" class="tab-pane active" id="basic">
  <div class="row">
    <div class="col-lg-6">
      <h4 class="mt-3"><?php echo _("System Information"); ?></h4>
        <div class="row ms-1">
          <div class="col-4">
            <img class="device-illustration mx-3 my-2" src="app/img/devices/<?php echo $deviceImage; ?>" alt="<?php echo htmlspecialchars($revision, ENT_QUOTES); ?>"></a>
          </div>
          <div class="col-sm-10">
            <div class="row mb-1">
              <div class="info-item col-4"><?php echo _("Hostname"); ?></div><div class="info-value col"><?php echo htmlspecialchars($hostname, ENT_QUOTES); ?></div>
            </div>
            <div class="row mb-1">
              <div class="info-item col-4"><?php echo _("Pi Revision"); ?></div><div class="info-value col"><?php echo htmlspecialchars($revision, ENT_QUOTES); ?></div>
            </div>
            <div class="row mb-1">
              <div class="info-item col-4"><?php echo _("OS"); ?></div><div class="info-value col"><?php echo htmlspecialchars($os, ENT_QUOTES); ?></div>
            </div>
            <div class="row mb-1">
              <div class="info-item col-4"><?php echo _("Kernel"); ?></div><div class="info-value col"><?php echo htmlspecialchars($kernel, ENT_QUOTES); ?></div>
            </div>
            <div class="row mb-1">
              <div class="info-item col-4"><?php echo _("Uptime"); ?></div><div class="info-value col"><?php echo htmlspecialchars($uptime, ENT_QUOTES); ?></div>
	    </div>
	    <div class="row mb-1">
              <div class="info-item col-4"><?php echo _("System Time"); ?></div><div class="info-value col"><?php echo htmlspecialchars($systime, ENT_QUOTES); ?></div>
            </div>
          </div>
        </div>
      <div class="mb-1"><?php echo _("Memory Used"); ?></div>
      <div class="progress mb-2" style="height: 20px;">
        <div class="progress-bar bg-<?php echo htmlspecialchars($memused_status, ENT_QUOTES); ?>"
            role="progressbar" aria-valuenow="<?php echo htmlspecialchars($memused, ENT_QUOTES); ?>" aria-valuemin="0" aria-valuemax="100"
            style="width: <?php echo htmlspecialchars($memused, ENT_QUOTES); ?>%"><?php echo htmlspecialchars($memused, ENT_QUOTES); ?>%
        </div>
      </div>
      <div class="mb-1"><?php echo _("Storage Used"); ?></div>
      <div class="progress mb-2" style="height: 20px;">
        <div class="progress-bar bg-<?php echo htmlspecialchars($diskused_status, ENT_QUOTES); ?>"
            role="progressbar" aria-valuenow="<?php echo htmlspecialchars($diskused, ENT_QUOTES); ?>" aria-valuemin="0" aria-valuemax="100"
            style="width: <?php echo htmlspecialchars($diskused, ENT_QUOTES); ?>%"><?php echo htmlspecialchars($diskused, ENT_QUOTES); ?>%
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

      <form action="system_info" method="POST">
        <?php echo \RaspAP\Tokens\CSRF::hiddenField(); ?>
        <?php if (!RASPI_MONITOR_ENABLED) : ?>
            <input type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#system-confirm-reboot" value="<?php echo _("Reboot"); ?>" />
            <input type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#system-confirm-shutdown" value="<?php echo _("Shutdown"); ?>" />
        <?php endif ?>
        <button type="button" onClick="window.location.reload();" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
     </form>
      </div>
    </div>
  </div>

