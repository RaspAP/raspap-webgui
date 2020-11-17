<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';

$interface = $_GET['iface'];

if (isset($interface)) {
    exec('cat '. RASPI_DNSMASQ_PREFIX.$interface.'.conf', $return);
    $conf = ParseConfig($return);

    // populate data 
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
    $dhcpdata['upstreamServers'] = array_filter($upstreamServers);
    $dhcpdata['upstreamServersEnabled'] = empty($conf['server']) ? false: true;
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
    echo json_encode($dhcpdata);
}
