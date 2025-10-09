<?php

use RaspAP\Networking\Hotspot\HostapdManager;
use RaspAP\Networking\Hotspot\HotspotService;
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
    $hotspot = new HotspotService();
    $status = new StatusMessage();
    $system = new Sysinfo();
    $operatingSystem = $system->operatingSystem();

    // set hostapd defaults
    $arr80211Standard = $hotspot->get80211Standards();
    $arrSecurity = $hotspot->getSecurityModes();
    $arrEncType = $hotspot->getEncTypes();
    $arr80211w = $hotspot->get80211wOptions();
    $languageCode = strtok($_SESSION['locale'], '_');
    $countryCodes = getCountryCodes($languageCode);
    $reg_domain = $hotspot->getRegDomain();
    $interfaces = $hotspot->getInterfaces();
    $arrTxPower = getDefaultNetOpts('txpower','dbm');
    $managedModeEnabled = false;

    if (isset($_POST['interface'])) {
        $interface = $_POST['interface'];
    } else {
        $interface = $_SESSION['ap_interface'];
    }
    $txpower = $hotspot->getTxPower($interface);
    $arrHostapdConf = $hotspot->getHostapdIni();

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
            $hotspot->saveSettings($_POST, $arrSecurity, $arrEncType, $arr80211Standard, $interfaces, $reg_domain, $status);
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
            $hotspot->maybeSetTxPower($interface, $txpower, $status);
        } elseif ($_POST['txpower'] == 'auto') {
            $hotspot->maybeSetTxPower($interface, 'auto', $status);
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

