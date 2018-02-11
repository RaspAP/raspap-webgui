<?php
/**
*
* Find the version of the Raspberry Pi
* Currently only used for the system information page but may useful elsewhere
*
*/

function RPiVersion() {
  // Lookup table from http://www.raspberrypi-spy.co.uk/2012/09/checking-your-raspberry-pi-board-version/
  $revisions = array(
    '0002' => 'Model B Revision 1.0',
    '0003' => 'Model B Revision 1.0 + ECN0001',
    '0004' => 'Model B Revision 2.0 (256 MB)',
    '0005' => 'Model B Revision 2.0 (256 MB)',
    '0006' => 'Model B Revision 2.0 (256 MB)',
    '0007' => 'Model A',
    '0008' => 'Model A',
    '0009' => 'Model A',
    '000d' => 'Model B Revision 2.0 (512 MB)',
    '000e' => 'Model B Revision 2.0 (512 MB)',
    '000f' => 'Model B Revision 2.0 (512 MB)',
    '0010' => 'Model B+',
    '0013' => 'Model B+',
    '0011' => 'Compute Module',
    '0012' => 'Model A+',
    'a01041' => 'a01041',
    'a21041' => 'a21041',
    '900092' => 'PiZero 1.2',
    '900093' => 'PiZero 1.3',
    '9000c1' => 'PiZero W',
    'a02082' => 'Pi 3 Model B',
    'a22082' => 'Pi 3 Model B'
  );
  exec('cat /proc/cpuinfo', $cpuinfo_array);
  $rev = trim(array_pop(explode(':',array_pop(preg_grep("/^Revision/", $cpuinfo_array)))));
  if (array_key_exists($rev, $revisions)) {
    return $revisions[$rev];
  } else {
    return 'Unknown Pi';
  }
}

/**
*
*
*/
function DisplaySystem(){

  // hostname
  exec("hostname -f", $hostarray);
  $hostname = $hostarray[0];

  // uptime
  $uparray = explode(" ", exec("cat /proc/uptime"));
  $seconds = round($uparray[0], 0);
  $minutes = $seconds / 60;
  $hours   = $minutes / 60;
  $days    = floor($hours / 24);
  $hours   = floor($hours   - ($days * 24));
  $minutes = floor($minutes - ($days * 24 * 60) - ($hours * 60));
  $uptime= '';
  if ($days    != 0) { $uptime .= $days    . ' day'    . (($days    > 1)? 's ':' '); }
  if ($hours   != 0) { $uptime .= $hours   . ' hour'   . (($hours   > 1)? 's ':' '); }
  if ($minutes != 0) { $uptime .= $minutes . ' minute' . (($minutes > 1)? 's ':' '); }

  // mem used
  $memused_status = "primary";
  exec("free -m | awk '/Mem:/ { total=$2 ; used=$3 } END { print used/total*100}'", $memarray);
  $memused = floor($memarray[0]);
  if     ($memused > 90) { $memused_status = "danger";  }
  elseif ($memused > 75) { $memused_status = "warning"; }
  elseif ($memused >  0) { $memused_status = "success"; }

  // cpu load
  $cores   = exec("grep -c ^processor /proc/cpuinfo");
        $loadavg = exec("awk '{print $1}' /proc/loadavg");
  $cpuload = floor(($loadavg * 100) / $cores);
  if     ($cpuload > 90) { $cpuload_status = "danger";  }
  elseif ($cpuload > 75) { $cpuload_status = "warning"; }
  elseif ($cpuload >  0) { $cpuload_status = "success"; }

  ?>
  <div class="row">
  <div class="col-lg-12">
  <div class="panel panel-primary">
  <div class="panel-heading"><i class="fa fa-cube fa-fw"></i> System</div>
  <div class="panel-body">

    <?php
    if (isset($_POST['system_reboot'])) {
      echo '<div class="alert alert-warning">System Rebooting Now!</div>';
      $result = shell_exec("sudo /sbin/reboot");
    }
    if (isset($_POST['system_shutdown'])) {
      echo '<div class="alert alert-warning">System Shutting Down Now!</div>';
      $result = shell_exec("sudo /sbin/shutdown -h now");
    }
    ?>

    <div class="row">
    <div class="col-md-12">
    <div class="panel panel-default">
    <div class="panel-body">
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active systemtab"><a href="#system" aria-controls="system" role="tab" data-toggle="tab">System</a></li>
        <li role="presentation" class="consoletab"><a href="#console" aria-controls="console" role="tab" data-toggle="tab">Console</a></li>
    </ul>

    <div class="systemtabcontent tab-content">
        <div role="tabpanel" class="tab-pane active" id="system">
            <div class="row">
                <div class="col-lg-6">
                        <h4>System Information</h4>
                        <div class="info-item">Hostname</div> <?php echo $hostname ?></br>
                        <div class="info-item">Pi Revision</div> <?php echo RPiVersion() ?></br>
                        <div class="info-item">Uptime</div>   <?php echo $uptime ?></br></br>
                        <div class="info-item">Memory Used</div>
                        <div class="progress">
                        <div class="progress-bar progress-bar-<?php echo $memused_status ?> progress-bar-striped active"
                        role="progressbar"
                        aria-valuenow="<?php echo $memused ?>" aria-valuemin="0" aria-valuemax="100"
                        style="width: <?php echo $memused ?>%;"><?php echo $memused ?>%
                        </div>
                        </div>
                        <div class="info-item">CPU Load</div>
                        <div class="progress">
                        <div class="progress-bar progress-bar-<?php echo $cpuload_status ?> progress-bar-striped active"
                        role="progressbar"
                        aria-valuenow="<?php echo $cpuload ?>" aria-valuemin="0" aria-valuemax="100"
                        style="width: <?php echo $cpuload ?>%;"><?php echo $cpuload ?>%
                        </div>
                        </div>

                        <form action="?page=system_info" method="POST">
                        <input type="submit" class="btn btn-warning" name="system_reboot"   value="Reboot" />
                        <input type="submit" class="btn btn-warning" name="system_shutdown" value="Shutdown" />
                        <input type="button" class="btn btn-outline btn-primary" value="Refresh" onclick="document.location.reload(true)" />
                        </form>
                </div>
            </div>
        </div>
      <div role="tabpanel" class="tab-pane" id="console">
      <iframe src="includes/webconsole.php" class="webconsole"></iframe>
      </div>



    </div><!-- /.panel-body -->
    </div><!-- /.panel-default -->
    </div><!-- /.col-md-6 -->
    </div><!-- /.row -->
  </div><!-- /.panel-body -->
  </div><!-- /.panel-primary -->
  </div><!-- /.col-lg-12 -->
  </div><!-- /.row -->




      </div>
  </div>
  <?php
}
?>
