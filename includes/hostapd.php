<?php

use RaspAP\Networking\Hotspot\DnsmasqManager;
use RaspAP\Networking\Hotspot\HostapdManager;
use RaspAP\Networking\Hotspot\DhcpcdManager;
use RaspAP\Networking\Hotspot\WiFiManager;
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
    $status = new StatusMessage();
    $system = new Sysinfo();
    $operatingSystem = $system->operatingSystem();

    // set hostapd defaults
    $arr80211Standard = $hostapd->get80211Standards();
    $arrSecurity = $hostapd->getSecurityModes();
    $arrEncType = $hostapd->getEncTypes();
    $arr80211w = $hostapd->get80211wOptions();
    $languageCode = strtok($_SESSION['locale'], '_');
    $countryCodes = getCountryCodes($languageCode);
    $reg_domain = $hostapd->getRegDomain();
    $interfaces = $hostapd->getInterfaces();
    $arrTxPower = getDefaultNetOpts('txpower','dbm');
    $managedModeEnabled = false;

    if (isset($_POST['interface'])) {
        $interface = $_POST['interface'];
    } else {
        $interface = $_SESSION['ap_interface'];
    }
    $txpower = $hostapd->getTxPower($interface);
    $arrHostapdConf = $hostapd->getHostapdIni();

    if (!RASPI_MONITOR_ENABLED) {
         if (isset($_POST['StartHotspot']) || isset($_POST['RestartHotspot'])) {
            $status->addMessage('Attempting to start hotspot', 'info');
            if ($arrHostapdConf['BridgedEnable'] == 1) {
                exec('sudo '.RASPI_CONFIG.'/hostapd/servicestart.sh --interface br0 --seconds 1', $return);
            } elseif ($arrHostapdConf['WifiAPEnable'] == 1) {
                exec('sudo '.RASPI_CONFIG.'/hostapd/servicestart.sh --interface uap0 --seconds 1', $return);
            } else {
                // systemctl expects a unit name like raspap-network-activity@wlan0.service
                $iface_nonescaped = $_POST['interface'];
                if (preg_match('/^[a-zA-Z0-9_-]+$/', $iface_nonescaped)) { // validate interface name
                    exec('sudo '.RASPI_CONFIG.'/hostapd/servicestart.sh --interface ' .$iface_nonescaped. ' --seconds 1', $return);
                } else {
                    throw new \Exception('Invalid network interface');
                }
            }
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['SaveHostAPDSettings'])) {
            saveHostapdConfig($arrSecurity, $arrEncType, $arr80211Standard, $interfaces, $reg_domain, $status);
        } elseif (isset($_POST['StopHotspot'])) {
            $status->addMessage('Attempting to stop hotspot', 'info');
            exec('sudo /bin/systemctl stop hostapd.service', $return);
            exec('sudo systemctl stop "raspap-network-activity@*.service"');
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }
    if (isset($_SESSION['wifi_client_interface'])) {
        exec('iwgetid '.escapeshellarg($_SESSION['wifi_client_interface']). ' -r', $wifiNetworkID);
        if (!empty($wifiNetworkID[0])) {
            $managedModeEnabled = true;
        }
    }

    // process txpower user input 
    if (isset($_POST['txpower'])) {
        if ($_POST['txpower'] != 'auto') {
            $txpower = intval($_POST['txpower']);
            $hostapd->maybeSetTxPower($interface, $txpower, $status);
        } elseif ($_POST['txpower'] == 'auto') {
            $hostapd->maybeSetTxPower($interface, 'auto', $status);
        }
        $txpower = $_POST['txpower'];
    }

    // parse hostapd configuration
    try {
        $arrConfig = $hostapd->getConfig();
    } catch (\RuntimeException $e) {
        error_log('Error: ' . $e->getMessage());
    }

    // assign disassoc_low_ack boolean if value is set
    $arrConfig['disassoc_low_ack_bool'] = isset($arrConfig['disassoc_low_ack']) ? 1 : 0;
    $hostapdstatus = $system->hostapdStatus();
    $serviceStatus = $hostapdstatus[0] == 0 ? "down" : "up";

    // ensure log is writeable 
    exec('sudo /bin/chmod o+r '.RASPI_HOSTAPD_LOG);
    $logdata = getLogLimited(RASPI_HOSTAPD_LOG);

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
            "logdata"
        )
    );
}

/**
 * Validates user input + saves configs for hostapd, dnsmasq & dhcp
 *
 * @param array $wpa_array
 * @param array $enc_types
 * @param array $modes
 * @param string $interface
 * @param string $reg_domain
 * @param object $status
 * @return boolean
 */
function saveHostapdConfig($wpa_array, $enc_types, $modes, $interfaces, $reg_domain, $status)
{
    $hostapd = new HostapdManager();
    $dnsmasq = new DnsmasqManager();
    $dhcpcd = new DhcpcdManager();
    $arrHostapdConf = $hostapd->getHostapdIni();
    $dualAPEnable = false;

    // derive mode states
    $states = $hostapd->deriveModeStates($_POST, $arrHostapdConf);

    // determine base interface (validated or fallback)
    $baseIface = validateInterface($_POST['interface']) ? $_POST['interface'] : RASPI_WIFI_AP_INTERFACE;

    // derive interface roles
    [$apIface, $cliIface, $sessionIface] = $hostapd->deriveInterfaces($baseIface, $states);

    // persist hostapd.ini
    $hostapd->persistHostapdIni($states, $apIface, $cliIface, $arrHostapdConf);

    // store session (compatibility)
    $_SESSION['ap_interface'] = $sessionIface;

    // validate config from $_POST
    $validated = $hostapd->validate($_POST, $wpa_array, $enc_types, $modes, $interfaces, $reg_domain, $status);

    if ($validated !== false) {
        try {
            // normalize state flags
            $validated['interface'] = $apIface;
            $validated['bridge']    = !empty($states['BridgedEnable']);
            $validated['apsta']     = !empty($states['WifiAPEnable']);
            $validated['repeater']  = !empty($states['RepeaterEnable']);
            $validated['dualmode']  = !empty($states['DualAPEnable']);
            $validated['txpower']   = $txpower;

            // hostapd
            $config = $hostapd->buildConfig($validated, $status);
            $hostapd->saveConfig($config, $dualAPEnable, $validated['interface']);
            $status->addMessage('WiFi hotspot settings saved.', 'success');

            // dnsmasq
            try {
                $syscfg = $dnsmasq->getConfig($ap_iface ?? RASPI_WIFI_AP_INTERFACE);
            } catch (\RuntimeException $e) {
                error_log('Error: ' . $e->getMessage());
            }

            try {
                $dnsmasqConfig = $dnsmasq->buildConfig(
                    $syscfg,
                    $validated['interface'],
                    $validated['apsta'],
                    $validated['bridge']
                );
                $dnsmasq->saveConfig($dnsmasqConfig, $validated['interface']);
            } catch (\RuntimeException $e) {
                error_log('Error: ' . $e->getMessage());
            }

            // dhcpcd
            try {
                $return = $dhcpcd->buildConfig(
                    $validated['interface'],
                    $validated['bridge'],
                    $validated['repeater'],
                    $validated['apsta'],
                    $validated['dualmode'],
                    $status
                );
            } catch (\RuntimeException $e) {
                error_log('Error: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            error_log('Error: ' . $e->getMessage());
            $status->addMessage('Unable to save WiFi hotspot settings', 'danger');
        }
    }

    return true;
}

