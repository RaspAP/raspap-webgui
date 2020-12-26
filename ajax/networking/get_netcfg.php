<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';

$interface = $_GET['iface'];

if (isset($interface)) {
    // fetch dnsmasq.conf settings for interface
    exec('cat '. RASPI_DNSMASQ_PREFIX.$interface.'.conf', $return);
    $conf = ParseConfig($return);

    $dhcpdata['DHCPEnabled'] = empty($conf) ? false : true;
    $arrRange = explode(",", $conf['dhcp-range']);
    $dhcpdata['RangeStart'] = $arrRange[0];
    $dhcpdata['RangeEnd'] = $arrRange[1];
    $dhcpdata['RangeMask'] = $arrRange[2];
    $dhcpdata['leaseTime'] = $arrRange[3];
    $dhcpHost = $conf["dhcp-host"];
    $dhcpHost = empty($dhcpHost) ? [] : $dhcpHost;
    $dhcpdata['dhcpHost'] = is_array($dhcpHost) ? $dhcpHost : [ $dhcpHost ];
    $upstreamServers = is_array($conf['server']) ? $conf['server'] : [ $conf['server'] ];
    $dhcpdata['upstreamServersEnabled'] = empty($conf['server']) ? false: true;
    $dhcpdata['upstreamServers'] = array_filter($upstreamServers);
    preg_match('/([0-9]*)([a-z])/i', $dhcpdata['leaseTime'], $arrRangeLeaseTime);
    $dhcpdata['leaseTime'] = $arrRangeLeaseTime[1];
    $dhcpdata['leaseTimeInterval'] = $arrRangeLeaseTime[2];
    if (isset($conf['dhcp-option'])) {
        $arrDns = explode(",", $conf['dhcp-option']);
        if ($arrDns[0] == '6') {
            if (count($arrDns) > 1) {
                $dhcpdata['DNS1'] = $arrDns[1];
            }
            if (count($arrDns) > 2) {
                $dhcpdata['DNS2'] = $arrDns[2];
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
    $dhcpdata['Metric'] = $metric[1];
    $dhcpdata['StaticIP'] = strpos($static_ip[1],'/') ?  substr($static_ip[1], 0, strpos($static_ip[1],'/')) : $static_ip[1];
    $dhcpdata['SubnetMask'] = cidr2mask($static_ip[1]);
    $dhcpdata['StaticRouters'] = $static_routers[1];
    $dhcpdata['StaticDNS'] = $static_dns[1];
    $dhcpdata['FallbackEnabled'] = empty($fallback) ? false: true;

    echo json_encode($dhcpdata);
}
