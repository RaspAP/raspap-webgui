<?php
$arrHostapdConf = parse_ini_file(RASPI_CONFIG.'/hostapd.ini');
if ($arrHostapdConf['WifiAPEnable'] == 1) {
    $client_interface = 'uap0';
} else {
    $client_interface = $clientinfo["name"];
}
$ap_iface = $_SESSION['ap_interface'];
$MACPattern = '"([[:xdigit:]]{2}:){5}[[:xdigit:]]{2}"';
if ($arrHostapdConf['BridgedEnable'] == 1) {
    $moreLink = "index.php?page=hostapd_conf";
    exec('iw dev '.$ap_iface.' station dump | grep -oE '.$MACPattern, $clients);
} else {
    $moreLink = "index.php?page=dhcpd_conf";
    exec('cat '.RASPI_DNSMASQ_LEASES.'| grep -E $(iw dev '.$ap_iface.' station dump | grep -oE '.$MACPattern.' | paste -sd "|")', $clients);
}
$ifaceStatus = $clientinfo["connected"]=="y" ? "up" : "down";
switch($clientinfo["type"]) {
	case 0:
	case 1:
			$client_title = "Client: Ethernet cable";
			$type_name = "Ethernet";
			break;
	case 2:
			$client_title = "Client: Smartphone (USB tethering)";
			$type_name = "Smartphone";
			break;
	case 3:
			$client_title = "Wireless Client";
			$type_name = "Wifi";
			break;
	case 4:
	case 5:
			$client_title = "Mobile Data Client";
			$type_name = "Mobile Data";
			break;
	default: 
			$client_title = "No information for client available";
			$type_name = "No Client";
}

?>
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
    <div class="col">
      <i class="fas fa-tachometer-alt fa-fw mr-2"></i><?php echo _("Dashboard"); ?>
    </div>
    <div class="col">
      <button class="btn btn-light btn-icon-split btn-sm service-status float-right">
        <span class="icon"><i class="fas fa-circle service-status-<?php echo $ifaceStatus ?>"></i></span>
        <span class="text service-status"><?php echo $type_name .' '. _($ifaceStatus) ?></span>
      </button>
    </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->

      <div class="card-body">
        <div class="row">

          <div class="col-lg-12">
            <div class="card mb-3">
              <div class="card-body">
                <h4><?php echo _("Hourly traffic amount"); ?></h4>
                <div id="divInterface" class="d-none"><?php echo $_SESSION['ap_interface']; ?></div>
                <div class="col-md-12">
                  <canvas id="divDBChartBandwidthhourly"></canvas>
                </div>
              </div><!-- /.card-body -->
            </div><!-- /.card-->
          </div>

          <div class="col-sm-6 align-items-stretch">
            <div class="card h-100">
              <div class="card-body wireless">
				<h4><?php echo _("$client_title"); ?></h4>
				<div class="row justify-content-md-center">
                <div class="col-md">
				  <?php if ($clientinfo["type"] < 0) : // NO CLIENT ?>
					<div class="info-item"><?php echo _("No Client device found"); ?></div>
				  <?php elseif ($clientinfo["type"] == 3) : // WIRELESS ?>
					<div class="info-item"><?php echo _("Connected To"); ?></div><div><?php echo htmlspecialchars($clientinfo["ssid"], ENT_QUOTES); ?></div>
					<div class="info-item"><?php echo _("AP Mac Address"); ?></div><div><?php echo htmlspecialchars($clientinfo["ap-mac"], ENT_QUOTES); ?></div>
					<div class="info-item"><?php echo _("Bitrate"); ?></div><div><?php echo htmlspecialchars($clientinfo["bitrate"], ENT_QUOTES); ?></div>
					<div class="info-item"><?php echo _("Signal Level"); ?></div><div><?php echo htmlspecialchars($clientinfo["signal"], ENT_QUOTES); ?></div>
					<div class="info-item"><?php echo _("Transmit Power"); ?></div><div><?php echo htmlspecialchars($txPower, ENT_QUOTES); ?></div>
					<div class="info-item"><?php echo _("Frequency"); ?></div><div><?php echo htmlspecialchars($clientinfo["freq"]." MHz", ENT_QUOTES); ?></div>
                  <?php elseif ($clientinfo["type"] == 2 ) : // Smartphones (tethering over USB) ?>
                    <div class="info-item"><?php echo _("Device"); ?></div><div><?php echo htmlspecialchars($clientinfo["vendor"]." ".$clientinfo["model"], ENT_QUOTES); ?></div>
                    <div class="info-item"><?php echo _("IP Address"); ?></div><div><?php echo htmlspecialchars($clientinfo["ipaddress"], ENT_QUOTES); ?></div>

 				  <?php elseif ($clientinfo["type"] == 4 ) : // MOBILE DATA - ROUTER MODE (HILINK) ?>
                    <?php 
						exec('ip route list |  sed -rn "s/default via (([0-9]{1,3}\.){3}[0-9]{1,3}).*dev '.$clientinfo["name"].'.*/\1/p"',$gw); // get gateway
						$gw=empty($gw) ? "" : $gw[0]; 
                    ?>
                    <div class="info-item"><?php echo _("Device"); ?></div><div><?php echo htmlspecialchars($clientinfo["model"], ENT_QUOTES)." (Hilink)"; ?></div>
                    <div class="info-item"><?php echo _("Connection mode"); ?></div><div><?php echo htmlspecialchars($clientinfo["mode"], ENT_QUOTES); ?></div>
                    <div class="info-item"><?php echo _("Signal quality"); ?></div><div><?php echo htmlspecialchars($clientinfo["signal"], ENT_QUOTES); ?></div>
                    <div class="info-item"><?php echo _("Network"); ?></div><div><?php echo htmlspecialchars($clientinfo["operator"], ENT_QUOTES); ?></div>
                    <div class="info-item"><?php echo _("WAN IP"); ?></div><div><?php echo htmlspecialchars($clientinfo["ipaddress"], ENT_QUOTES); ?></div>
                    <div class="info-item"><?php echo _("Web-GUI"); ?></div><div><?php if(!empty($gw)) echo '<a href="http://'.$gw.'" >'.$gw."</a>"; ?></div>
				  <?php elseif ($clientinfo["type"] == 5 ) : // MOBILE DATA MODEM) ?>
                    <div class="info-item"><?php echo _("Device"); ?></div><div><?php echo htmlspecialchars($clientinfo["model"], ENT_QUOTES); ?></div>
                    <div class="info-item"><?php echo _("Connection mode"); ?></div><div><?php echo htmlspecialchars($clientinfo["mode"], ENT_QUOTES); ?></div>
                    <div class="info-item"><?php echo _("Signal strength"); ?></div><div><?php echo htmlspecialchars($clientinfo["signal"], ENT_QUOTES); ?></div>
                    <div class="info-item"><?php echo _("Network"); ?></div><div><?php echo htmlspecialchars($clientinfo["operator"], ENT_QUOTES); ?></div>
                 <?php else : // ETHERNET ?>
                    <div class="info-item"><?php echo _("Device"); ?></div><div><?php echo htmlspecialchars($clientinfo["vendor"]." ".$clientinfo["model"], ENT_QUOTES); ?></div>
                    <div class="info-item"><?php echo _("IP Address"); ?></div><div><?php echo htmlspecialchars($clientinfo["ipaddress"], ENT_QUOTES); ?></div>
 				 <?php endif; ?>
                </div>


				<div class="col-md mt-2 d-flex justify-content-center">
			    <?php 
 				  preg_match("/.*\((\s*\d*)\s*%\s*\)/",$clientinfo["signal"],$match);
				  $strLinkQuality=array_key_exists(1,$match) ? $match[1] : 0;
				?>
                <script>var linkQ = <?php echo json_encode($strLinkQuality); ?>;</script>
                <div class="chart-container">
                  <canvas id="divChartLinkQ"></canvas>
                </div>
                </div><!--row-->
              </div>
             </div><!-- /.card-body -->
            </div><!-- /.card -->
          </div><!-- /.col-md-6 -->
          <div class="col-sm-6">
            <div class="card h-100 mb-3">
              <div class="card-body">
                <h4><?php echo _("Connected Devices"); ?></h4>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <?php if ($arrHostapdConf['BridgedEnable'] == 1) : ?>
                          <th><?php echo _("MAC Address"); ?></th>
                        <?php else : ?>
                          <th><?php echo _("Host name"); ?></th>
                          <th><?php echo _("IP Address"); ?></th>
                          <th><?php echo _("MAC Address"); ?></th>
                        <?php endif; ?>
                      </tr>
                    </thead>
                    <tbody>
                        <?php if ($arrHostapdConf['BridgedEnable'] == 1) : ?>
                          <tr>
                            <td><small class="text-muted"><?php echo _("Bridged AP mode is enabled. For Hostname and IP, see your router's admin page.");?></small></td>
                          </tr>
                        <?php endif; ?>
                        <?php foreach (array_slice($clients,0, 2) as $client) : ?>
                        <tr>
                          <?php if ($arrHostapdConf['BridgedEnable'] == 1): ?>
                            <td><?php echo htmlspecialchars($client, ENT_QUOTES) ?></td>
                          <?php else : ?>
                            <?php $props = explode(' ', $client) ?>
                            <td><?php echo htmlspecialchars($props[3], ENT_QUOTES) ?></td>
                            <td><?php echo htmlspecialchars($props[2], ENT_QUOTES) ?></td>
                            <td><?php echo htmlspecialchars($props[1], ENT_QUOTES) ?></td>
                          <?php endif; ?>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                  </table>
                  <?php if (sizeof($clients) >2) : ?>
                      <div class="col-lg-12 float-right">
                        <a class="btn btn-outline-info" role="button" href="<?php echo $moreLink ?>"><?php echo _("More");?>  <i class="fas fa-chevron-right"></i></a>
                      </div>
                  <?php elseif (sizeof($clients) ==0) : ?>
                      <div class="col-lg-12 mt-3"><?php echo _("No connected devices");?></div>
                  <?php endif; ?>
                </div><!-- /.table-responsive -->
              </div><!-- /.card-body -->
            </div><!-- /.card -->
          </div><!-- /.col-md-6 -->
        </div><!-- /.row -->

        <div class="col-lg-12 mt-3">
          <div class="row">
            <form action="?page=wlan0_info" method="POST">
                <?php echo CSRFTokenFieldTag() ?>
                <?php if (!RASPI_MONITOR_ENABLED) : ?>
                    <?php if ($ifaceStatus == "down") : ?>
                    <input type="submit" class="btn btn-success" value="<?php echo _("Start").' '.$type_name ?>" name="ifup_wlan0" />
                    <?php else : ?>
                    <input type="submit" class="btn btn-warning" value="<?php echo _("Stop").' '.$type_name ?>"  name="ifdown_wlan0" />
                    <?php endif ?>
                <?php endif ?>
              <a href="?page=<?php echo $_GET['page'] ?>" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> <?php echo _("Refresh") ?></a>
            </form>
          </div>
        </div>

      </div><!-- /.card-body -->
      <div class="card-footer"><?php echo _("Information provided by ip and iw and from system"); ?></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
<script type="text/javascript"<?php //echo ' nonce="'.$csp_page_nonce.'"'; ?>>
// js translations:
var t = new Array();
t['send'] = '<?php echo addslashes(_('Send')); ?>';
t['receive'] = '<?php echo addslashes(_('Receive')); ?>';
</script>
