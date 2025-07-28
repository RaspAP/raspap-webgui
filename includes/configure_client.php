<?php

use RaspAP\Networking\Hotspot\WiFiManager;

/**
 *
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

    $iface = escapeshellarg($_SESSION['wifi_client_interface']);

    if (isset($_POST['connect'])) {
        $netid = intval($_POST['connect']);
        $cmd = "sudo wpa_cli -i $iface select_network $netid";
        $return = shell_exec($cmd);
        sleep(2);
        if (trim($return) == "FAIL") {
            $status->addMessage('WPA command line client returned failure. Check your adapter.', 'danger');
        } else {
            $status->addMessage('New network selected', 'success');
        }
    } elseif (isset($_POST['wpa_reinit'])) {
        $status->addMessage('Attempting to reinitialize wpa_supplicant', 'warning');
        $force_remove = true;
        $result = $wifi->reinitializeWPA($force_remove);
    } elseif (isset($_POST['client_settings'])) {
        $tmp_networks = $networks;
        if ($wpa_file = fopen('/tmp/wifidata', 'w')) {
            fwrite($wpa_file, 'ctrl_interface=DIR=' . RASPI_WPA_CTRL_INTERFACE . ' GROUP=netdev' . PHP_EOL);
            fwrite($wpa_file, 'update_config=1' . PHP_EOL);

            foreach (array_keys($_POST) as $post) {

                if (preg_match('/delete(\d+)/', $post, $post_match)) {
                    $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];
                    $netid = $network['index'];
                    exec('sudo wpa_cli -i ' . $iface . ' disconnect ' . $netid);
                    exec('sudo wpa_cli -i ' . $iface . ' remove_network ' . $netid);
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
                    $network = $tmp_networks[$_POST['ssid' . $post_match[1]]];
                    $ssid = escapeshellarg('"'.$_POST['ssid' . $post_match[1]].'"');
                    $psk = escapeshellarg('"'.$_POST['passphrase' . $post_match[1]].'"');
                    $netid = trim(shell_exec("sudo wpa_cli -i $iface add_network"));
                    if (isset($netid)) {
                        $commands = [
                            "sudo wpa_cli -i $iface set_network $netid ssid $ssid",
                            "sudo wpa_cli -i $iface set_network $netid psk $psk",
                            "sudo wpa_cli -i $iface enable_network $netid"
                        ];
                        foreach ($commands as $cmd) {
                            exec($cmd);
                        }
                    } else {
                        $status->addMessage('Unable to add network with WPA command line client', 'warning');
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
                        exec('wpa_passphrase '. $wifi->ssid2utf8( escapeshellarg($ssid) ) . ' ' . escapeshellarg($network['passphrase']), $wpa_passphrase);
                        foreach ($wpa_passphrase as $line) {
                            if (preg_match('/^\s*}\s*$/', $line)) {
                                if (array_key_exists('priority', $network)) {
                                    fwrite($wpa_file, "\tpriority=".$network['priority'].PHP_EOL);
                                }
                                fwrite($wpa_file, $line.PHP_EOL);
                            } else {
                                if ( preg_match('/\\\\x[0-9A-Fa-f]{2}/',$ssid) && strpos($line, "ssid=\"") !== false ) {
                                     fwrite($wpa_file, "\tssid=P\"".$ssid."\"".PHP_EOL);
                                } else {
                                     fwrite($wpa_file, $line.PHP_EOL);
                                }
                            }
                        }
                    } elseif (strlen($network['passphrase']) == 0 && strlen($network['passkey']) == 64) {
                        $line = "\tpsk=" . $network['passkey'];
                        fwrite($wpa_file, "network={".PHP_EOL);
                        fwrite($wpa_file, "\tssid=\"".$ssid."\"".PHP_EOL);
                        fwrite($wpa_file, $line.PHP_EOL);
                        if (array_key_exists('priority', $network)) {
                            fwrite($wpa_file, "\tpriority=".$network['priority'].PHP_EOL);
                        }
                        fwrite($wpa_file, "}".PHP_EOL);
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

    $clientInterface = $_SESSION['wifi_client_interface'];

    exec('ip a show '.$clientInterface, $stdoutIp);
    $stdoutIpAllLinesGlued = implode(" ", $stdoutIp);
    $stdoutIpWRepeatedSpaces = preg_replace('/\s\s+/', ' ', $stdoutIpAllLinesGlued);
    preg_match('/state (UP|DOWN)/i', $stdoutIpWRepeatedSpaces, $matchesState) || $matchesState[1] = 'unknown';
    $ifaceStatus = strtolower($matchesState[1]) ? "up" : "down";

    echo renderTemplate("configure_client", compact("status", "clientInterface", "ifaceStatus"));
}
