<?php

use RaspAP\Networking\UdevRulePrototypes;
use RaspAP\Networking\DeviceScanner;
use RaspAP\Networking\NetDeviceService;
use RaspAP\Networking\DeviceStatus;

require_once 'includes/internetRoute.php';
require_once 'includes/functions.php';

/**
 * Displays a networking summary, available network devices,
 * mobile data configuration, WLAN routing settings and
 * network diagnostic tools
 */
function DisplayNetworkingConfig(&$extraFooterScripts)
{
    $status = new \RaspAP\Messages\StatusMessage;

    exec("ls /sys/class/net | grep -v lo", $interfaces);
    $hostapdConfig = RASPI_CONFIG.'/hostapd.ini';
    $routingConfig = RASPI_CONFIG.'/networking/routing.ini';
    $arrHostapdConf = [];
    $arrRoutingConf = [];
    $staInterface = $_SESSION['wifi_client_interface'] ?? null;
    $routeInfo = getRouteInfo(true);
    $routeInfoRaw = getRouteInfoRaw();
    $state = "disabled";

    if (file_exists($hostapdConfig)) {
        $arrHostapdConf = parse_ini_file($hostapdConfig);
    }
    if (file_exists($routingConfig)) {
        $arrRoutingConf = parse_ini_file($routingConfig);
    }
    $bridgedEnabled = $arrHostapdConf['BridgedEnable'] ?? null;
    $wifiAPEnabled = $arrHostapdConf['WifiAPEnable'] ?? null;
    $routingEnabled = $arrRoutingConf['WifiRouting'] ?? null;
    $configAdded = $arrRoutingConf['ConfigAdded'] ?? null;
    $outputInterface = $arrRoutingConf['OutputInterface'] ?? null;
    $serviceStatus = $routingEnabled == 1 ? "up" : "warn";
    $serviceLabel = $routingEnabled == 1 ? "active" : "inactive";

    handleBtnActions($status);

    if (isset($staInterface)) {
        exec('iwgetid '. $staInterface. ' -r', $wifiNetworkID);
        if (!empty($wifiNetworkID[0])) {
            $state = "";
        } else {
            $staInterface = _("not configured");
        }
    } else {
        $staInterface = _("not configured");
    }

    // populate mobile data keys, set defaults
    $mobileData = [];
    $configPath = RASPI_MOBILEDATA_CONFIG;

    if (file_exists($configPath)) {
        $mobileData = parse_ini_file($configPath) ?: [];
    }
    $expectedKeys = ['pin', 'apn', 'apn_user', 'apn_pw', 'router_user', 'router_pw'];
    foreach ($expectedKeys as $key) {
        $mobileData[$key] = $mobileData[$key] ?? '';
    }

    try {
        $udev = new UdevRulePrototypes(RASPI_CLIENT_CONFIG_PATH);
        $deviceTypes = $udev->getPrototypes();
    } catch (\RuntimeException $e) {
        error_log("Failed to load UDEV rules: " . $e->getMessage());
        $deviceTypes = []; // fallback
    }
    $deviceTypes = array_column($deviceTypes, null, 'type');

    $scanner = new DeviceScanner();
    $devices = $scanner->listDevices();

    $service = new NetDeviceService();
    $statusService = new DeviceStatus();

    foreach ($devices as &$device) {
        $deviceStatus = $statusService->getStatus($device);
        $device = array_merge($device, $deviceStatus);
    }

    $deviceData = $service->getDeviceTableData(
        $devices,
        $deviceTypes,
        $_SESSION['udevrules']['udev_rules_file'] ?? RASPI_USER_UDEV_RULES
    );

    echo renderTemplate("networking", compact(
        "status",
        "interfaces",
        "routeInfo",
        "routeInfoRaw",
        "bridgedEnabled",
        "deviceData",
        "mobileData",
        "arrRoutingConf",
        "staInterface",
        "state",
        "routingEnabled",
        "configAdded",
        "outputInterface",
        "serviceStatus",
        "serviceLabel"
        )
    );
    $extraFooterScripts[] = array('src'=>'app/js/vendor/speedtestUI.js', 'defer'=>false);
}

/**
 * Handles form button actions
 * @param object $status
 */
function handleBtnActions($status)
{
    if (!RASPI_MONITOR_ENABLED) {
        $staInterface = $_POST['staInterface'] ?? null;
        $outputInterface = $_POST['outputInterface'] ?? null;
        $optConfigOutput = $_POST['optConfigOutput'] ?? null;
        $outputIfaceEscaped = escapeshellarg($outputInterface);
        $staIfaceEscaped = escapeshellarg($staInterface);

        if (isset($_POST['SaveWlanSettings'])) {
            if (isset($outputInterface)) {
                saveWlanConfigOpt($status, $optConfigOutput, $outputInterface);
                saveWlanRoutingConfig($status, $staInterface, $outputInterface, $optConfigOutput, true);
            }
        } elseif (isset($_POST['StartWlanRouting'])) {
            if (isset($_POST['outputInterface'])) {
                $status->addMessage(sprintf(_('Attempting to enable routing between %s and %s interfaces'), $staInterface, $outputInterface), 'info');
                saveWlanConfigOpt($status, $optConfigOutput, $outputInterface);
                exec("sudo ".RASPI_CONFIG."/networking/toggle-routing.sh $staIfaceEscaped $outputIfaceEscaped add", $return);
                foreach ($return as $line) {
                    $status->addMessage($line, 'info');
                }
                restartDnsmasq($status);
                saveWlanRoutingConfig($status, $staInterface, $outputInterface, $optConfigOutput, false, true);
            }
        } elseif (isset($_POST['RestartWlanRouting'])) {
                $status->addMessage(sprintf(_('Attempting to restart routing between %s and %s interfaces'), $staInterface, $outputInterface), 'info');
                restartDnsmasq($status);
        } elseif (isset($_POST['StopWlanRouting'])) {
            if (isset($_POST['outputInterface'])) {
                $status->addMessage(sprintf(_('Attempting to disable routing between %s and %s interfaces'), $staInterface, $outputInterface), 'info');
                exec("sudo ".RASPI_CONFIG."/networking/toggle-routing.sh $staIfaceEscaped $outputIfaceEscaped delete", $return);
                foreach ($return as $line) {
                    $status->addMessage($line, 'info');
                }
                saveWlanRoutingConfig($status, $staInterface, $outputInterface, $optConfigOutput, false, false);
                restartDnsmasq($status);
            }
        }
    }
}

/**
 * Creates or removes a DHCP and dnsmasq default configuration
 * to support WLAN routing
 *
 * @param object $status
 * @param string $optConfigOutput
 * @param string $outputInterface
 */
function saveWlanConfigOpt($status, $optConfigOutput, $outputInterface)
{
    if ($optConfigOutput) {
        if (isPredictableIfaceName($outputInterface)) {
            $outputDefault = substr($outputInterface, 0, 3);
        } else {
            $outputDefault = $outputInterface;
        }
        $result = saveDHCPConfigEx($outputInterface, $outputDefault, $status);
        if ($result == 0) {
            saveDnsmasqConfig($outputInterface, $outputDefault, $status);
        }
    } else {
        removeDHCPConfig($outputInterface, $status);
        removeDnsmasqConfig($outputInterface, $status);
    }
}

/**
 * Persists WLAN routing settings to networking/routing.ini
 * Additionally saves or removes an associated DHCP and dnsmasq config
 * @param object $status
 * @param string $staInterface
 * @param string $outputInterface
 * @param string $optConfigOutput
 * @param boolean $statusOut
 * @param boolean|null $routingEnabled
 * @return void
 */
function saveWlanRoutingConfig($status, $staInterface, $outputInterface, $optConfigOutput, $statusOut, $routingEnabled = null)
{
    // persist user config to /etc/raspap
    $cfg = [];
    $cfg['WifiInterface'] = $staInterface;
    $cfg['OutputInterface'] = $outputInterface;
    $cfg['ConfigAdded'] = $optConfigOutput;
    if (isset($routingEnabled)) {
        $cfg['WifiRouting'] = $routingEnabled;
    }
    $return = write_php_ini($cfg, RASPI_CONFIG.'/networking/routing.ini');
    if ($statusOut) {
        if ($return) {
            $status->addMessage('WLAN routing configuration saved', 'success');
        } else {
            $status->addMessage('Unable to save WLAN routing configuration', 'danger');
        }
    }
}

/**
 * Saves a DHCP configuration
 *
 * @param string $outputInterface
 * @param string $outputDefault
 * @param object $status
 */
function saveDHCPConfigEx($outputInterface, $outputDefault, $status)
{
    // Set DHCP values from system config, fallback to default if undefined
    $jsonData = json_decode(getNetConfig($outputInterface), true);

    $default = getDefaultNetValue('dhcp', $outputDefault, 'static ip_address');
    if (isset($default)) {
        $ip_address = ($jsonData['StaticIP'] == '') ? getDefaultNetValue('dhcp', $outputDefault, 'static ip_address') : $jsonData['StaticIP'];
        $domain_name_server = ($jsonData['StaticDNS'] == '') ? getDefaultNetValue('dhcp', $outputDefault, 'static domain_name_server') : $jsonData['StaticDNS'];
        $routers = ($jsonData['StaticRouters'] == '') ? getDefaultNetValue('dhcp', $outputDefault, 'static routers') : $jsonData['StaticRouters'];
        $netmask = ($jsonData['SubnetMask'] == '' || $jsonData['SubnetMask'] == '0.0.0.0') ? getDefaultNetValue('dhcp', $outputDefault, 'subnetmask') : $jsonData['SubnetMask'];
        $ip_address .= (!preg_match('/.*\/\d+/', $ip_address)) ? '/'.mask2cidr($netmask) : null;

        $cfg = [ '# RaspAP '.$outputInterface.' configuration' ];
        $cfg[] = 'interface '.$outputInterface;
        $cfg[] = 'static ip_address='.$ip_address;
        $cfg[] = 'static routers='.$routers;
        $cfg[] = 'static domain_name_server='.$domain_name_server;
        if (!is_null($jsonData['Metric'])) {
            $cfg[] = 'metric '.$jsonData['Metric'];
        }

        $dhcp_cfg = file_get_contents(RASPI_DHCPCD_CONFIG);
        $arrHostapdConf = file_exists(RASPI_CONFIG.'/hostapd.ini') ? parse_ini_file(RASPI_CONFIG.'/hostapd.ini') : [];
        if (($arrHostapdConf['BridgedEnable'] ?? 0) == 1 || ($arrHostapdConf['WifiAPEnable'] ?? 0) == 1) {
            $dhcp_cfg = join(PHP_EOL, $cfg);
            $status->addMessage(sprintf(_('DHCP configuration for %s enabled'), $outputInterface), 'success');
        } elseif (!preg_match('/^interface\s'.$outputInterface.'$/m', $dhcp_cfg)) {
            $cfg[] = PHP_EOL;
            $cfg = join(PHP_EOL, $cfg);
            $dhcp_cfg = removeDHCPIface($dhcp_cfg, 'br0');
            $dhcp_cfg = removeDHCPIface($dhcp_cfg, 'uap0');
            $dhcp_cfg .= $cfg;
            $status->addMessage(sprintf(_('DHCP configuration for %s added'), $outputInterface), 'success');
        } else {
            $cfg = join(PHP_EOL, $cfg);
            $dhcp_cfg = removeDHCPIface($dhcp_cfg, 'br0');
            $dhcp_cfg = removeDHCPIface($dhcp_cfg, 'uap0');
            $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$outputInterface.'\s.*?(?=\s*^\s*$)/ms', $cfg, $dhcp_cfg, 1);
            $status->addMessage(sprintf(_('DHCP configuration for %s updated'), $outputInterface), 'success');
        }
        file_put_contents("/tmp/dhcpddata", $dhcp_cfg);
        system('sudo cp /tmp/dhcpddata '.RASPI_DHCPCD_CONFIG, $return);
        return $return;
    } else {
        $status->addMessage(sprintf(_('No default DHCP configuration exists for the %s interface'), $outputInterface), 'warning');
        $status->addMessage(sprintf(_('Configure a static IP and DHCP for this interface in DHCP Server settings'), $outputInterface), 'warning');
        return 1;
    }
}

/**
 * Removes a previously-written per-interface block from a dhcpcd.conf
 * string, as written by saveDHCPConfigEx(). Pure string transform.
 *
 * @param string $dhcp_cfg
 * @param string $iface
 * @return string
 */
function removeDHCPIface($dhcp_cfg, $iface)
{
    return preg_replace('/^#\sRaspAP\s'.preg_quote($iface, '/').'\s.*?(?=\s*^\s*$)/ms', '', $dhcp_cfg, 1);
}

/**
 * Removes a previously-saved per-interface DHCP configuration
 *
 * @param string $outputInterface
 * @param object $status
 */
function removeDHCPConfig($outputInterface, $status)
{
    $dhcp_cfg = file_get_contents(RASPI_DHCPCD_CONFIG);
    $updated = removeDHCPIface($dhcp_cfg, $outputInterface);
    if ($updated === $dhcp_cfg) {
        return;
    }
    file_put_contents("/tmp/dhcpddata", $updated);
    system('sudo cp /tmp/dhcpddata '.RASPI_DHCPCD_CONFIG, $return);
    if ($return === 0) {
        $status->addMessage(sprintf(_('DHCP configuration for %s removed'), $outputInterface), 'success');
    } else {
        $status->addMessage(sprintf(_('Failed to remove DHCP configuration for %s'), $outputInterface), 'danger');
    }
}

/**
 * Saves a dnsmasq configuration
 *
 * @param string $outputInterface
 * @param string $outputDefault
 * @param object $status
 */
function saveDnsmasqConfig($outputInterface, $outputDefault, $status)
{
    // Fetch dnsmasq values from system config, fallback to default if undefined
    $syscfg = parse_ini_file(RASPI_DNSMASQ_PREFIX.$outputInterface.'.conf', false, INI_SCANNER_RAW) ?: [];
    $dhcp_range = ($syscfg['dhcp-range'] ?? '') == '' ? getDefaultNetValue('dnsmasq', $outputDefault, 'dhcp-range') : $syscfg['dhcp-range'];
    $cfg = [ '# RaspAP '.$outputInterface.' configuration' ];
    $cfg[] = 'interface='.$outputInterface;
    $cfg[] = 'dhcp-range='.$dhcp_range;
    $cfg[] = PHP_EOL;
    $cfg = join(PHP_EOL, $cfg);
    $status->addMessage(sprintf(_('Dnsmasq configuration for %s added'), $outputInterface), 'success');
    file_put_contents("/tmp/dnsmasqdata", $cfg);
    system('sudo cp /tmp/dnsmasqdata '.RASPI_DNSMASQ_PREFIX.$outputInterface.'.conf', $return);
}

/**
 * Removes a previously-saved per-interface dnsmasq configuration
 *
 * @param string $outputInterface
 * @param object $status
 */
function removeDnsmasqConfig($outputInterface, $status)
{
    $file = RASPI_DNSMASQ_PREFIX.$outputInterface.'.conf';
    if (!file_exists($file)) {
        return;
    }
    exec('sudo rm '.escapeshellarg($file), $out, $ret);
    if ($ret === 0) {
        $status->addMessage(sprintf(_('Dnsmasq configuration for %s removed'), $outputInterface), 'success');
    } else {
        $status->addMessage(sprintf(_('Failed to remove dnsmasq configuration for %s'), $outputInterface), 'danger');
    }
}

/*
 * Restarts the dnsmasq.service
 * @param object $status
 */
function restartDnsmasq(&$status)
{
    $return = shell_exec("sudo /bin/systemctl restart dnsmasq.service");
    if ($return == 0) {
        $status->addMessage('Successfully restarted dnsmasq', 'success');
    } else {
        $status->addMessage('Failed to restart dnsmasq', 'danger');
    }
}
