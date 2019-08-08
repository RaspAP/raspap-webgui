<?php

require('../../includes/csrf.php');
include_once('../../includes/config.php');
include_once('../../includes/functions.php');

$cacheTime = filemtime(RASPI_WPA_SUPPLICANT_CONFIG);
$cacheKey  = "wifi_stations_$cacheTime";

if (isset($_REQUEST["refresh"])) {
    deleteCache($cacheKey);
}

echo cache($cacheKey, function() {
    $networks = [];
    $network  = null;
    $ssid     = null;

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

    exec('sudo wpa_cli -i ' . RASPI_WIFI_CLIENT_INTERFACE . ' scan');
    sleep(3);
    exec('sudo wpa_cli -i ' . RASPI_WIFI_CLIENT_INTERFACE . ' scan_results', $scan_return);
    array_shift($scan_return);

    foreach ($scan_return as $network) {
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

    exec('iwconfig ' . RASPI_WIFI_CLIENT_INTERFACE, $iwconfig_return);
    foreach ($iwconfig_return as $line) {
        if (preg_match('/ESSID:\"([^"]+)\"/i', $line, $iwconfig_ssid)) {
            $networks[$iwconfig_ssid[1]]['connected'] = true;
        }
    }

    return renderTemplate('wifi_stations', compact('networks'));
});
