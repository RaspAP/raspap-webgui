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

    public function knownWifiStations(&$networks)
    {
        // find currently configured networks
        exec(' sudo cat ' . RASPI_WPA_SUPPLICANT_CONFIG, $known_return);
        $index = 0;
        foreach ($known_return as $line) {
            if (preg_match('/network\s*=/', $line)) {
                $network = array('visible' => false, 'configured' => true, 'connected' => false, 'index' => null);
                ++$index;
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
                        $index = $this->getNetworkIdBySSID($ssid);
                        $network['index'] = $index;
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
                            $network['protocol'] = 'Open';
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
                return preg_split("/\n/", $stdout);
            }
        );

        // exclude the AP from nearby networks
        exec('sed -rn "s/ssid=(.*)\s*$/\1/p" ' . escapeshellarg(RASPI_HOSTAPD_CONFIG), $ap_ssid);
        $ap_ssid = $ap_ssid[0] ?? '';

        $index = 0;
        if (!empty($networks)) {
            $lastnet = end($networks);
            if (isset($lastnet['index'])) {
                $index = $lastnet['index'] + 1;
            }
        }

        $current = [];
        $commitCurrent = function () use (&$current, &$networks, &$index, $ap_ssid) {
            if (empty($current['ssid'])) {
                return;
            }

            $ssid = $current['ssid'];

            // unprintable 7bit ASCII control codes, delete or quotes -> ignore network
            if ($ssid === $ap_ssid || preg_match('/[\x00-\x1f\x7f\'`\´"]/', $ssid)) {
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
                    'protocol' => $current['security'] ?? 'OPEN',
                    'channel' => $channel,
                    'passphrase' => '',
                    'visible' => true,
                    'connected' => false,
                    'RSSI' => $rssi,
                    'index' => $index
                ];
                ++$index;
            }
        };

        foreach ($scan_results as $line) {
            $line = trim($line);

            if (preg_match('/^BSS\s+([0-9a-f:]{17})/', $line, $match)) {
                $commitCurrent(); // commit previous
                $current = [
                    'bssid' => $match[1],
                    'ssid' => '',
                    'signal' => null,
                    'freq' => null,
                    'security' => 'OPEN'
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
     *
     */
    public function connectedWifiStations(&$networks)
    {
        exec('iwconfig ' .$_SESSION['wifi_client_interface'], $iwconfig_return);
        foreach ($iwconfig_return as $line) {
            if (preg_match('/ESSID:\"([^"]+)\"/i', $line, $iwconfig_ssid)) {
                $ssid=hexSequence2lower($iwconfig_ssid[1]);
                $networks[$ssid]['connected'] = true;
                //$check=detectCaptivePortal($_SESSION['wifi_client_interface']);
                $networks[$ssid]["portal-url"]=$check["URL"];
            }
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
            $cmd = "sudo /sbin/wpa_supplicant -i $unescapedIface -c /etc/wpa_supplicant/wpa_supplicant.conf -B 2>&1";
            $result = shell_exec($cmd);
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

        if (strtolower($protocol) === 'open') {
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

    /*
     * Parses wpa_cli list_networks output and returns the id
     * of a corresponding network SSID
     *
     * @param string $ssid
     * @return integer id
     */
    public function getNetworkIdBySSID($ssid) {
        $iface = escapeshellarg($_SESSION['wifi_client_interface']);
        $cmd = "sudo wpa_cli -i $iface list_networks";
        $output = [];
        exec($cmd, $output);
        array_shift($output);
        foreach ($output as $line) {
            $columns = preg_split('/\t/', $line);
            if (count($columns) >= 4 && trim($columns[1]) === trim($ssid)) {
                return $columns[0]; // return network ID
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

}

