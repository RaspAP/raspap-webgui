<?php

use RaspAP\Networking\Hotspot\WiFiManager;
use RaspAP\Networking\NetworkManagerClient;

/**
 * WiFi client configuration page handler
 *
 */
function DisplayWPAConfig()
{
    $wifi = new WiFiManager();
    $status = new \RaspAP\Messages\StatusMessage;
    $networks = [];

    $saveInterfaceStatus = $wifi->getWifiInterface();
    if ($saveInterfaceStatus !== null) {
        $status->addMessage($saveInterfaceStatus['message'], $saveInterfaceStatus['status']);
    }

    // When NetworkManager manages the client interface, the networks become
    // NetworkManager connections. wpa_cli must then not add, select or
    // remove networks, because NetworkManager controls wpa_supplicant.
    $nm = new NetworkManagerClient();
    $nmMode = $nm->isManaged($_SESSION['wifi_client_interface'] ?? null);

    $wifi->knownWifiStations($networks);
    if (!$nmMode) {
        $wifi->setKnownStationsWPA($networks);
    } elseif ($nm->ensureProfiles($_SESSION['wifi_client_interface'], $networks, $skipped) === false) {
        // networks of wpa_supplicant.conf without a RaspAP connection yet
        $status->addMessage('Unable to update the NetworkManager connections', 'danger');
    } else {
        addSkippedNetworkMessages($status, $skipped ?? []);
    }
    $wifi->connectedWifiStations($networks);

    $connectedNetworks = array_filter($networks, function($n) { return $n['connected']; } );
    $hasConnection = count($connectedNetworks) > 0;

    $ifaces = $wifi->getInterfaces();
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
    $initial_iface = $clientInterface = $_SESSION['wifi_client_interface'];

    if (isset($_POST['nm_connect'])) {
        // a NetworkManager connection that RaspAP did not create
        if ($nmMode && is_string($_POST['nm_connect']) && $nm->connect($clientInterface, $_POST['nm_connect'])) {
            $status->addMessage('New network selected', 'success');
        } else {
            $status->addMessage('NetworkManager could not activate the connection', 'danger');
        }
    } elseif (isset($_POST['connect'])) {
        $netid = intval($_POST['connect']);

        if ($nmMode) {
            $ssid = $_POST['ssid' . $netid] ?? '';
            if (is_string($ssid) && $nm->connectBySsid($clientInterface, $ssid)) {
                $status->addMessage('New network selected', 'success');
            } else {
                $status->addMessage('NetworkManager could not activate the connection', 'danger');
            }
        } elseif ($wifi->connectToNetwork($clientInterface, $netid)) {
            $status->addMessage('New network selected', 'success');
        } else {
            $status->addMessage('WPA command line client returned failure. Check your adapter.', 'danger');
        }
    } elseif (isset($_POST['wpa_reinit']) && !$nmMode) {
        $status->addMessage('Attempting to reinitialize wpa_supplicant', 'warning');
        $force_remove = true;
        $result = $wifi->reinitializeWPA($force_remove);
    } elseif (isset($_POST['client_settings'])) {
        $tmp_networks = $networks;
        $nmDisconnect = false;

        foreach (array_keys($_POST) as $post) {

            if (preg_match('/delete(\d+)/', $post, $post_match)) {
                $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];
                if (!$nmMode) {
                    $wifi->deleteNetwork($clientInterface, $network['index']);
                }
                unset($tmp_networks[$_POST['ssid' . $post_match[1]]]);
            } elseif (preg_match('/disconnect(\d+)/', $post, $post_match)) {
                $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];
                if ($nmMode) {
                    // after the sync below, because a reload can connect again
                    $nmDisconnect = true;
                } else {
                    $wifi->disconnectNetwork($clientInterface, $network['index']);
                }
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

                if (!$nmMode) {
                    $netid = $wifi->updateNetwork($clientInterface, $ssid, $passphrase, $protocol);
                    if ($netid === null) {
                        $status->addMessage('Unable to add network with WPA command line client', 'warning');
                    }
                }
            }
        }

        $result = $wifi->writeWpaSupplicant($tmp_networks, $clientInterface, !$nmMode);

        if ($result['success']) {
            $status->addMessage($result['message'], 'success');
            $networks = $tmp_networks;
            if ($nmMode) {
                // read the file again, for the PSKs that wpa_passphrase derived
                $saved = [];
                $wifi->knownWifiStations($saved);
                if (!$nm->syncNetworks($clientInterface, $saved, $skipped)) {
                    $status->addMessage('Unable to update the NetworkManager connections', 'danger');
                }
                addSkippedNetworkMessages($status, $skipped ?? []);
            }
            if ($nmDisconnect && !$nm->disconnect($clientInterface)) {
                $status->addMessage('NetworkManager could not disconnect the interface', 'danger');
            }
        } else {
            $status->addMessage($result['message'], 'danger');
        }
    }

    echo renderTemplate("configure_client", compact("status", "hasConnection", "interfaces", "initial_iface", "nmMode"));
}

/**
 * Adds a warning for each network that NetworkManager can not use, because
 * wpa_supplicant.conf has no usable passphrase for it
 *
 * @param object $status
 * @param array $skipped SSIDs
 */
function addSkippedNetworkMessages($status, array $skipped)
{
    foreach ($skipped as $ssid) {
        // the network does not work until the user enters the passphrase, so
        // the warning stays until the user closes it
        $status->addMessage(sprintf(_('%s has no passphrase that NetworkManager can use. Enter the passphrase and click Update.'), htmlspecialchars(evalHexSequence($ssid), ENT_QUOTES)), 'warning alert-persistent');
    }
}
