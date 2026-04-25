<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

$liveForm = new \RaspAP\UI\LiveForm();
$liveForm->initAjax();
$liveForm->sendStartMessage();

try {

if (RASPI_MONITOR_ENABLED) {
    $liveForm->sendUpdateMessage(_('RaspAP Monitor Mode Enabled'), 100);
    $liveForm->saveStatusMessage(_('RaspAP Monitor Mode Enabled'), 'warning');
    $liveForm->sendCompleteMessage();
}

$hostapd = new \RaspAP\Networking\Hotspot\HostapdManager();
$hotspot = new \RaspAP\Networking\Hotspot\HotspotService();
$status = new \RaspAP\UI\LiveFormStatusMessage($liveForm);

if (isset($_POST['SaveHostAPDSettings'])) {
    $liveForm->sendUpdateMessage(_('Saving Hotspot settings'), 20);

    $interface = $_POST['interface'];
    $arrSecurity = $hotspot->getSecurityModes();
    $arrEncType = $hotspot->getEncTypes();
    $arr80211Standard = $hotspot->get80211Standards();
    $ifaces = $hotspot->getInterfaces();
    $reg_domain = $hotspot->getRegDomain();

    $result = $hotspot->saveSettings(
        $_POST,
        $arrSecurity,
        $arrEncType,
        $arr80211Standard,
        $ifaces,
        $reg_domain,
        $status
    );

     // process txpower user input
    if (isset($_POST['txpower'])) {
        if ($_POST['txpower'] != 'auto') {
            $txpower = intval($_POST['txpower']);
            $hotspot->maybeSetTxPower($interface, $txpower, $status);
        } elseif ($_POST['txpower'] == 'auto') {
            $hotspot->maybeSetTxPower($interface, 'auto', $status);
        }
    }

    $liveForm->sendUpdateMessage(_('Saved Hotspot settings'), 90);
    $liveForm->saveStatusMessage(_('Successfully saved Hotspot settings'), 'success', true);
    $liveForm->sendCompleteMessage();
} elseif (isset($_POST['StartHotspot']) || isset($_POST['RestartHotspot']) || isset($_POST['StopHotspot'])) {
    $arrHostapdConf = $hotspot->getHostapdIni();
    $interface = $_SESSION['ap_interface'];
    $isDualAP = isset($arrHostapdConf['DualAPEnable']) && $arrHostapdConf['DualAPEnable'] == 1;

    // check if hotspot is already running
    // $hostapdstatus = $system->hostapdStatus();
    // $serviceStatus = $hostapdstatus[0] == 0 ? "down" : "up";

    if (isset($_POST['StartHotspot']) || isset($_POST['RestartHotspot'])) {
        $liveForm->sendUpdateMessage(_('Attempting to start hotspot'), 30);

        if ($arrHostapdConf['BridgedEnable'] == 1) {
            $liveForm->sendUpdateMessage(_('Starting in bridged mode'), 40);
            
            exec('sudo '.RASPI_CONFIG.'/hostapd/servicestart.sh --interface br0 --seconds 1', $return);
        } elseif ($arrHostapdConf['WifiAPEnable'] == 1) {
            $liveForm->sendUpdateMessage(_('Starting in WiFi AP mode'), 40);

            exec('sudo '.RASPI_CONFIG.'/hostapd/servicestart.sh --interface uap0 --seconds 1', $return);
        } elseif ($isDualAP && $hostapd->countHostapdConfigs() < 2) {
            $liveForm->sendUpdateMessage('Dual AP mode requires at least 2 hostapd configurations');
            $liveForm->saveStatusMessage('Dual AP mode requires at least 2 hostapd configurations', 'danger');
            $liveForm->sendFailedMessage();
        } else {
            $liveForm->sendUpdateMessage(_('Starting in standard mode'), 40);

            // systemctl expects a unit name like raspap-network-activity@wlan0.service
            $iface_nonescaped = $interface;
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $iface_nonescaped)) { // validate interface name
                exec('sudo '.RASPI_CONFIG.'/hostapd/servicestart.sh --interface ' .$iface_nonescaped. ' --seconds 1', $return);
            } else {
                $liveForm->sendUpdateMessage(sprintf(_('Invalid network interface: %s'), $iface_nonescaped), 50);
                $liveForm->saveStatusMessage(_('Invalid network interface'), 'danger');
                $liveForm->sendFailedMessage();
            }
        }

        foreach ($return as $line) {
            $liveForm->sendUpdateMessage($line);
        }

        $liveForm->sendUpdateMessage(isset($_POST['RestartHotspot']) ? _('Hotspot Restarted') : _('Hotspot Started'), 90);
        $liveForm->saveStatusMessage(isset($_POST['RestartHotspot']) ? _('Hotspot Restarted') : _('Hotspot Started'), 'success', true);
        $liveForm->sendCompleteMessage();
    } elseif (isset($_POST['StopHotspot'])) {
        $liveForm->sendUpdateMessage(_('Attempting to stop hotspot'), 30);

        if ($isDualAP) {
            $liveForm->sendUpdateMessage(_('Stopping Hotspot in Dual AP mode'), 40);
            // stop all hostapd@*.service units
            exec('find /etc/hostapd -name "hostapd-*.conf"', $confFiles);
            foreach ($confFiles as $conf) {
                if (preg_match('/hostapd-([a-zA-Z0-9]+)\.conf$/', $conf, $matches)) {
                    $iface = $matches[1];
                    $cmd = escapeshellcmd("sudo systemctl stop hostapd@{$iface}.service");
                    exec($cmd, $output);
                    $liveForm->sendUpdateMessage(sprintf(_('Stopped hostapd@{%s}.service'), $iface));
                }
            }
        } else {
            $liveForm->sendUpdateMessage(_('Stopping Hotspot'), 40);
            // stop default service
            exec('sudo systemctl stop hostapd.service', $return);
            foreach ($return as $line) {
                $liveForm->sendUpdateMessage($line);
            }
        }

        exec('sudo systemctl stop "raspap-network-activity@*.service"');
        $liveForm->sendUpdateMessage(_('Stopped RaspAP Network Activity Monitor'), 40);

        $liveForm->sendUpdateMessage(_('Hotspot Stopped'), 90);
        $liveForm->saveStatusMessage(_('Hotspot Stopped'), 'success', true);
        $liveForm->sendCompleteMessage();
    }
}

$liveForm->saveStatusMessage(_('No Instructions to Complete'), 'warning');
$liveForm->sendCompleteMessage();

} catch (\Throwable $e) {
    $liveForm->sendUpdateMessage(sprintf(_('An error occurred: %s'), $e->getMessage()), 100);
    $liveForm->saveStatusMessage(_('An error occurred'), 'danger', true);
    $liveForm->sendFailedMessage();
}
