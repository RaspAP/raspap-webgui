<?php

/**
 * A wireless utility class for RaspAP
 * @description A collection of wireless utility methods
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking\Hotspot;

class WiFiManager
{

    private const MIN_RSSI = -100;
    private const MAX_RSSI = -55;
    const SECURITY_OPEN = 'OPEN';

    public function knownWifiStations(&$networks)
    {
        // find currently configured networks
        exec(' sudo cat ' . RASPI_WPA_SUPPLICANT_CONFIG, $known_return);
        foreach ($known_return as $line) {
            if (preg_match('/network\s*=/', $line)) {
                $network = array('visible' => false, 'configured' => true, 'connected' => false, 'index' => null);
            } elseif (isset($network) && $network !== null) {
                if (preg_match('/^\s*}\s*$/', $line)) {
                    $networks[$ssid] = $network;
                    $network = null;
                    $ssid = null;
                } elseif ($lineArr = preg_split('/\s*=\s*/', trim($line), 2)) {
                    switch (strtolower($lineArr[0])) {
                    case 'ssid':
                        $ssid = trim($lineArr[1], '"');
                        $ssid = str_replace('P"','',$ssid);
                        $network['ssid'] = $ssid;
                        $network['index'] = $this->getNetworkIdBySSID($ssid);
                        break;
                    case 'psk':
                        $network['passkey'] = trim($lineArr[1]);
                        $network['protocol'] = 'WPA';
                        break;
                    case '#psk':
                        $network['protocol'] = 'WPA';
                    case 'wep_key0': // Untested
                        $network['passphrase'] = trim($lineArr[1], '"');
                        break;
                    case 'key_mgmt':
                        if (! array_key_exists('passphrase', $network) && $lineArr[1] === 'NONE') {
                            $network['protocol'] = self::SECURITY_OPEN;
                        }
                        break;
                    case 'priority':
                        $network['priority'] = trim($lineArr[1], '"');
                        break;
                    }
                }
            }
        }
    }

    /**
     * Scans for nearby WiFi networks using `iw` and updates the reference array
     *
     * @param array $networks Reference to the array of known and discovered networks
     * @param bool $cached    If false, bypasses the cache and performs a fresh scan
     */
    public function nearbyWifiStations(&$networks, $cached = true)
    {
        $cacheTime = filemtime(RASPI_WPA_SUPPLICANT_CONFIG);
        $cacheKey  = "nearby_wifi_stations_$cacheTime";

        if ($cached == false) {
            deleteCache($cacheKey);
        }

        $iface = escapeshellarg($_SESSION['wifi_client_interface']);

        $scan_results = cache(
            $cacheKey,
            function () use ($iface) {
                $stdout = shell_exec("sudo iw dev $iface scan");
                sleep(1);
                if ($stdout === null) {
                    return [];
                }
                return preg_split("/\n/", $stdout);
            }
        );

        // determine the next index that follows the indexes of the known networks
        $index = 0;
        if (!empty($networks)) {
            foreach ($networks as $network) {
                if (isset($network['index']) && is_numeric($network['index']) && ($network['index'] > $index)) {
                    $index = (int)$network['index'];
                }
            }
        }
        $index++;

        $current = [];
        $commitCurrent = function () use (&$current, &$networks, &$index) {
            if (empty($current['ssid'])) {
                return;
            }

            $ssid = $current['ssid'];

            // unprintable 7bit ASCII control codes, delete or quotes -> ignore network
            if (preg_match('/[\x00-\x1f\x7f\'`\´"]/', $ssid)) {
                return;
            }

            $channel = ConvertToChannel($current['freq'] ?? 0);
            $rssi = $current['signal'] ?? -100;

            // if network is saved
            if (array_key_exists($ssid, $networks)) {
                $networks[$ssid]['visible'] = true;
                $networks[$ssid]['channel'] = $channel;
                if (!isset($networks[$ssid]['RSSI']) || $networks[$ssid]['RSSI'] < $rssi) {
                    $networks[$ssid]['RSSI'] = $rssi;
                }
            } else {
                $networks[$ssid] = [
                    'ssid' => $ssid,
                    'configured' => false,
                    'protocol' => $current['security'] ?? self::SECURITY_OPEN,
                    'channel' => $channel,
                    'passphrase' => '',
                    'visible' => true,
                    'connected' => false,
                    'RSSI' => $rssi,
                    'index' => $index
                ];
                $index++; // increment for next new network
            }
        };
        
        if (is_string($scan_results)) {
            $scan_results = explode("\n", trim($scan_results));
        }

        foreach ($scan_results as $line) {
            $line = trim($line);

            if (preg_match('/^BSS\s+([0-9a-f:]{17})/', $line, $match)) {
                $commitCurrent(); // commit previous
                $current = [
                    'bssid' => $match[1],
                    'ssid' => '',
                    'signal' => null,
                    'freq' => null,
                    'security' => self::SECURITY_OPEN
                ];
                continue;
            }
            if (preg_match('/^SSID:\s*(.*)$/', $line, $match)) {
                $current['ssid'] = $match[1];
                continue;
            }
            if (preg_match('/^signal:\s*(-?\d+\.\d+)/', $line, $match)) {
                $current['signal'] = (float)$match[1];
                continue;
            }
            if (preg_match('/^freq:\s*(\d+)/', $line, $match)) {
                $current['freq'] = (int)$match[1];
                continue;
            }
            if (preg_match('/^RSN:/', $line) || preg_match('/^WPA:/', $line)) {
                $current['security'] = 'WPA/WPA2';
                continue;
            }
        }
        $commitCurrent();
    }
    /**
     * Check if networks are connected via wpa_cli status
     * NB: iwconfig shows the last associated SSID even when connection is inactive
     */
    public function connectedWifiStations(&$networks)
    {
        $wpa_state = null;
        $connected_ssid = null;
        $iface = $_SESSION['wifi_client_interface'];
        
        $cmd = "sudo wpa_cli -i $iface status";
        $status_output = shell_exec($cmd);

        if ($status_output === null || empty($status_output)) {
            error_log("WiFiManager::connectedWifiStations: wpa_cli command failed or returned no output");
            return;
        }
        $lines = explode("\n", trim($status_output));

        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^wpa_state=(.+)$/', $line, $matches)) {
                $wpa_state = trim($matches[1]);
            }
            if (preg_match('/^ssid=(.+)$/', $line, $matches)) {
                $connected_ssid = trim($matches[1]);
            }
        }        
        
        if ($wpa_state === 'COMPLETED' && !empty($connected_ssid)) {
            $ssid = hexSequence2lower($connected_ssid);

            // check if this SSID exists in networks array
            if (array_key_exists($ssid, $networks)) {
                $networks[$ssid]['connected'] = true;
            } else {
                error_log("WiFiManager::connectedWifiStations: SSID '$ssid' not found. SSIDs: " . implode(', ', array_keys($networks)));
            }

            // captive portal detection
            // $check = detectCaptivePortal($iface);
            // if (isset($check["URL"])) {
            //     $networks[$ssid]["portal-url"] = $check["URL"];
            // }
        }
    }

    /**
     *
     *
     */
    public function sortNetworksByRSSI(&$networks)
    {
        $valRSSI = array();
        foreach ($networks as $SSID => $net) {
            if (!array_key_exists('RSSI', $net)) {
                $net['RSSI'] = -1000;
            }
            $valRSSI[$SSID] = $net['RSSI'];
        }
        $nets = $networks;
        arsort($valRSSI);
        $networks = array();
        foreach ($valRSSI as $SSID => $RSSI) {
            $networks[$SSID] = $nets[$SSID];
            $networks[$SSID]['RSSI'] = $RSSI;
        }
    }

    /*
     * Determines the configured wireless AP interface
     *
     * If not saved in /etc/raspap/hostapd.ini, check for a second
     * wireless interface with iw dev. Fallback to the constant
     * value defined in config.php
     */
    public function getWifiInterface()
    {
        $hostapdIni = RASPI_CONFIG . '/hostapd.ini';
        $arrHostapdConf = file_exists($hostapdIni) ? parse_ini_file($hostapdIni) : [];

        $iface = $_SESSION['ap_interface'] = $arrHostapdConf['WifiInterface'] ?? RASPI_WIFI_AP_INTERFACE;

        if (!validateInterface($iface)) {
            $iface = RASPI_WIFI_AP_INTERFACE;
        }

        // check for 2nd wifi interface -> wifi client on different interface
        exec("iw dev | awk '$1==\"Interface\" && $2!=\"$iface\" {print $2}'", $iface2);
        $client_iface = $_SESSION['wifi_client_interface'] = empty($iface2) ? $iface : trim($iface2[0]);

        // handle special case for RPi Zero W in AP-STA mode
        if ($client_iface === "uap0" && ($arrHostapdConf['WifiAPEnable'] ?? 0)) {
            $_SESSION['wifi_client_interface'] = $iface;
            $_SESSION['ap_interface'] = $client_iface;
        }
    }

    /*
     * Reinitializes wpa_supplicant for the wireless client interface
     * The 'force' parameter deletes the socket in /var/run/wpa_supplicant/
     *
     * @param boolean $force
     */
    public function reinitializeWPA($force)
    {
        $iface = $_SESSION['wifi_client_interface'];
        if ($force == true) {
            $cmd = "sudo rm -f /var/run/wpa_supplicant/" . $iface;
            $result = shell_exec($cmd);
            $cmd = "sudo /sbin/wpa_supplicant -i $iface -c /etc/wpa_supplicant/wpa_supplicant.conf -B 2>&1";
            $result = shell_exec($cmd);
            sleep(2);
        }
        $cmd = "sudo wpa_cli -i $iface reconfigure";
        $result = shell_exec($cmd);
        sleep(1);
        return $result;
    }

    /*
     * Replace escaped bytes (hex) by binary - assume UTF8 encoding
     *
     * @param string $ssid
     */
    public function ssid2utf8($ssid)
    {
        return  evalHexSequence($ssid);
    }

    /*
     * Returns a signal strength indicator based on RSSI value
     *
     * @param string $rssi
     */
    public function getSignalBars($rssi)
    {
        // assign css class based on RSSI value
        $class = '';
        if ($rssi >= SELF::MAX_RSSI) {
            $class = 'strong';
        } elseif ($rssi >= -56) {
            $class = 'medium';
        } elseif ($rssi >= -67) {
            $class = 'weak';
        } elseif ($rssi >= -89) {
            $class = '';
        }

        // calculate percent strength
        if ($rssi >= -50) {
            $pct =  100;
        } elseif ($rssi <= SELF::MIN_RSSI) {
            $pct = 0;
        } else {
            $pct = 2*($rssi + 100);
        }
        $elem = '<div data-toggle="tooltip" title="' . _("Signal strength"). ': ' .$pct. '%" class="signal-icon ' .$class. '">'.PHP_EOL;
        for ($n = 0; $n < 3; $n++ ) {
            $elem .= '<div class="signal-bar"></div>'.PHP_EOL;
        }
        $elem .= '</div>'.PHP_EOL;
        return $elem;
    }

    /**
     * Parses output of wpa_cli list_networks, compares with known networks
     * from wpa_supplicant, and adds with wpa_cli if not found
     *
     * @param array $networks
     * @throws Exception on wpa_cli command failure
     */
    public function setKnownStationsWPA($networks)
    {
        $this->ensureWpaSupplicant();

        $iface = escapeshellarg($_SESSION['wifi_client_interface']);
        $output = shell_exec("sudo wpa_cli -i $iface list_networks 2>&1");

        if ($output === null) {
            throw new \Exception("Failed to execute wpa_cli command - command returned null");
        }

        // check for common wpa_cli errors and try to fix them
        if (strpos($output, 'Failed to connect') !== false || strpos($output, 'No such file or directory') !== false) {
            error_log("wpa_supplicant not available for interface, attempting to start it");

            // try starting wpa_supplicant for this interface
            $unescapedIface = trim($iface, "'\"");
            $startCmd = "sudo /sbin/wpa_supplicant -i $unescapedIface -c /etc/wpa_supplicant/wpa_supplicant.conf -B 2>&1";
            $startResult = shell_exec($startCmd);
            sleep(2);

            // retry
            $output = shell_exec("sudo wpa_cli -i $iface list_networks 2>&1");

            // if it still fails, throw an exception
            if ($output === null || strpos($output, 'Failed to connect') !== false) {
                throw new \Exception("Failed to start wpa_supplicant for interface: " . trim($startResult ?? 'unknown error'));
            }
        }

        // split output into lines
        $lines = explode("\n", trim($output));

        // check for header line
        if (empty($lines) || count($lines) < 1) {
            error_log("wpa_cli list_networks returned no output");
            $wpaCliNetworks = [];
        } else {
            // remove header line if it exists
            $headerLine = trim($lines[0]);
            if (strpos($headerLine, 'network id') !== false || strpos($headerLine, 'id') !== false) {
                array_shift($lines);
            }

            $wpaCliNetworks = [];
            foreach ($lines as $line) {
                $trimmedLine = trim($line);

                // skip empty lines
                if (empty($trimmedLine)) {
                    continue;
                }

                $data = explode("\t", $trimmedLine);
                if (count($data) >= 2) {
                    $id = trim($data[0]);
                    $ssid = trim($data[1]);

                    // add if we have valid data
                    if ($id !== '' && $ssid !== '') {
                        $wpaCliNetworks[] = [
                            'id' => $id,
                            'ssid' => $ssid
                        ];
                    }
                }
            }
        }

        // process networks to add
        foreach ($networks as $network) {
            if (!isset($network['ssid']) || empty($network['ssid'])) {
                error_log("Skipping network with missing or empty SSID");
                continue;
            }

            $ssid = $network['ssid'];
            if (!$this->networkExists($ssid, $wpaCliNetworks)) {
                $this->addWpaNetwork($network, $iface);
            }
        }
    }

    /**
     * Helper method to add a single network to wpa_supplicant
     *
     * @param array $network Network configuration
     * @param string $iface Escaped shell argument for interface
     */
    private function addWpaNetwork($network, $iface)
    {
        $ssid = escapeshellarg('"' . $network['ssid'] . '"');
        $psk = escapeshellarg('"' . $network['passphrase'] . '"');
        $protocol = $network['protocol'] ?? 'WPA';

        // add network and get its ID
        $netid = trim(shell_exec("sudo wpa_cli -i $iface add_network 2>&1"));

        // validate network ID
        if (!$netid || !is_numeric($netid)) {
            error_log("Failed to add network '{$network['ssid']}': Invalid network ID returned: '$netid'");
            return;
        }

        // prepare command based on protocol
        $commands = [
            "sudo wpa_cli -i $iface set_network $netid ssid $ssid",
        ];

        if ($protocol === self::SECURITY_OPEN) {
            $commands[] = "sudo wpa_cli -i $iface set_network $netid key_mgmt NONE";
        } else {
            $commands[] = "sudo wpa_cli -i $iface set_network $netid psk $psk";
        }

        $commands[] = "sudo wpa_cli -i $iface enable_network $netid";

        // execute commands, checking errors
        foreach ($commands as $cmd) {
            $result = shell_exec("$cmd 2>&1");
            if ($result === null || strpos($result, 'FAIL') !== false) {
                error_log("Command failed: $cmd - Result: " . ($result ?? 'null'));
                // remove the failed network
                shell_exec("sudo wpa_cli -i $iface remove_network $netid 2>&1");
                return;
            }
            usleep(1000);
        }
        error_log("Successfully added network: {$network['ssid']}");
    }

    /**
     * Parses wpa_cli list_networks output and returns the id
     * of a corresponding network SSID
     *
     * @param string $ssid
     * @return integer id or null
     */
    public function getNetworkIdBySSID($ssid) {
        $iface = escapeshellarg($_SESSION['wifi_client_interface']);
        $cmd = "sudo wpa_cli -i $iface list_networks";
        $output = [];
        exec($cmd, $output);
        array_shift($output);

        foreach ($output as $line) {
            $columns = preg_split('/\t/', $line);
            if (count($columns) >= 2 && trim($columns[1]) === trim($ssid)) {
                return (int)$columns[0]; // return network ID
            }
        }
        return null;
    }

    /**
     *
     */
    public function networkExists($ssid, $collection)
    {
        foreach ($collection as $network) {
            if ($network['ssid'] === $ssid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ensures /etc/wpa_supplicant/wpa_supplicant.conf exists with minimal safe contents
     * Does not overwrite an existing file
     *
     * @throws \RuntimeException on permission or write failure
     */
    public function ensureWpaSupplicant(): void
    {
        $confPath = '/etc/wpa_supplicant/wpa_supplicant.conf';

        if (file_exists($confPath)) {
            return;
        }

        $contents = <<<CONF
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1
CONF;

        $tmpFile = tempnam(sys_get_temp_dir(), 'wpa_conf_');
        if ($tmpFile === false) {
            throw new \RuntimeException("Failed to create temporary file for wpa_supplicant.conf");
        }

        file_put_contents($tmpFile, $contents);
        chmod($tmpFile, 0600);

        $cmd = escapeshellcmd("sudo cp $tmpFile $confPath");
        exec($cmd, $output, $exitCode);
        unlink($tmpFile);

        if ($exitCode !== 0) {
            throw new \RuntimeException("Failed to initialize wpa_supplicant.conf:  " . implode("\n", $output));
        }
    }


    /**
     * Gets the operational status of a network interface
     *
     * @param string $interface network interface name
     * @return string returns up, down, or unknown
     */
    public function getInterfaceStatus(string $interface): string
    {
        exec('ip a show ' . escapeshellarg($interface), $output);
        $outputGlued = implode(" ", $output);
        $outputNormalized = preg_replace('/\s\s+/', ' ', $outputGlued);

        if (preg_match('/state (UP|DOWN)/i', $outputNormalized, $matches)) {
            return strtolower($matches[1]);
        }

        return 'unknown';
    }

    /**
     * Connects to a network using wpa_cli
     *
     * @param string $interface network interface name
     * @param int $netid network ID to connect to
     * @return bool true on success, false on failure
     */
    public function connectToNetwork(string $interface, int $netid): bool
    {
        $iface = escapeshellarg($interface);

        $cmd = "sudo wpa_cli -i $iface select_network $netid";
        $selectResult = shell_exec($cmd);

        if ($selectResult === null || trim($selectResult) === "FAIL") {
            return false;
        }
        sleep(3);

        $cmd = "sudo wpa_cli -i $iface reassociate";
        $reassociateResult = shell_exec($cmd);

        if ($reassociateResult !== null) {
            $trimmed = trim($reassociateResult);
            if ($trimmed === "FAIL") {
                return false;
            }
        }

        return true;
    }

    /**
     * Deletes a network from wpa_cli
     *
     * @param string $interface network interface name
     * @param int $netid network ID to delete
     * @return void
     */
    public function deleteNetwork(string $interface, int $netid): void
    {
        $iface = escapeshellarg($interface);

        exec("sudo wpa_cli -i $iface disconnect $netid");
        exec("sudo wpa_cli -i $iface remove_network $netid");
    }

    /**
     * Disconnects from a network using wpa_cli
     *
     * @param string $interface network interface name
     * @param int $netid network ID to disconnect from
     * @return void
     */
    public function disconnectNetwork(string $interface, int $netid): void
    {
        $iface = escapeshellarg($interface);

        exec("sudo wpa_cli -i $iface disconnect $netid");
        exec("sudo wpa_cli -i $iface remove_network $netid");
        sleep(2);
    }

    /**
     * Updates/adds a network via wpa_cli
     *
     * @param string $interface network interface name
     * @param string $ssid network SSID
     * @param string $passphrase network passphrase
     * @param string $protocol security protocol (OPEN or WPA)
     * @return int|null network ID on success, null on failure
     */
    public function updateNetwork(string $interface, string $ssid, string $passphrase, string $protocol = 'WPA'): ?int
    {
        $iface = escapeshellarg($interface);
        $escapedSsid = escapeshellarg('"' . $ssid . '"');

        $netid = shell_exec("sudo wpa_cli -i $iface add_network");

        if ($netid === null || !is_numeric(trim($netid))) {
            return null;
        }

        $netid = trim($netid);
        $commands = [
            "sudo wpa_cli -i $iface set_network $netid ssid $escapedSsid"
        ];

        if ($protocol === self::SECURITY_OPEN) {
            $commands[] = "sudo wpa_cli -i $iface set_network $netid key_mgmt NONE";
        } else {
            $escapedPsk = escapeshellarg('"' . $passphrase . '"');
            $commands[] = "sudo wpa_cli -i $iface set_network $netid psk $escapedPsk";
        }

        $commands[] = "sudo wpa_cli -i $iface enable_network $netid";

        foreach ($commands as $cmd) {
            exec($cmd);
        }

        return (int)$netid;
    }

    /**
     * Writes a wpa_supplicant configuration and applies it
     *
     * @param array $networks array of network configurations
     * @param string $interface the network interface name
     * @return array Array with 'success' (bool) and 'message' (string)
     */
    public function writeWpaSupplicant(array $networks, string $interface): array
    {
        $wpa_file = fopen('/tmp/wifidata', 'w');
        if (!$wpa_file) {
            return ['success' => false, 'message' => 'Failed to update wifi settings'];
        }

        fwrite($wpa_file, 'ctrl_interface=DIR=' . RASPI_WPA_CTRL_INTERFACE . ' GROUP=netdev' . PHP_EOL);
        fwrite($wpa_file, 'update_config=1' . PHP_EOL);

        $ok = true;
        foreach ($networks as $ssid => $network) {
            if ($network['protocol'] === self::SECURITY_OPEN) {
                fwrite($wpa_file, "network={".PHP_EOL);
                fwrite($wpa_file, "\tssid=\"".$ssid."\"".PHP_EOL);
                fwrite($wpa_file, "\tkey_mgmt=NONE".PHP_EOL);
                fwrite($wpa_file, "\tscan_ssid=1".PHP_EOL);
                if (array_key_exists('priority', $network)) {
                    fwrite($wpa_file, "\tpriority=".$network['priority'].PHP_EOL);
                }
                fwrite($wpa_file, "}".PHP_EOL);
            } else {
                if (strlen($network['passphrase']) >= 8 && strlen($network['passphrase']) <= 63) {
                    unset($wpa_passphrase);
                    unset($line);
                    exec('wpa_passphrase '. $this->ssid2utf8(escapeshellarg($ssid)) . ' ' . escapeshellarg($network['passphrase']), $wpa_passphrase);
                    foreach ($wpa_passphrase as $line) {
                        if (preg_match('/^\s*}\s*$/', $line)) {
                            if (array_key_exists('priority', $network)) {
                                fwrite($wpa_file, "\tpriority=".$network['priority'].PHP_EOL);
                            }
                            fwrite($wpa_file, $line.PHP_EOL);
                        } else {
                            if (preg_match('/\\\\x[0-9A-Fa-f]{2}/', $ssid) && strpos($line, "ssid=\"") !== false) {
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
                    $ok = false;
                    fclose($wpa_file);
                    return ['success' => false, 'message' => 'WPA passphrase must be between 8 and 63 characters'];
                }
            }
        }

        fclose($wpa_file);

        if ($ok) {
            system('sudo cp /tmp/wifidata ' . RASPI_WPA_SUPPLICANT_CONFIG, $returnval);
            if ($returnval == 0) {
                exec('sudo wpa_cli -i ' . escapeshellarg($interface) . ' reconfigure', $reconfigure_out, $reconfigure_return);
                if ($reconfigure_return == 0) {
                    return ['success' => true, 'message' => 'Wifi settings updated successfully'];
                } else {
                    return ['success' => false, 'message' => 'Wifi settings updated but cannot restart (cannot execute "wpa_cli reconfigure")'];
                }
            } else {
                return ['success' => false, 'message' => 'Wifi settings failed to be updated'];
            }
        }

        return ['success' => false, 'message' => 'Unknown error'];
    }

}
