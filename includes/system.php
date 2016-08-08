<?php
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
	exec("free -m | awk '/Mem:/ { total=$2 } /buffers\/cache/ { used=$3 } END { print used/total*100}'", $memarray);
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
		<div class="col-md-6">
		<div class="panel panel-default">
		<div class="panel-body">
			<h4>System Information</h4>
			<div class="info-item">Hostname</div> <?php echo $hostname ?></br>
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
		</div><!-- /.panel-body -->
		</div><!-- /.panel-default -->
		</div><!-- /.col-md-6 -->
		</div><!-- /.row -->

		<form action="?page=system_info" method="POST">
			<input type="submit" class="btn btn-warning" name="system_reboot"   value="Reboot" />
			<input type="submit" class="btn btn-warning" name="system_shutdown" value="Shutdown" />
			<input type="button" class="btn btn-outline btn-primary" value="Refresh" onclick="document.location.reload(true)" />
		</form>

	</div><!-- /.panel-body -->
	</div><!-- /.panel-primary -->
	</div><!-- /.col-lg-12 -->
	</div><!-- /.row -->
	<?php
}
?>
