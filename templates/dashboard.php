<?php
$arrHostapdConf = parse_ini_file('/etc/raspap/hostapd.ini');
if ($arrHostapdConf['WifiAPEnable'] == 1) {
    $client_iface = 'uap0';
} else {
    $client_iface = RASPI_WIFI_CLIENT_INTERFACE;
}
exec('cat '.RASPI_DNSMASQ_LEASES.'| grep -E $(arp -i '.$client_iface.' -n | grep -oE "(([0-9]|[a-f]|[A-F]){2}:){5}([0-9]|[a-f]|[A-F]){2}" | tr "\n" "\|" | sed "s/.$//")', $clients);
?>
<div class="row">
  <div class="col-lg-12">
    <div class="panel panel-primary">
      <div class="panel-heading"><i class="fa fa-dashboard fa-fw"></i> <?php echo _("Dashboard"); ?></div>
      <div class="panel-body">
        <?php $status->showMessages(); ?>
        <div class="row">
          <div class="col-md-6">
            <div class="panel panel-default">
              <div class="panel-body">
                <h4><?php echo _("Interface Information"); ?></h4>
                <div class="info-item"><?php echo _("Interface Name"); ?></div> <?php echo RASPI_WIFI_CLIENT_INTERFACE; ?><br />
                <div class="info-item"><?php echo _("IPv4 Address"); ?></div> <?php echo htmlspecialchars($ipv4Addrs, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("Subnet Mask"); ?></div> <?php echo htmlspecialchars($ipv4Netmasks, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("IPv6 Address"); ?></div> <?php echo htmlspecialchars($ipv6Addrs, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("Mac Address"); ?></div> <?php echo htmlspecialchars($macAddr, ENT_QUOTES); ?><br /><br />
              </div><!-- /.panel-body -->
            </div><!-- /.panel-default -->
            <div class="panel panel-default">
              <div class="panel-body">
                <h4><?php echo _("Interface Statistics"); ?></h4>
                <div class="info-item"><?php echo _("Received Packets"); ?></div> <?php echo htmlspecialchars($strRxPackets, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("Received Bytes"); ?></div> <?php echo htmlspecialchars($strRxBytes, ENT_QUOTES); ?><br /><br />
                <div class="info-item"><?php echo _("Transferred Packets"); ?></div> <?php echo htmlspecialchars($strTxPackets, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("Transferred Bytes"); ?></div> <?php echo htmlspecialchars($strTxBytes, ENT_QUOTES); ?><br />
              </div><!-- /.panel-body -->
            </div><!-- /.panel-default -->
          </div><!-- /.col-md-6 -->
          <div class="col-md-6">
            <div class="panel panel-default">
              <div class="panel-body wireless">
                <h4><?php echo _("Wireless Information"); ?></h4>
                <div class="info-item"><?php echo _("Connected To"); ?></div> <?php echo htmlspecialchars($connectedSSID, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("AP Mac Address"); ?></div> <?php echo htmlspecialchars($connectedBSSID, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("Bitrate"); ?></div> <?php echo htmlspecialchars($bitrate, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("Signal Level"); ?></div> <?php echo htmlspecialchars($signalLevel, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("Transmit Power"); ?></div> <?php echo htmlspecialchars($txPower, ENT_QUOTES); ?><br />
                <div class="info-item"><?php echo _("Frequency"); ?></div> <?php echo htmlspecialchars($frequency, ENT_QUOTES); ?><br /><br />
                <div class="info-item"><?php echo _("Link Quality"); ?></div>
                <div class="progress">
                  <div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="<?php echo htmlspecialchars($strLinkQuality, ENT_QUOTES); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo htmlspecialchars($strLinkQuality, ENT_QUOTES); ?>%;"><?php echo htmlspecialchars($strLinkQuality, ENT_QUOTES); ?>%</div>
                </div>
              </div><!-- /.panel-body -->
            </div><!-- /.panel-default -->
            <div class="panel panel-default">
              <div class="panel-body wireless">
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
              </div><!-- /.panel-body -->
            </div><!-- /.panel-default -->
          </div><!-- /.col-md-6 -->
        </div><!-- /.row -->

        <div class="col-lg-12">
          <div class="row">
            <form action="?page=wlan0_info" method="POST">
                <?php echo CSRFTokenFieldTag() ?>
                <?php if (!RASPI_MONITOR_ENABLED) : ?>
                    <?php if (!$wlan0up) : ?>
                    <input type="submit" class="btn btn-success" value="<?php echo _("Start ").RASPI_WIFI_CLIENT_INTERFACE ?>" name="ifup_wlan0" />
                    <?php else : ?>
                    <input type="submit" class="btn btn-warning" value="<?php echo _("Stop ").RASPI_WIFI_CLIENT_INTERFACE ?>"  name="ifdown_wlan0" />
                    <?php endif ?>
                <?php endif ?>
              <a href="?page=<?php echo $_GET['page'] ?>" class="btn btn-outline btn-primary"><i class="fa fa-refresh"></i> <?php echo _("Refresh") ?></a>
            </form>
          </div>
        </div>

      </div><!-- /.panel-body -->
      <div class="panel-footer"><?php echo _("Information provided by ip and iw and from system."); ?></div>
    </div><!-- /.panel-default -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
