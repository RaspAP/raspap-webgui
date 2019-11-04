<?php
$arrHostapdConf = parse_ini_file('/etc/raspap/hostapd.ini');
if ($arrHostapdConf['WifiAPEnable'] == 1) {
    $client_iface = 'uap0';
} else {
    $client_iface = RASPI_WIFI_CLIENT_INTERFACE;
}
exec('cat '.RASPI_DNSMASQ_LEASES.'| grep -E $(arp -i '.$client_iface.' -n | grep -oE "(([0-9]|[a-f]|[A-F]){2}:){5}([0-9]|[a-f]|[A-F]){2}" | tr "\n" "\|" | sed "s/.$//")', $clients);
$ifaceStatus = $wlan0up ? "up" : "down";
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
	      <span class="text service-status"><?php echo strtolower($client_iface) .' '. _($ifaceStatus) ?></span>
	    </button>
	  </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <div class="card h-100">
              <div class="card-body wireless">
                <h4><?php echo _("Wireless Information"); ?></h4>
                <div class="info-item"><?php echo _("Connected To"); ?></div><div><?php echo htmlspecialchars($connectedSSID, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("AP Mac Address"); ?></div><div><?php echo htmlspecialchars($connectedBSSID, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("Bitrate"); ?></div><div><?php echo htmlspecialchars($bitrate, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("Signal Level"); ?></div><div><?php echo htmlspecialchars($signalLevel, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("Transmit Power"); ?></div><div><?php echo htmlspecialchars($txPower, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("Frequency"); ?></div><div><?php echo htmlspecialchars($frequency, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("Link Quality"); ?></div>
                <script>var linkQ = <?php echo json_encode($strLinkQuality); ?>;</script>
                <div class="chart-container">
                  <canvas id="canvas" class="chartjs-render-monitor"></canvas>
                </div>
             </div><!-- /.card-body -->
            </div><!-- /.card -->
          </div><!-- /.col-md-6 -->
          <div class="col-md-6">
	    <div class="card mb-3">
              <div class="card-body">
                <h4><?php echo _("Interface Information"); ?></h4>
                <div class="info-item"><?php echo _("Interface Name"); ?></div><div><?php echo RASPI_WIFI_CLIENT_INTERFACE; ?></div>
                <div class="info-item"><?php echo _("IPv4 Address"); ?></div><div><?php echo htmlspecialchars($ipv4Addrs, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("Subnet Mask"); ?></div><div><?php echo htmlspecialchars($ipv4Netmasks, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("IPv6 Address"); ?></div><div><?php echo htmlspecialchars($ipv6Addrs, ENT_QUOTES); ?></div>
                <div class="info-item"><?php echo _("Mac Address"); ?></div><div><?php echo htmlspecialchars($macAddr, ENT_QUOTES); ?></div>
              </div><!-- /.card-body -->
            </div><!-- /.card-->
            <div class="card mb-3">
              <div class="card-body">
                <h4><?php echo _("Interface Statistics"); ?></h4>
                <div class="info-item"><?php echo _("Received Packets"); ?></div><div><?php echo number_format($strRxPackets); ?></div>
                <div class="info-item"><?php echo _("Received Bytes"); ?></div><div><?php echo number_format($strRxBytes); ?></div>
                <div class="info-item"><?php echo _("Transferred Packets"); ?></div><div><?php echo number_format($strTxPackets); ?></div>
                <div class="info-item"><?php echo _("Transferred Bytes"); ?></div><div><?php echo number_format($strTxBytes); ?></div>
              </div><!-- /.card-body -->
            </div><!-- /.card -->
            <div class="card mb-3">
              <div class="card-body wireless">
                <h4><?php echo _("Connected Devices"); ?></h4>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th><?php echo _("Host name"); ?></th>
                        <th><?php echo _("IP Address"); ?></th>
                        <th><?php echo _("MAC Address"); ?></th>
                      </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client) : ?>
                            <?php $props = explode(' ', $client) ?>
                        <tr>
                          <td><?php echo htmlspecialchars($props[3], ENT_QUOTES) ?></td>
                          <td><?php echo htmlspecialchars($props[2], ENT_QUOTES) ?></td>
                          <td><?php echo htmlspecialchars($props[1], ENT_QUOTES) ?></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                  </table>
                </div><!-- /.table-responsive -->
              </div><!-- /.card-body -->
            </div><!-- /.card -->
          </div><!-- /.col-md-6 -->
        </div><!-- /.row -->

        <div class="col-lg-12">
          <div class="row">
            <form action="?page=wlan0_info" method="POST">
                <?php echo CSRFTokenFieldTag() ?>
                <?php if (!RASPI_MONITOR_ENABLED) : ?>
                    <?php if (!$wlan0up) : ?>
                    <input type="submit" class="btn btn-success" value="<?php echo _("Start").' '.RASPI_WIFI_CLIENT_INTERFACE ?>" name="ifup_wlan0" />
                    <?php else : ?>
                    <input type="submit" class="btn btn-warning" value="<?php echo _("Stop").' '.RASPI_WIFI_CLIENT_INTERFACE ?>"  name="ifdown_wlan0" />
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
