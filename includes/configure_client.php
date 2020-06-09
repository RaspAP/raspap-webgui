<?php

require_once 'includes/status_messages.php';
require_once 'includes/wifi_functions.php';

/**
 *
 *
 */
function DisplayWPAConfig()
{
    $status = new StatusMessages();
    $networks = [];

    getWifiInterface();
    knownWifiStations($networks);

    if (isset($_POST['connect'])) {
        $result = 0;
        exec('sudo wpa_cli -i ' . $_SESSION['wifi_client_interface'] . ' select_network '.strval($_POST['connect']));
        $status->addMessage('New network selected', 'success');
    } elseif (isset($_POST['client_settings'])) {
        $tmp_networks = $networks;
        if ($wpa_file = fopen('/tmp/wifidata', 'w')) {
            fwrite($wpa_file, 'ctrl_interface=DIR=' . RASPI_WPA_CTRL_INTERFACE . ' GROUP=netdev' . PHP_EOL);
            fwrite($wpa_file, 'update_config=1' . PHP_EOL);

            foreach (array_keys($_POST) as $post) {
                if (preg_match('/delete(\d+)/', $post, $post_match)) {
                    unset($tmp_networks[$_POST['ssid' . $post_match[1]]]);
                } elseif (preg_match('/update(\d+)/', $post, $post_match)) {
                    // NB, multiple protocols are separated with a forward slash ('/')
                    $tmp_networks[$_POST['ssid' . $post_match[1]]] = array(
                    'protocol' => ( $_POST['protocol' . $post_match[1]] === 'Open' ? 'Open' : 'WPA' ),
                    'passphrase' => $_POST['passphrase' . $post_match[1]],
                    'configured' => true
                    );
                    if (array_key_exists('priority' . $post_match[1], $_POST)) {
                        $tmp_networks[$_POST['ssid' . $post_match[1]]]['priority'] = $_POST['priority' . $post_match[1]];
                    }
                }
            }

            $ok = true;
            foreach ($tmp_networks as $ssid => $network) {
                if ($network['protocol'] === 'Open') {
                    fwrite($wpa_file, "network={".PHP_EOL);
                    fwrite($wpa_file, "\tssid=\"".$ssid."\"".PHP_EOL);
                    fwrite($wpa_file, "\tkey_mgmt=NONE".PHP_EOL);
                    fwrite($wpa_file, "\tscan_ssid=1".PHP_EOL);
                    if (array_key_exists('priority', $network)) {
                        fwrite($wpa_file, "\tpriority=".$network['priority'].PHP_EOL);
                    }
                    fwrite($wpa_file, "}".PHP_EOL);
                } else {
                    if (strlen($network['passphrase']) >=8 && strlen($network['passphrase']) <= 63) {
                        unset($wpa_passphrase);
                        unset($line);
                        exec('wpa_passphrase '.escapeshellarg($ssid). ' ' . escapeshellarg($network['passphrase']), $wpa_passphrase);
                        foreach ($wpa_passphrase as $line) {
                            if (preg_match('/^\s*}\s*$/', $line)) {
                                if (array_key_exists('priority', $network)) {
                                    fwrite($wpa_file, "\tpriority=".$network['priority'].PHP_EOL);
                                }
                                fwrite($wpa_file, $line.PHP_EOL);
                            } else {
                                fwrite($wpa_file, $line.PHP_EOL);
                            }
                        }
                    } else {
                        $status->addMessage('WPA passphrase must be between 8 and 63 characters', 'danger');
                        $ok = false;
                    }
                }
            }

            if ($ok) {
                system('sudo cp /tmp/wifidata ' . RASPI_WPA_SUPPLICANT_CONFIG, $returnval);
                if ($returnval == 0) {
                    exec('sudo wpa_cli -i ' . $_SESSION['wifi_client_interface'] . ' reconfigure', $reconfigure_out, $reconfigure_return);
                    if ($reconfigure_return == 0) {
                        $status->addMessage('Wifi settings updated successfully', 'success');
                        $networks = $tmp_networks;
                    } else {
                        $status->addMessage('Wifi settings updated but cannot restart (cannot execute "wpa_cli reconfigure")', 'danger');
                    }
                } else {
                    $status->addMessage('Wifi settings failed to be updated', 'danger');
                }
            }
        } else {
            $status->addMessage('Failed to update wifi settings', 'danger');
        }
    }

    nearbyWifiStations($networks);
    connectedWifiStations($networks);
    sortNetworksByRSSI($networks);

    echo renderTemplate("configure_client", compact("status"));
}
