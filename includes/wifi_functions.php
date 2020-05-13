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

    // get the name of the AP - should be excluded von the nearby networks
	$pars=parse_ini_file(RASPI_HOSTAPD_CONFIG,false,INI_SCANNER_RAW );
	$ap_ssid = $pars['ssid'];
	
	foreach (explode("\n", $scan_results) as $network) {
		$arrNetwork = preg_split("/[\t]+/", $network);  // split result into array
        if (!array_key_exists(4, $arrNetwork) ||
            trim($arrNetwork[4]) == $ap_ssid) continue;

        $ssid = trim($arrNetwork[4]);
        // filter SSID string - anything invisable in 7bit ASCII or quotes -> ignore network
        if( preg_match('/[\x00-\x1f\x7f-\xff\'\`\´\"]/',$ssid)) continue;

        // If network is saved
        if (array_key_exists($ssid, $networks)) {
            $networks[$ssid]['visible'] = true;
            $networks[$ssid]['channel'] = ConvertToChannel($arrNetwork[1]);
            // TODO What if the security has changed?
        } else {
            $networks[$ssid] = array(
                'configured' => false,
                'protocol' => ConvertToSecurity($arrNetwork[3]),
                'channel' => ConvertToChannel($arrNetwork[1]),
                'passphrase' => '',
                'visible' => true,
                'connected' => false
            );
        }

        // Save RSSI, if the current value is larger than the already stored
        if (array_key_exists(4, $arrNetwork) && array_key_exists($arrNetwork[4],$networks)) {
            if(! array_key_exists('RSSI',$networks[$arrNetwork[4]]) || $networks[$ssid]['RSSI'] < $arrNetwork[2])
                $networks[$ssid]['RSSI'] = $arrNetwork[2];
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

function sortNetworksByRSSI(&$networks) {
        $valRSSI = array();
        foreach ($networks as $SSID => $net) {
                if (!array_key_exists('RSSI',$net)) $net['RSSI'] = -1000;
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
