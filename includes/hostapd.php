<?php

use RaspAP\Networking\Hotspot\HostapdManager;
use RaspAP\Networking\Hotspot\HotspotService;
use RaspAP\Networking\Hotspot\WiFiManager;
use RaspAP\Networking\Hotspot\DhcpcdManager;
use RaspAP\Messages\StatusMessage;
use RaspAP\System\Sysinfo;

$wifi = new WiFiManager();
$wifi->getWifiInterface();

/**
 * Initialize hostapd values, display interface
 *
 */
function DisplayHostAPDConfig()
{
    
    $hostapd = new HostapdManager();
    $hotspot = new HotspotService();
    $status = new StatusMessage();
    $dhcpcd = new DhcpcdManager();
    $system = new Sysinfo();
    $operatingSystem = $system->operatingSystem();

    \RaspAP\UI\LiveForm::loadStatusMessages($status);

    $interface = $_SESSION['ap_interface'];

    // set hostapd defaults
    $arr80211Standard = $hotspot->get80211Standards();
    $arrSecurity = $hotspot->getSecurityModes();
    $arrEncType = $hotspot->getEncTypes();
    $arr80211w = $hotspot->get80211wOptions();
    $ifaces = $hotspot->getInterfaces();
    
    // get current hostapd config
    $arrHostapdConf = $hotspot->getHostapdIni();
    
    // get country codes for select input and set default reg domain
    $languageCode = strtok($_SESSION['locale'], '_');
    $countryCodes = getCountryCodes($languageCode);
    $reg_domain = 'GB';
    try {
        $reg_domain = $hotspot->getRegDomain();
    } catch (RuntimeException $e) {
        error_log('Failed to get regulatory domain: ' . $e->getMessage());
    }
        
    // get current txpower settings and select options
    $txpower = $hotspot->getTxPower($interface);
    $arrTxPower = getDefaultNetOpts('txpower','dbm');
    
    // check if wifi client interface is connected to a network
    // used conditionally enables toggles
    $managedModeEnabled = false;
    if (isset($_SESSION['wifi_client_interface'])) {
        exec('iwgetid '.escapeshellarg($_SESSION['wifi_client_interface']). ' -r', $wifiNetworkID);
        if (!empty($wifiNetworkID[0])) {
            $managedModeEnabled = true;
        }
    }

    // parse hostapd configuration
    try {
        $arrConfig = $hostapd->getConfig();
    } catch (\RuntimeException $e) {
        error_log('Error: ' . $e->getMessage());
    }

    // bridge configuration
    if (!empty($arrHostapdConf['BridgedEnable']) && (int)$arrHostapdConf['BridgedEnable'] === 1) {
        $iface = 'br0';
        $bridgeConfig = $dhcpcd->getInterfaceConfig($iface);

        if (is_array($bridgeConfig) && !empty($bridgeConfig)) {
            $arrConfig['bridgeStaticIP'] = !empty($bridgeConfig['StaticIP'])
                ? $bridgeConfig['StaticIP']
                : '192.168.1.10';

            $arrConfig['bridgeNetmask'] = !empty($bridgeConfig['SubnetMask'])
                ? mask2cidr($bridgeConfig['SubnetMask'])
                : '24';

            $arrConfig['bridgeGateway'] = !empty($bridgeConfig['StaticRouters'])
                ? $bridgeConfig['StaticRouters']
                : '192.168.1.1';

            $arrConfig['bridgeDNS'] = !empty($bridgeConfig['StaticDNS'])
                ? $bridgeConfig['StaticDNS']
                : '192.168.1.1';
        }
    }

    // fetch hostapd logs if enabled
    $logOutput = [];
    if ((string)$arrHostapdConf['LogEnable'] === "1") {
        $logResult = $hotspot->getHostapdLogs(5000);
        if ($logResult['success']) {
            $joined = implode("\n", $logResult['logs']);
            $limited = getLogLimited('', $joined);
            $logOutput = explode("\n", $limited);
        }
    }

    // assign disassoc_low_ack boolean if value is set
    $arrConfig['disassoc_low_ack_bool'] = isset($arrConfig['disassoc_low_ack']) ? 1 : 0;
    $hostapdstatus = $system->hostapdStatus();
    $serviceStatus = $hostapdstatus[0] == 0 ? "down" : "up";

    $interfaces = [];
    foreach ($ifaces as $iface) {
        $ifaceServices = [];
        if ($iface === $_SESSION['ap_interface']) {
            $ifaceServices[] = 'AP';
        }
        if ($iface === $_SESSION['wifi_client_interface']) {
            $ifaceServices[] = 'Client';
        }
        $label = !empty($ifaceServices) ? $iface . ' (' . implode(', ', $ifaceServices) . ')' : $iface;
        $interfaces[$iface] = $label;
    }

    echo renderTemplate(
        "hostapd", compact(
            "status",
            "serviceStatus",
            "hostapdstatus",
            "managedModeEnabled",
            "interfaces",
            "arrConfig",
            "arr80211Standard",
            "arrSecurity",
            "arrEncType",
            "arr80211w",
            "arrTxPower",
            "txpower",
            "arrHostapdConf",
            "operatingSystem",
            "countryCodes",
            "logOutput"
        )
    );
}

