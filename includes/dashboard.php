<?php

require_once 'includes/config.php';
require_once 'includes/wifi_functions.php';

/**
 * Show dashboard page.
 */
function DisplayDashboard(&$extraFooterScripts)
{
    getWifiInterface();
    $status = new StatusMessages();
    // Need this check interface name for proper shell execution.
    if (!preg_match('/^([a-zA-Z0-9]+)$/', $_SESSION['wifi_client_interface'])) {
        $status->addMessage(_('Interface name invalid.'), 'danger');
        $status->showMessages();
        return;
    }

    if (!function_exists('exec')) {
        $status->addMessage(_('Required exec function is disabled. Check if exec is not added to php disable_functions.'), 'danger');
        $status->showMessages();
        return;
    }
	
	// ----------------------------- INFOS ABOUT THE ACCESS POINT -------------------------------------------------------------
	
    exec('ip a show '.$_SESSION['ap_interface'], $stdoutIp);
    $stdoutIpAllLinesGlued = implode(" ", $stdoutIp);
    $stdoutIpWRepeatedSpaces = preg_replace('/\s\s+/', ' ', $stdoutIpAllLinesGlued);

    preg_match('/link\/ether ([0-9a-f:]+)/i', $stdoutIpWRepeatedSpaces, $matchesMacAddr) || $matchesMacAddr[1] = _('No MAC Address Found');
    $macAddr = $matchesMacAddr[1];

    $ipv4Addrs = '';
    $ipv4Netmasks = '';
    if (!preg_match_all('/inet (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/([0-3][0-9])/i', $stdoutIpWRepeatedSpaces, $matchesIpv4AddrAndSubnet, PREG_SET_ORDER)) {
        $ipv4Addrs = _('No IPv4 Address Found');
    } else {
        foreach ($matchesIpv4AddrAndSubnet as $inet) {
            $address = $inet[1];
            $suffix  = (int) $inet[2];
            $netmask = long2ip(-1 << (32 - $suffix));

            $ipv4Addrs    .= " $address";
            $ipv4Netmasks .= " $netmask";
        }
        $ipv4Addrs    = trim($ipv4Addrs);
        $ipv4Netmasks = trim($ipv4Netmasks);
    }
    $ipv4Netmasks = empty($ipv4Netmasks) ? "-" : $ipv4Netmasks;

    $ipv6Addrs = '';
    if (!preg_match_all('/inet6 ([a-f0-9:]+)/i', $stdoutIpWRepeatedSpaces, $matchesIpv6Addr)) {
        $ipv6Addrs = _('No IPv6 Address Found');
    } else {
        if (isset($matchesIpv6Addr[1])) {
            $ipv6Addrs = implode(' ', $matchesIpv6Addr[1]);
        }
    }

    preg_match('/state (UP|DOWN)/i', $stdoutIpWRepeatedSpaces, $matchesState) || $matchesState[1] = 'unknown';
    $interfaceState = $matchesState[1];

    // Because of table layout used in the ip output we get the interface statistics directly from
    // the system. One advantage of this is that it could work when interface is disable.
    exec('cat /sys/class/net/'.$_SESSION['ap_interface'].'/statistics/rx_packets ', $stdoutCatRxPackets);
    $strRxPackets = _('No data');
    if (ctype_digit($stdoutCatRxPackets[0])) {
        $strRxPackets = $stdoutCatRxPackets[0];
    }

    exec('cat /sys/class/net/'.$_SESSION['ap_interface'].'/statistics/tx_packets ', $stdoutCatTxPackets);
    $strTxPackets = _('No data');
    if (ctype_digit($stdoutCatTxPackets[0])) {
        $strTxPackets = $stdoutCatTxPackets[0];
    }

    exec('cat /sys/class/net/'.$_SESSION['ap_interface'].'/statistics/rx_bytes ', $stdoutCatRxBytes);
    $strRxBytes = _('No data');
    if (ctype_digit($stdoutCatRxBytes[0])) {
        $strRxBytes = $stdoutCatRxBytes[0];
        $strRxBytes .= getHumanReadableDatasize($strRxBytes);
    }

    exec('cat /sys/class/net/'.$_SESSION['ap_interface'].'/statistics/tx_bytes ', $stdoutCatTxBytes);
    $strTxBytes = _('No data');
    if (ctype_digit($stdoutCatTxBytes[0])) {
        $strTxBytes = $stdoutCatTxBytes[0];
        $strTxBytes .= getHumanReadableDatasize($strTxBytes);
    }

	// ------------------------ INFOS ABOUT THE CLIENT---------------------------------------------------------------
	$clientinfo=array("name"=>"none","type"=>-1,"connected"=>"n");
	$raspi_client=$_SESSION['wifi_client_interface'];
    exec('/usr/local/sbin/getClients.sh', $clients); # get list of clients, including connection information (json format)
    if(!empty($clients)) {
        $clients=json_decode($clients[0],true);
		// client type: 0 - eth0, 1 -ethx, 2 - usb tethering, 3 - wlan, 4 - mobile data (router mode), 5 - mobile data modem
        // extract the infos for the device with the highest type number		
        $ncl=$clients["clients"];
        if($ncl > 0) {
			$ty=-1;
            foreach($clients["device"] as $dev) {
               if($dev["type"]>$ty) {
                 $ty=$dev["type"];
                 $clientinfo=$dev;
               }
            }
        }
    }
	if ($clientinfo["name"] != "none") $raspi_client = $clientinfo["name"];
	$interfaceState = $clientinfo["connected"] == "y" ? 'UP' : 'DOWN';
	
	$txPower="";
	if ($clientinfo["type"] == 3) {
      // txpower is now displayed on iw dev(..) info command, not on link command.
      exec('iw dev '.$clientinfo["name"].' info |  sed -rn "s/.*txpower ([0-9]*)[0-9\.]*( dBm).*/\1\2/p"', $stdoutIwInfo);
      if (!empty($stdoutIwInfo)) $txPower=$stdoutIwInfo[0];
	}
	
    $classMsgDevicestatus = 'warning';
    if ($interfaceState === 'UP') {
        $classMsgDevicestatus = 'success';
    }

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['ifdown_wlan0'])) {
            // Pressed stop button
            if ($interfaceState === 'UP') {
                $status->addMessage(sprintf(_('Interface is going %s.'), _('down')), 'warning');
                exec('sudo /usr/local/sbin/switchClientState.sh down');
                $status->addMessage(sprintf(_('Interface is now %s.'), _('down')), 'success');
            } elseif ($interfaceState === 'unknown') {
                $status->addMessage(_('Interface state unknown.'), 'danger');
            } else {
                $status->addMessage(sprintf(_('Interface already %s.'), _('down')), 'warning');
            }
        } elseif (isset($_POST['ifup_wlan0'])) {
            // Pressed start button
            if ($interfaceState === 'DOWN') {
                $status->addMessage(sprintf(_('Interface is going %s.'), _('up')), 'warning');
                exec('sudo /usr/local/sbin/switchClientState.sh up');
                exec('sudo ip -s a f label ' . $raspi_client);
                $status->addMessage(sprintf(_('Interface is now %s.'), _('up')), 'success');
            } elseif ($interfaceState === 'unknown') {
                $status->addMessage(_('Interface state unknown.'), 'danger');
            } else {
                $status->addMessage(sprintf(_('Interface already %s.'), _('up')), 'warning');
            }
        } else {
            $status->addMessage(sprintf(_('Interface is %s.'), strtolower($interfaceState)), $classMsgDevicestatus);
        }
    }
 

    echo renderTemplate(
        "dashboard", compact(
            "status",
            "ipv4Addrs",
            "ipv4Netmasks",
            "ipv6Addrs",
            "macAddr",
            "strRxPackets",
            "strRxBytes",
            "strTxPackets",
            "strTxBytes",
            "txPower",
			"clientinfo"
        )
    );
    $extraFooterScripts[] = array('src'=>'app/js/dashboardchart.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'app/js/linkquality.js', 'defer'=>false);
}


/**
 * Get a human readable data size string from a number of bytes.
 *
 * @param  long $numbytes  The number of bytes.
 * @param  int  $precision The number of numbers to round to after the dot/comma.
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
