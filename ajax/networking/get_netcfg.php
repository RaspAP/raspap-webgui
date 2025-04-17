<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

$interface = $_POST['iface'];

if (isset($interface)) {
    // fetch dnsmasq.conf settings for interface
    exec('cat '. escapeshellarg(RASPI_DNSMASQ_PREFIX.$interface.'.conf'), $return);
    $conf = ParseConfig($return);

    $dhcpdata['DHCPEnabled'] = empty($conf) ? false : true;
    if (is_string($conf['dhcp-range'])) {
        $arrRange = explode(",", $conf['dhcp-range']);
    } else {
        $arrRange = explode(",", $conf['dhcp-range'][0]);
    }
    $dhcpdata['RangeStart'] = $arrRange[0] ?? null;
    $dhcpdata['RangeEnd'] = $arrRange[1] ?? null;
    $dhcpdata['RangeMask'] = $arrRange[2] ?? null;
    $dhcpdata['leaseTime'] = $arrRange[3] ?? null;
    $dhcpHost = $conf["dhcp-host"] ?? null;
    $dhcpHost = empty($dhcpHost) ? [] : $dhcpHost;
    $dhcpdata['dhcpHost'] = is_array($dhcpHost) ? $dhcpHost : [ $dhcpHost ];
    $upstreamServers = is_array($conf['server'] ?? null) ? $conf['server'] : [ $conf['server'] ?? '' ];
    $dhcpdata['upstreamServersEnabled'] = empty($conf['server']) ? false: true;
    $dhcpdata['upstreamServers'] = array_filter($upstreamServers);
    preg_match('/([0-9]*)([a-z])/i', $dhcpdata['leaseTime'], $arrRangeLeaseTime);
    $dhcpdata['leaseTime'] = $arrRangeLeaseTime[1];
    $dhcpdata['leaseTimeInterval'] = $arrRangeLeaseTime[2];
    if (isset($conf['dhcp-option'])) {
        $arrDns = explode(",", $conf['dhcp-option']);
        if ($arrDns[0] == '6') {
            if (count($arrDns) > 1) {
                $dhcpdata['DNS1'] = $arrDns[1] ?? null;
            }
            if (count($arrDns) > 2) {
                $dhcpdata['DNS2'] = $arrDns[2] ?? null;
            }
        }
    }

    // fetch dhcpcd.conf settings for interface
    $conf = file_get_contents(RASPI_DHCPCD_CONFIG);
    preg_match('/^#\sRaspAP\s'.$interface.'\s.*?(?=\s*+$)/ms', $conf, $matched);
    preg_match('/metric\s(\d*)/', $matched[0], $metric);
    preg_match('/static\sip_address=(.*)/', $matched[0], $static_ip);
    preg_match('/static\srouters=(.*)/', $matched[0], $static_routers);
    preg_match('/static\sdomain_name_server=(.*)/', $matched[0], $static_dns);
    preg_match('/fallback\sstatic_'.$interface.'/', $matched[0], $fallback);
    preg_match('/(?:no)?gateway/', $matched[0], $gateway);
    preg_match('/nohook\swpa_supplicant/', $matched[0], $nohook_wpa_supplicant);
    $dhcpdata['Metric'] = $metric[1] ?? null;
    $dhcpdata['StaticIP'] = isset($static_ip[1]) && strpos($static_ip[1], '/') !== false
        ? substr($static_ip[1], 0, strpos($static_ip[1], '/'))
        : ($static_ip[1] ?? '');
    $dhcpdata['SubnetMask'] = cidr2mask($static_ip[1] ?? '');
    $dhcpdata['StaticRouters'] = $static_routers[1] ?? null;
    $dhcpdata['StaticDNS'] = $static_dns[1] ?? null;
    $dhcpdata['FallbackEnabled'] = empty($fallback) ? false: true;
    $dhcpdata['DefaultRoute'] = $gateway[0] == "gateway";
    $dhcpdata['NoHookWPASupplicant'] = ($nohook_wpa_supplicant[0] ?? '') == "nohook wpa_supplicant";
    echo json_encode($dhcpdata);
}
