<?php

$hostname = $system->hostname();
$uptime   = $system->uptime();
$cores    = $system->processorCount();

// mem used
$memused  = $system->usedMemory();
$memused_status = "primary";
if ($memused > 90) {
    $memused_status = "danger";
} elseif ($memused > 75) {
    $memused_status = "warning";
} elseif ($memused >  0) {
    $memused_status = "success";
}

// cpu load
$cpuload = $system->systemLoadPercentage();
if ($cpuload > 90) {
    $cpuload_status = "danger";
} elseif ($cpuload > 75) {
    $cpuload_status = "warning";
} elseif ($cpuload >  0) {
    $cpuload_status = "success";
}

?>
<div class="row">
<div class="col-lg-12">
<div class="panel panel-primary">
<div class="panel-heading"><i class="fa fa-cube fa-fw"></i> <?php echo _("System"); ?></div>
<div class="panel-body">
<?php $status->showMessages(); ?>
<form role="form" action="?page=system_info" method="POST">
<?php echo CSRFTokenFieldTag() ?>
<ul class="nav nav-tabs" role="tablist">
  <li role="presentation" class="active systemtab"><a href="#system" aria-controls="system" role="tab" data-toggle="tab"><?php echo _("System"); ?></a></li>
  <li role="presentation" class="languagetab"><a href="#language" aria-controls="language" role="tab" data-toggle="tab"><?php echo _("Language"); ?></a></li>
  <li role="presentation" class="consoletab"><a href="#console" aria-controls="console" role="tab" data-toggle="tab"><?php echo _("Console"); ?></a></li>
</ul>

<div class="systemtabcontent tab-content">
  <div role="tabpanel" class="tab-pane active" id="system">
    <div class="row">
      <div class="col-lg-6">
        <h4><?php echo _("System Information"); ?></h4>
        <div class="info-item"><?php echo _("Hostname"); ?></div> <?php echo htmlspecialchars($hostname, ENT_QUOTES); ?></br>
        <div class="info-item"><?php echo _("Pi Revision"); ?></div> <?php echo htmlspecialchars(RPiVersion(), ENT_QUOTES); ?></br>
        <div class="info-item"><?php echo _("Uptime"); ?></div>   <?php echo htmlspecialchars($uptime, ENT_QUOTES); ?></br></br>
        <div class="info-item"><?php echo _("Memory Used"); ?></div>
        <div class="progress">
        <div class="progress-bar progress-bar-<?php echo htmlspecialchars($memused_status, ENT_QUOTES); ?> progress-bar-striped active"
        role="progressbar"
        aria-valuenow="<?php echo htmlspecialchars($memused, ENT_QUOTES); ?>" aria-valuemin="0" aria-valuemax="100"
        style="width: <?php echo htmlspecialchars($memused, ENT_QUOTES); ?>%;"><?php echo htmlspecialchars($memused, ENT_QUOTES); ?>%
        </div>
        </div>
        <div class="info-item"><?php echo _("CPU Load"); ?></div>
        <div class="progress">
        <div class="progress-bar progress-bar-<?php echo htmlspecialchars($cpuload_status, ENT_QUOTES); ?> progress-bar-striped active"
        role="progressbar"
        aria-valuenow="<?php echo htmlspecialchars($cpuload, ENT_QUOTES); ?>" aria-valuemin="0" aria-valuemax="100"
        style="width: <?php echo htmlspecialchars($cpuload, ENT_QUOTES); ?>%;"><?php echo htmlspecialchars($cpuload, ENT_QUOTES); ?>%
        </div>
        </div>

        <form action="?page=system_info" method="POST">
    <?php echo CSRFTokenFieldTag() ?>
        <?php if (!RASPI_MONITOR_ENABLED) : ?>
            <input type="submit" class="btn btn-warning" name="system_reboot"   value="<?php echo _("Reboot"); ?>" />
        <input type="submit" class="btn btn-warning" name="system_shutdown" value="<?php echo _("Shutdown"); ?>" />
        <?php endif ?>
    <a href="?page=<?php echo $_GET['page'] ?>" class="btn btn-outline btn-primary"><i class="fa fa-refresh"></i> <?php echo _("Refresh") ?></a>
        </form>
      </div>
    </div>
  </div>

  <div role="tabpanel" class="tab-pane" id="language">
    <h4><?php echo _("Language settings") ;?></h4>
    <div class="row">
      <div class="form-group col-md-4">
        <label for="code"><?php echo _("Select a language"); ?></label>
            <?php SelectorOptions('locale', $arrLocales, $_SESSION['locale']); ?>
      </div>
    </div>
    <input type="submit" class="btn btn-outline btn-primary" name="SaveLanguage" value="<?php echo _("Save settings"); ?>" />
    <a href="?page=<?php echo $_GET['page'] ?>" class="btn btn-outline btn-primary"><i class="fa fa-refresh"></i> <?php echo _("Refresh") ?></a>
  </div>

  <div role="tabpanel" class="tab-pane" id="console">
    <div class="row">
      <div class="col-lg-12"> 
        <iframe src="includes/webconsole.php" class="webconsole"></iframe>
      </div>
    </div>
  </div>

</div><!-- /.systemtabcontent -->

</form>
</div><!-- /.panel-body -->
<div class="panel-footer"></div>
</div><!-- /.panel-primary -->
</div><!-- /.col-lg-12 -->
</div><!-- /.row -->
