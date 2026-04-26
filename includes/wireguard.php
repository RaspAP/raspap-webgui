<?php

require_once 'includes/config.php';

use RaspAP\Networking\Hotspot\WiFiManager;

$wifi = new WiFiManager();
$wifi->getWifiInterface();

/**
 * Displays wireguard server & peer configuration
 */
function DisplayWireGuardConfig()
{
    $parseFlag = true;
    $status = new \RaspAP\Messages\StatusMessage;

    \RaspAP\UI\LiveForm::loadStatusMessages($status);

    // fetch server config
    exec('sudo cat '. RASPI_WIREGUARD_CONFIG, $return);
    $conf = ParseConfig($return, $parseFlag);
    $wg_srvpubkey = exec('sudo cat '. RASPI_WIREGUARD_PATH .'wg-server-public.key', $return);
    $wg_srvport = ($conf['ListenPort'] ?? '') === ''
        ? getDefaultNetValue('wireguard','server','ListenPort')
        : $conf['ListenPort'];
    $wg_srvipaddress = ($conf['Address'] == '') ? getDefaultNetValue('wireguard','server','Address') : $conf['Address'];
    $wg_srvdns = ($conf['DNS'] == '') ? getDefaultNetValue('wireguard','server','DNS') : $conf['DNS'];
    if (is_array($wg_srvdns)) {
        $wg_srvdns = implode(', ', $wg_srvdns);
    }
    $wg_peerpubkey = exec('sudo cat '. RASPI_WIREGUARD_PATH .'wg-peer-public.key', $return);
    if (sizeof($conf) > 0) {
        $wg_senabled = true;
    }

    // fetch client config
    exec('sudo cat '. RASPI_WIREGUARD_PATH.'client.conf', $preturn);
    $conf = ParseConfig($preturn, $parseFlag);
    $wg_pipaddress = ($conf['Address'] == '') ? getDefaultNetValue('wireguard','peer','Address') : $conf['Address'];
    $wg_plistenport = ($conf['ListenPort'] == '') ? getDefaultNetValue('wireguard','peer','ListenPort') : $conf['ListenPort'];
    $wg_pendpoint = ($conf['Endpoint'] == '') ? getDefaultNetValue('wireguard','peer','Endpoint') : $conf['Endpoint'];
    $wg_pallowedips = ($conf['AllowedIPs'] == '') ? getDefaultNetValue('wireguard','peer','AllowedIPs') : $conf['AllowedIPs'];
    $wg_pkeepalive = ($conf['PersistentKeepalive'] == '') ? getDefaultNetValue('wireguard','peer','PersistentKeepalive') : $conf['PersistentKeepalive'];
    $wg_penabled = false;
    if (sizeof($conf) > 0) {
        $wg_penabled = true;
    }

    // fetch service status
    exec('ip link show wg0 2>/dev/null', $wgstatus, $wg_return);
    $serviceStatus = ($wg_return === 0) ? "up" : "down";
    $wg_state = ($wg_return === 0);
    $public_ip = get_public_ip();

    // fetch uploaded file configs
    exec("sudo ls ".RASPI_WIREGUARD_PATH, $clist);
    $configs = preg_grep('/^((?!wg0).)*\.conf/', $clist);
    exec("sudo readlink ".RASPI_WIREGUARD_CONFIG." | xargs basename", $ret);
    $conf_default = empty($ret) ? "none" : $ret[0];

    // fetch wg log
    exec('sudo chmod o+r /tmp/wireguard.log');
    $log = '';
    $optLogEnable = 0;
    if (file_exists('/tmp/wireguard.log') && filesize('/tmp/wireguard.log') > 0) {
        $optLogEnable = 1;
        $log = file_get_contents('/tmp/wireguard.log');
    }
    $peer_id = $peer_id ?? "1";

    // fetch available interfaces
    exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);
    sort($interfaces);

    echo renderTemplate(
        "wireguard", compact(
            "status",
            "wg_state",
            "serviceStatus",
            "public_ip",
            "interfaces",
            "optLogEnable",
            "peer_id",
            "wg_srvpubkey",
            "wg_srvport",
            "wg_srvipaddress",
            "wg_srvdns",
            "wg_senabled",
            "wg_penabled",
            "wg_pipaddress",
            "wg_plistenport",
            "wg_peerpubkey",
            "wg_pendpoint",
            "wg_pallowedips",
            "wg_pkeepalive",
            "configs",
            "conf_default",
            "log"
        )
    );
}
