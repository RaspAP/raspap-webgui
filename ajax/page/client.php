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

$wifi = new \RaspAP\Networking\Hotspot\WiFiManager();

$clientInterface = $_SESSION['wifi_client_interface'];

if (isset($_POST['wifiClientInterface'])) {
    $saveInterfaceStatus = $wifi->getWifiInterface();
    if ($saveInterfaceStatus !== null) {
        $liveForm->sendUpdateMessage($saveInterfaceStatus['message'], 10);
        $liveForm->saveStatusMessage($saveInterfaceStatus['message'], $saveInterfaceStatus['status'], true);
        if ($saveInterfaceStatus['status'] === 'danger') {
            $liveForm->sendFailedMessage();
        } else {
            $liveForm->sendCompleteMessage();
        }
    }
} elseif (isset($_POST['connect'])) {
    $liveForm->sendUpdateMessage(_('Attempting to connect to network'), 40);
    $netid = intval($_POST['connect']);

    if ($wifi->connectToNetwork($clientInterface, $netid)) {
        $liveForm->sendUpdateMessage(_('Connected to network successfully'), 80);
        $liveForm->saveStatusMessage(_('Connected to network successfully'), 'success', true);
        $liveForm->sendCompleteMessage();
    } else {
        $liveForm->sendUpdateMessage(_('Failed to connect to network'), 80);
        $liveForm->saveStatusMessage(_('WPA command line client returned failure. Check your adapter.'), 'danger', true);
        $liveForm->sendFailedMessage();
    }
} elseif (isset($_POST['wpa_reinit'])) {
    $liveForm->sendUpdateMessage(_('Attempting to reinitialize wpa_supplicant'), 40);
    $force_remove = true;
    $result = $wifi->reinitializeWPA($force_remove);
    if (str_contains($result, 'OK')) {
        $liveForm->sendUpdateMessage(_('wpa_supplicant reinitialized successfully'), 80);
        $liveForm->saveStatusMessage(_('wpa_supplicant reinitialized successfully'), 'success', true);
        $liveForm->sendCompleteMessage();
    } else {
        error_log("wpa_supplicant reinit result: " . var_export($result, true));
        $liveForm->sendUpdateMessage(_('Failed to reinitialize wpa_supplicant:'), 80);
        $liveForm->sendUpdateMessage($result, 80);
        $liveForm->saveStatusMessage(_('Failed to reinitialize wpa_supplicant'), 'danger', true);
        $liveForm->sendFailedMessage();
    }
} elseif (isset($_POST['client_settings'])) {
    $liveForm->sendUpdateMessage(_("Updating network settings"), 20);
    $tmp_networks = $networks;

    foreach (array_keys($_POST) as $post) {

        if (preg_match('/delete(\d+)/', $post, $post_match)) {
            $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];
            $netid = $network['index'];
            $liveForm->sendUpdateMessage(sprintf(_('Deleting network: %s'), $network['ssid']));
            $wifi->deleteNetwork($clientInterface, $netid);
            unset($tmp_networks[$_POST['ssid' . $post_match[1]]]);
        } elseif (preg_match('/disconnect(\d+)/', $post, $post_match)) {
            $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];
            $netid = $network['index'];
            $liveForm->sendUpdateMessage(sprintf(_('Disconnecting from network: %s'), $network['ssid']));
            $wifi->disconnectNetwork($clientInterface, $netid);
        } elseif (preg_match('/update(\d+)/', $post, $post_match)) {
            // NB, multiple protocols are separated with a forward slash ('/')
            $protocol = $_POST['protocol' . $post_match[1]] === $wifi::SECURITY_OPEN ? $wifi::SECURITY_OPEN : 'WPA';
            $tmp_networks[$_POST['ssid' . $post_match[1]]] = array(
            'protocol' => $protocol,
            'passphrase' => $_POST['passphrase' . $post_match[1]] ?? '',
            'configured' => true
            );
            if (array_key_exists('priority' . $post_match[1], $_POST) && $_POST['priority' . $post_match[1]] != '') {
                $tmp_networks[$_POST['ssid' . $post_match[1]]]['priority'] = $_POST['priority' . $post_match[1]];
            }
            $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];

            $ssid = $_POST['ssid' . $post_match[1]];
            $passphrase = $_POST['passphrase' . $post_match[1]] ?? '';

            $liveForm->sendUpdateMessage(sprintf(_('Updating network: %s'), $ssid));
            $netid = $wifi->updateNetwork($clientInterface, $ssid, $passphrase, $protocol);
            if ($netid === null) {
                $liveForm->sendUpdateMessage(sprintf(_('Unable to add network with WPA command line client: %s'), $ssid));
            }
        }
    }

    $liveForm->sendUpdateMessage(_('Writing network settings'), 60);
    $result = $wifi->writeWpaSupplicant($tmp_networks, $clientInterface);

    if ($result['success']) {
        $liveForm->sendUpdateMessage(_('Network settings saved successfully'), 80);
        $liveForm->sendUpdateMessage($result['message']);
        $liveForm->saveStatusMessage(_('Network settings saved successfully'), 'success', true);
        $liveForm->sendCompleteMessage();
    } else {
        $liveForm->sendUpdateMessage(_('Failed to save network settings'), 80);
        $liveForm->sendUpdateMessage($result['message']);
        $liveForm->saveStatusMessage(_('Failed to save network settings'), 'danger', true);
        $liveForm->sendFailedMessage();
    }
}

$liveForm->saveStatusMessage(_('No Instructions to Complete'), 'warning');
$liveForm->sendCompleteMessage();

} catch (\Throwable $e) {
    $liveForm->sendUpdateMessage(sprintf(_('An error occurred: %s'), $e->getMessage()), 100);
    $liveForm->saveStatusMessage(_('An error occurred'), 'danger', true);
    $liveForm->sendFailedMessage();
}
