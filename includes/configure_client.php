<?php

use RaspAP\Networking\Hotspot\WiFiManager;

/**
 * WiFi client configuration page handler
 *
 */
function DisplayWPAConfig()
{
    $wifi = new WiFiManager();
    $status = new \RaspAP\Messages\StatusMessage;
    $networks = [];

    $wifi->getWifiInterface();
    $wifi->knownWifiStations($networks);
    $wifi->setKnownStationsWPA($networks);

    $clientInterface = $_SESSION['wifi_client_interface'];

    if (isset($_POST['connect'])) {
        $netid = intval($_POST['connect']);

        if ($wifi->connectToNetwork($clientInterface, $netid)) {
            $status->addMessage('New network selected', 'success');
        } else {
            $status->addMessage('WPA command line client returned failure. Check your adapter.', 'danger');
        }
    } elseif (isset($_POST['wpa_reinit'])) {
        $status->addMessage('Attempting to reinitialize wpa_supplicant', 'warning');
        $force_remove = true;
        $result = $wifi->reinitializeWPA($force_remove);
    } elseif (isset($_POST['client_settings'])) {
        $tmp_networks = $networks;

        foreach (array_keys($_POST) as $post) {

            if (preg_match('/delete(\d+)/', $post, $post_match)) {
                $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];
                $netid = $network['index'];
                $wifi->deleteNetwork($clientInterface, $netid);
                unset($tmp_networks[$_POST['ssid' . $post_match[1]]]);
            } elseif (preg_match('/disconnect(\d+)/', $post, $post_match)) {
                $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];
                $netid = $network['index'];
                $wifi->disconnectNetwork($clientInterface, $netid);
            } elseif (preg_match('/update(\d+)/', $post, $post_match)) {
                // NB, multiple protocols are separated with a forward slash ('/')
                $protocol = $_POST['protocol' . $post_match[1]] === $wifi::SECURITY_OPEN ? $wifi::SECURITY_OPEN : 'WPA';
                $tmp_networks[$_POST['ssid' . $post_match[1]]] = array(
                'protocol' => $protocol,
                'passphrase' => $_POST['passphrase' . $post_match[1]] ?? '',
                'configured' => true
                );
                if (array_key_exists('priority' . $post_match[1], $_POST)) {
                    $tmp_networks[$_POST['ssid' . $post_match[1]]]['priority'] = $_POST['priority' . $post_match[1]];
                }
                $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];

                $ssid = $_POST['ssid' . $post_match[1]];
                $passphrase = $_POST['passphrase' . $post_match[1]] ?? '';

                $netid = $wifi->updateNetwork($clientInterface, $ssid, $passphrase, $protocol);
                if ($netid === null) {
                    $status->addMessage('Unable to add network with WPA command line client', 'warning');
                }
            }
        }

        $result = $wifi->writeWpaSupplicant($tmp_networks, $clientInterface);

        if ($result['success']) {
            $status->addMessage($result['message'], 'success');
            $networks = $tmp_networks;
        } else {
            $status->addMessage($result['message'], 'danger');
        }
    }

    $ifaceStatus = $wifi->getInterfaceStatus($clientInterface);

    echo renderTemplate("configure_client", compact("status", "clientInterface", "ifaceStatus"));
}
