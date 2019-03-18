<?php

/**
* Show dashboard page.
*/
function DisplayDashboard(){

  $status = new StatusMessages();
  // Need this check interface name for proper shell execution.
  if (!preg_match('/^([a-zA-Z0-9]+)$/', RASPI_WIFI_CLIENT_INTERFACE)) {
    $status->addMessage(_('Interface name invalid.'), 'danger');
    $status->showMessages();
    return;
  }

  if (!function_exists('exec')) {
    $status->addMessage(_('Required exec function is disabled. Check if exec is not added to php disable_functions.'), 'danger');
    $status->showMessages();
    return;
  }

  exec('ip a show '.RASPI_WIFI_CLIENT_INTERFACE, $stdoutIp);
  $stdoutIpAllLinesGlued = implode(" ", $stdoutIp);
  $stdoutIpWRepeatedSpaces = preg_replace('/\s\s+/', ' ', $stdoutIpAllLinesGlued);

  preg_match('/link\/ether ([0-9a-f:]+)/i', $stdoutIpWRepeatedSpaces, $matchesMacAddr ) || $matchesMacAddr[1] = _('No MAC Address Found');
  $macAddr = $matchesMacAddr[1];

  $ipv4Addrs = '';
  $ipv4Netmasks = '';
  if (!preg_match_all('/inet (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/([0-3][0-9])/i', $stdoutIpWRepeatedSpaces, $matchesIpv4AddrAndSubnet)) {
    $ipv4Addrs = _('No IPv4 Address Found');
  } else {
    $numMatchesIpv4AddrAndSubnet = count($matchesIpv4AddrAndSubnet);
    for ($i = 1; $i < $numMatchesIpv4AddrAndSubnet; $i += 2) {
      if ($i > 2) {
        $ipv4Netmasks .= ' ';
        $ipv4Addrs .= ' ';
      }

      $ipv4Addrs .= $matchesIpv4AddrAndSubnet[$i][0];
      $ipv4Netmasks .= long2ip(-1 << (32 -(int)$matchesIpv4AddrAndSubnet[$i+1][0]));
    }
  }

  $ipv6Addrs = '';
  if (!preg_match_all('/inet6 ([a-f0-9:]+)/i', $stdoutIpWRepeatedSpaces, $matchesIpv6Addr)) {
    $ipv6Addrs = _('No IPv6 Address Found');
  } else {
    $numMatchesIpv6Addr = count($matchesIpv6Addr);
    for ($i = 1; $i < $numMatchesIpv6Addr; ++$i) {
      if ($i > 1) {
        $ipv6Addrs .= ' ';
      }

      $ipv6Addrs .= $matchesIpv6Addr[$i];
    }
  }

  preg_match('/state (UP|DOWN)/i', $stdoutIpWRepeatedSpaces, $matchesState ) || $matchesState[1] = 'unknown';
  $interfaceState = $matchesState[1];

  // Because of table layout used in the ip output we get the interface statistics directly from 
  // the system. One advantage of this is that it could work when interface is disable.
  exec('cat /sys/class/net/'.RASPI_WIFI_CLIENT_INTERFACE.'/statistics/rx_packets ', $stdoutCatRxPackets);
  $strRxPackets = _('No data');
  if (ctype_digit($stdoutCatRxPackets[0])) {
    $strRxPackets = $stdoutCatRxPackets[0];
  }

  exec('cat /sys/class/net/'.RASPI_WIFI_CLIENT_INTERFACE.'/statistics/tx_packets ', $stdoutCatTxPackets);
  $strTxPackets = _('No data');
  if (ctype_digit($stdoutCatTxPackets[0])) {
    $strTxPackets = $stdoutCatTxPackets[0];
  }

  exec('cat /sys/class/net/'.RASPI_WIFI_CLIENT_INTERFACE.'/statistics/rx_bytes ', $stdoutCatRxBytes);
  $strRxBytes = _('No data');
  if (ctype_digit($stdoutCatRxBytes[0])) {
    $strRxBytes = $stdoutCatRxBytes[0];
    $strRxBytes .= getHumanReadableDatasize($strRxBytes);
  }

  exec('cat /sys/class/net/'.RASPI_WIFI_CLIENT_INTERFACE.'/statistics/tx_bytes ', $stdoutCatTxBytes);
  $strTxBytes = _('No data');
  if (ctype_digit($stdoutCatTxBytes[0])) {
    $strTxBytes = $stdoutCatTxBytes[0];
    $strTxBytes .= getHumanReadableDatasize($strTxBytes);
  }

  define('SSIDMAXLEN', 32);
  // Warning iw comes with: "Do NOT screenscrape this tool, we don't consider its output stable."
  exec('iw dev '.RASPI_WIFI_CLIENT_INTERFACE.' link ', $stdoutIw);
  $stdoutIwAllLinesGlued = implode(' ', $stdoutIw);
  $stdoutIwWRepSpaces = preg_replace('/\s\s+/', ' ', $stdoutIwAllLinesGlued);

  preg_match('/Connected to (([0-9A-Fa-f]{2}:){5}([0-9A-Fa-f]{2}))/', $stdoutIwWRepSpaces, $matchesBSSID) || $matchesBSSID[1] = '';
  $connectedBSSID = $matchesBSSID[1];

  $wlanHasLink = false;
  if ($interfaceState === 'UP') {
    $wlanHasLink = true;
  }

  if (!preg_match('/SSID: ([^ ]{1,'.SSIDMAXLEN.'})/', $stdoutIwWRepSpaces, $matchesSSID)) {
    $wlanHasLink = false;
    $matchesSSID[1] = 'Not connected';
  }

  $connectedSSID = $matchesSSID[1];

  preg_match('/freq: (\d+)/i', $stdoutIwWRepSpaces, $matchesFrequency) || $matchesFrequency[1] = '';
  $frequency = $matchesFrequency[1].' MHz';

  preg_match('/signal: (-?[0-9]+ dBm)/i', $stdoutIwWRepSpaces, $matchesSignal) || $matchesSignal[1] = '';
  $signalLevel = $matchesSignal[1];

  preg_match('/tx bitrate: ([0-9\.]+ [KMGT]?Bit\/s)/', $stdoutIwWRepSpaces, $matchesBitrate) || $matchesBitrate[1] = '';
  $bitrate = $matchesBitrate[1];

  // txpower is now displayed on iw dev(..) info command, not on link command.
  exec('iw dev '.RASPI_WIFI_CLIENT_INTERFACE.' info ', $stdoutIwInfo);
  $stdoutIwInfoAllLinesGlued = implode(' ', $stdoutIwInfo);
  $stdoutIpInfoWRepSpaces = preg_replace('/\s\s+/', ' ', $stdoutIwInfoAllLinesGlued);

  preg_match('/txpower ([0-9\.]+ dBm)/i', $stdoutIpInfoWRepSpaces, $matchesTxPower ) || $matchesTxPower[1] = '';
  $txPower = $matchesTxPower[1];

  // iw does not have the "Link Quality". This is a is an aggregate value, 
  // and depends on the driver and hardware.
  // Display link quality as signal quality for now.
  $strLinkQuality = 0;
  if ($signalLevel > -100 && $wlanHasLink) {
    if ($signalLevel >= 0) {
      $strLinkQuality = 100;
    } else {
      $strLinkQuality = 100 + $signalLevel;
    }
  }

  $wlan0up = false;
  $classMsgDevicestatus = 'warning';
  if ($interfaceState === 'UP') {
      $wlan0up = true;
      $classMsgDevicestatus = 'success';
  }


  if (isset($_POST['ifdown_wlan0'])) {
    // Pressed stop button
    if ($interfaceState === 'UP') {
      $status->addMessage(sprintf(_('Interface is going %s.'), _('down')), 'warning');
      exec( 'sudo ip link set '.RASPI_WIFI_CLIENT_INTERFACE.' down' );
      $wlan0up = false;
      $status->addMessage(sprintf(_('Interface is now %s.'), _('down')), 'success');
    } elseif ($interfaceState === 'unknown') {
      $status->addMessage(_('Interface state unknown.'), 'danger');
    } else {
      $status->addMessage(sprintf(_('Interface already %s.'), _('down')), 'warning');
    }
  } elseif( isset($_POST['ifup_wlan0']) ) {
    // Pressed start button
    if ($interfaceState === 'DOWN') {
      $status->addMessage(sprintf(_('Interface is going %s.'), _('up')), 'warning');
      exec('sudo ip link set ' . RASPI_WIFI_CLIENT_INTERFACE . ' up');
      exec('sudo ip -s a f label ' . RASPI_WIFI_CLIENT_INTERFACE);
      $wlan0up = true;
      $status->addMessage(sprintf(_('Interface is now %s.'), _('up')), 'success');
    } elseif ($interfaceState === 'unknown') {
      $status->addMessage(_('Interface state unknown.'), 'danger');
    } else {
      $status->addMessage(sprintf(_('Interface already %s.'), _('up')), 'warning');
    }
  } else {
    $status->addMessage(sprintf(_('Interface is %s.'), strtolower($interfaceState)), $classMsgDevicestatus);
  }
  ?>
  <div class="row">
      <div class="col-lg-12">
          <div class="panel panel-primary">
            <div class="panel-heading"><i class="fa fa-dashboard fa-fw"></i> <?php echo _("Dashboard"); ?></div>
              <div class="panel-body">
                <p><?php $status->showMessages(); ?></p>
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
                                <div class="progress-bar progress-bar-info progress-bar-striped active"
                                  role="progressbar"
                                  aria-valuenow="<?php echo htmlspecialchars($strLinkQuality, ENT_QUOTES); ?>" aria-valuemin="0" aria-valuemax="100"
                                  style="width: <?php echo htmlspecialchars($strLinkQuality, ENT_QUOTES); ?>%;"><?php echo htmlspecialchars($strLinkQuality, ENT_QUOTES); ?>%
                                </div>
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
<?php
exec('cat '.RASPI_DNSMASQ_LEASES.'| grep -E $(arp -i '.RASPI_WIFI_CLIENT_INTERFACE.' | grep -oE "(([0-9]|[a-f]|[A-F]){2}:){5}([0-9]|[a-f]|[A-F]){2}" | tr "\n" "\|" | sed "s/.$//")', $clients);
foreach( $clients as $client ) {
    $client_items = explode(' ', $client);
    echo '<tr>'.PHP_EOL;
    echo '<td>'.htmlspecialchars($client_items[3], ENT_QUOTES).'</td>'.PHP_EOL;
    echo '<td>'.htmlspecialchars($client_items[2], ENT_QUOTES).'</td>'.PHP_EOL;
    echo '<td>'.htmlspecialchars($client_items[1], ENT_QUOTES).'</td>'.PHP_EOL;
    echo '</tr>'.PHP_EOL;
};
?>
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
                    <?php if ( !$wlan0up ) {
                      echo '<input type="submit" class="btn btn-success" value="'._("Start ").RASPI_WIFI_CLIENT_INTERFACE.'" name="ifup_wlan0" />';
                    } else {
                      echo '<input type="submit" class="btn btn-warning" value="'._("Stop ").RASPI_WIFI_CLIENT_INTERFACE.'"  name="ifdown_wlan0" />';
                    }
              ?>
              <input type="button" class="btn btn-outline btn-primary" value="<?php echo _("Refresh"); ?>" onclick="document.location.reload(true)" />
              </form>
            </div>
              </div>

                </div><!-- /.panel-body -->
                <div class="panel-footer"><?php echo _("Information provided by ip and iw and from system."); ?></div>
            </div><!-- /.panel-default -->
        </div><!-- /.col-lg-12 -->
    </div><!-- /.row -->
  <?php
}


/**
 * Get a human readable data size string from a number of bytes.
 *
 * @param long $numbytes   The number of bytes.
 * @param int  $precision  The number of numbers to round to after the dot/comma.
 * @return string Data size in units: PB, TB, GB, MB or KB otherwise an empty string.
 */
function getHumanReadableDatasize($numbytes, $precision = 2)
{
  $humanDatasize = '';
  $kib = 1024;
  $mib = $kib * 1024;
  $gib = $mib * 1024;
  $tib = $gib * 1024;
  $pib = $tib * 1024;
  if ($numbytes >= $pib) {
    $humanDatasize = ' ('.round($numbytes / $pib, $precision).' PB)';
  } elseif ($numbytes >= $tib) {
    $humanDatasize = ' ('.round($numbytes / $tib, $precision).' TB)';
  } elseif ($numbytes >= $gib) {
    $humanDatasize = ' ('.round($numbytes / $gib, $precision).' GB)';
  } elseif ($numbytes >= $mib) {
    $humanDatasize = ' ('.round($numbytes / $mib, $precision).' MB)';
  } elseif ($numbytes >= $kib) {
    $humanDatasize = ' ('.round($numbytes / $kib, $precision).' KB)';
  }

  return $humanDatasize;
}

