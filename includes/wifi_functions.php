<?php

require_once 'functions.php';

function knownWifiStations(&$networks)
{
    // Find currently configured networks
    exec(' sudo cat ' . RASPI_WPA_SUPPLICANT_CONFIG, $known_return);
    foreach ($known_return as $line) {
        if (preg_match('/network\s*=/', $line)) {
            $network = array('visible' => false, 'configured' => true, 'connected' => false);
        } elseif ($network !== null) {
            if (preg_match('/^\s*}\s*$/', $line)) {
                $networks[$ssid] = $network;
                $network = null;
                $ssid = null;
            } elseif ($lineArr = preg_split('/\s*=\s*/', trim($line))) {
                switch (strtolower($lineArr[0])) {
                case 'ssid':
                    $ssid = trim($lineArr[1], '"');
                    break;
                case 'psk':
                    if (array_key_exists('passphrase', $network)) {
                        break;
                    }
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

function nearbyWifiStations(&$networks, $cached = true)
{
    $cacheTime = filemtime(RASPI_WPA_SUPPLICANT_CONFIG);
    $cacheKey  = "nearby_wifi_stations_$cacheTime";

    if ($cached == false) {
        deleteCache($cacheKey);
    }

    $scan_results = cache(
        $cacheKey, function () {
            exec('sudo wpa_cli -i ' . RASPI_WIFI_CLIENT_INTERFACE . ' scan');
            sleep(3);

            exec('sudo wpa_cli -i ' . RASPI_WIFI_CLIENT_INTERFACE . ' scan_results', $stdout);
            array_shift($stdout);

            return implode("\n", $stdout);
        }
    );

    foreach (explode("\n", $scan_results) as $network) {
        $arrNetwork = preg_split("/[\t]+/", $network);  // split result into array

        // If network is saved
        if (array_key_exists(4, $arrNetwork) && array_key_exists($arrNetwork[4], $networks)) {
            $networks[$arrNetwork[4]]['visible'] = true;
            $networks[$arrNetwork[4]]['channel'] = ConvertToChannel($arrNetwork[1]);
            // TODO What if the security has changed?
        } else {
            $networks[$arrNetwork[4]] = array(
                'configured' => false,
                'protocol' => ConvertToSecurity($arrNetwork[3]),
                'channel' => ConvertToChannel($arrNetwork[1]),
                'passphrase' => '',
                'visible' => true,
                'connected' => false
            );
        }

        // Save RSSI
        if (array_key_exists(4, $arrNetwork)) {
            $networks[$arrNetwork[4]]['RSSI'] = $arrNetwork[2];
        }
    }
}

function connectedWifiStations(&$networks)
{
    exec('iwconfig ' . RASPI_WIFI_CLIENT_INTERFACE, $iwconfig_return);
    foreach ($iwconfig_return as $line) {
        if (preg_match('/ESSID:\"([^"]+)\"/i', $line, $iwconfig_ssid)) {
            $networks[$iwconfig_ssid[1]]['connected'] = true;
        }
    }
}
