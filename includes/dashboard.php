<?php

/**
* Show dashboard page.
*/
function DisplayDashboard()
{

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

    preg_match('/txpower ([0-9\.]+ dBm)/i', $stdoutIpInfoWRepSpaces, $matchesTxPower) || $matchesTxPower[1] = '';
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
            exec('sudo ip link set '.RASPI_WIFI_CLIENT_INTERFACE.' down');
            $wlan0up = false;
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

    echo renderTemplate("dashboard", compact(
        "status",
        "ipv4Addrs",
        "ipv4Netmasks",
        "ipv6Addrs",
        "macAddr",
        "strRxPackets",
        "strRxBytes",
        "strTxPackets",
        "strTxBytes",
        "connectedSSID",
        "connectedBSSID",
        "bitrate",
        "signalLevel",
        "txPower",
        "frequency",
        "strLinkQuality",
        "wlan0up"
    ));
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
