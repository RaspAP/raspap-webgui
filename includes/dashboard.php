<?php

require_once 'includes/config.php';
require_once 'includes/wifi_functions.php';
require_once 'includes/functions.php';

/**
 * Displays the dashboard
 */
function DisplayDashboard()
{
    // instantiate RaspAP objects
    $status = new \RaspAP\Messages\StatusMessage;
    $system = new \RaspAP\System\Sysinfo;
    $pluginManager = \RaspAP\Plugins\PluginManager::getInstance();

    $hostname = $system->hostname();
    $revision = $system->rpiRevision();
    $hostapd = $system->hostapdStatus();
    $adblock = $system->adBlockStatus();

    getWifiInterface();

    exec('ip a show '.$_SESSION['ap_interface'], $stdoutIp);
    $stdoutIpAllLinesGlued = implode(" ", $stdoutIp);
    $stdoutIpWRepeatedSpaces = preg_replace('/\s\s+/', ' ', $stdoutIpAllLinesGlued);

    preg_match('/link\/ether ([0-9a-f:]+)/i', $stdoutIpWRepeatedSpaces, $matchesMacAddr) || $matchesMacAddr[1] = _('No MAC Address Found');
    $macAddr = $matchesMacAddr[1];

    $ipv4Addrs = '';
    $ipv4Netmasks = '';
    if (!preg_match_all('/inet (\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})\/([0-3][0-9])/i', $stdoutIpWRepeatedSpaces, $matchesIpv4AddrAndSubnet, PREG_SET_ORDER)) {
        $ipv4Addrs = _('None');
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


    define('SSIDMAXLEN', 32);
    exec('iw dev ' .$_SESSION['ap_interface']. ' info ', $stdoutIw);
    $stdoutIwAllLinesGlued = implode('+', $stdoutIw);
    $stdoutIwWRepSpaces = preg_replace('/\s\s+/', ' ', $stdoutIwAllLinesGlued);

    preg_match('/Connected to (([0-9A-Fa-f]{2}:){5}([0-9A-Fa-f]{2}))/', $stdoutIwWRepSpaces, $matchesBSSID) || $matchesBSSID[1] = '';
    $connectedBSSID = $matchesBSSID[1];
    $connectedBSSID = empty($connectedBSSID) ? "-" : $connectedBSSID;

    $wlanHasLink = false;
    if ($interfaceState === 'UP') {
        $wlanHasLink = true;
    }

    if (!preg_match('/SSID: ([^+]{1,'.SSIDMAXLEN.'})/', $stdoutIwWRepSpaces, $matchesSSID)) {
        $wlanHasLink = false;
        $matchesSSID[1] = 'None';
    }
    $connectedSSID = str_replace('\x20', '', $matchesSSID[1]);

    preg_match('/freq: (\d+)/i', $stdoutIwWRepSpaces, $matchesFrequency) || $matchesFrequency[1] = '';
    $frequency = $matchesFrequency[1].' MHz';

    preg_match('/signal: (-?[0-9]+ dBm)/i', $stdoutIwWRepSpaces, $matchesSignal) || $matchesSignal[1] = '';
    $signalLevel = $matchesSignal[1];
    $signalLevel = empty($signalLevel) ? "-" : $signalLevel;

    preg_match('/tx bitrate: ([0-9\.]+ [KMGT]?Bit\/s)/', $stdoutIwWRepSpaces, $matchesBitrate) || $matchesBitrate[1] = '';
    $bitrate = $matchesBitrate[1];
    $bitrate = empty($bitrate) ? "-" : $bitrate;

    // txpower is now displayed on iw dev(..) info command, not on link command.
    exec('iw dev '.$_SESSION['wifi_client_interface'].' info ', $stdoutIwInfo);
    $stdoutIwInfoAllLinesGlued = implode(' ', $stdoutIwInfo);
    $stdoutIpInfoWRepSpaces = preg_replace('/\s\s+/', ' ', $stdoutIwInfoAllLinesGlued);

    preg_match('/txpower ([0-9\.]+ dBm)/i', $stdoutIpInfoWRepSpaces, $matchesTxPower) || $matchesTxPower[1] = '';
    $txPower = $matchesTxPower[1];

    $strLinkQuality = 0;
    if ($signalLevel > -100 && $wlanHasLink) {
        if ($signalLevel >= 0) {
            $strLinkQuality = 100;
        } else {
            $strLinkQuality = 100 + intval($signalLevel);
        }
    }

    $wlan0up = false;
    $classMsgDevicestatus = 'warning';
    if ($interfaceState === 'UP') {
        $wlan0up = true;
        $classMsgDevicestatus = 'success';
    }

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['ifdown_wlan0'])) {
            // Pressed stop button
            if ($interfaceState === 'UP') {
                $status->addMessage(sprintf(_('Interface is going %s.'), _('down')), 'warning');
                exec('sudo ip link set '.$_SESSION['ap_interface'].' down');
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
                exec('sudo ip link set ' .$_SESSION['ap_interface']. ' up');
                exec('sudo ip -s a f label ' .$_SESSION['ap_interface']);
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
    }
    $arrHostapdConf = parse_ini_file(RASPI_CONFIG.'/hostapd.ini');
    $bridgedEnable = $arrHostapdConf['BridgedEnable'];
    $clientInterface = $_SESSION['wifi_client_interface'];
    $apInterface = $_SESSION['ap_interface'];
    $MACPattern = '"([[:xdigit:]]{2}:){5}[[:xdigit:]]{2}"';

    if (getBridgedState()) {
        $moreLink = "hostapd_conf";
        exec('iw dev ' . $apInterface . ' station dump | grep -oE ' . $MACPattern, $clients);
    } else {
        $moreLink = "dhcpd_conf";
        exec('cat ' . RASPI_DNSMASQ_LEASES . '| grep -E $(iw dev ' . $apInterface . ' station dump | grep -oE ' . $MACPattern . ' | paste -sd "|")', $clients);
    }
    $ifaceStatus = $wlan0up ? "up" : "down";

    $plugins = $pluginManager->getInstalledPlugins();
    $bridgedStatus = ($bridgedEnable == 1) ? "active" : "";
    $hostapdStatus = ($hostapd[0] == 1) ?  "active" : "";
    $adblockStatus = ($adblock == true) ?  "active" : "";
    $firewallInstalled = array_filter($plugins, fn($p) => str_ends_with($p, 'Firewall')) ? true : false;
    if (!$firewallInstalled) {
        $firewallStack = '<i class="fas fa-slash fa-stack-1x"></i>';
    }
    echo renderTemplate(
        "dashboard", compact(
            "clients",
            "moreLink",
            "apInterface",
            "clientInterface",
            "ifaceStatus",
            "bridgedStatus",
            "hostapdStatus",
            "adblockStatus",
            "firewallStack",
            "status",
            "ipv4Addrs",
            "ipv4Netmasks",
            "ipv6Addrs",
            "macAddr",
            "connectedSSID",
            "connectedBSSID",
            "frequency",
            "revision",
            "wlan0up"
        )
    );
}

