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
            exec('sudo wpa_cli -i ' .$_SESSION['wifi_client_interface']. ' scan');
            sleep(3);

            exec('sudo wpa_cli -i ' .$_SESSION['wifi_client_interface']. ' scan_results', $stdout);
            array_shift($stdout);

            return implode("\n", $stdout);
        }
    );

    // get the name of the AP. Should be excluded from nearby networks
    exec('cat '.RASPI_HOSTAPD_CONFIG.' | sed -rn "s/ssid=(.*)\s*$/\1/p" ', $ap_ssid);
    $ap_ssid = $ap_ssid[0];

    foreach (explode("\n", $scan_results) as $network) {
        $arrNetwork = preg_split("/[\t]+/", $network);  // split result into array
        if (!array_key_exists(4, $arrNetwork) ||
            trim($arrNetwork[4]) == $ap_ssid) {
            continue;
        }

        $ssid = trim($arrNetwork[4]);
        // filter SSID string: anything invisible in 7bit ASCII or quotes -> ignore network
        if (preg_match('[\x00-\x1f\x7f-\xff\'\`\´\"]', $ssid)) {
            continue;
        }

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
        if (array_key_exists(4, $arrNetwork) && array_key_exists($arrNetwork[4], $networks)) {
            if (! array_key_exists('RSSI', $networks[$arrNetwork[4]]) || $networks[$ssid]['RSSI'] < $arrNetwork[2]) {
                $networks[$ssid]['RSSI'] = $arrNetwork[2];
            }
        }
    }
}

function connectedWifiStations(&$networks)
{
    exec('iwconfig ' .$_SESSION['wifi_client_interface'], $iwconfig_return);
    foreach ($iwconfig_return as $line) {
        if (preg_match('/ESSID:\"([^"]+)\"/i', $line, $iwconfig_ssid)) {
            $networks[$iwconfig_ssid[1]]['connected'] = true;
        }
    }
}

function sortNetworksByRSSI(&$networks)
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
function getWifiInterface()
{
        $arrHostapdConf = parse_ini_file(RASPI_CONFIG.'/hostapd.ini');
        $iface = $_SESSION['ap_interface'] = isset($arrHostapdConf['WifiInterface']) ?  $arrHostapdConf['WifiInterface'] : RASPI_WIFI_AP_INTERFACE;
        // check for 2nd wifi interface -> wifi client on different interface
        exec("iw dev | awk '$1==\"Interface\" && $2!=\"$iface\" {print $2}'",$iface2);
        $client_iface = $_SESSION['wifi_client_interface'] = (empty($iface2) ? $iface : trim($iface2[0]));

        // specifically for rpi0W in AP-STA mode, the above check ends up with the interfaces
        // crossed over (wifi_client_interface vs 'ap_interface'), because the second interface (uap0) is 
        // created by raspap and used as the access point.
        if ($client_iface == "uap0"  && ($arrHostapdConf['WifiAPEnable'] ?? 0)){
            $_SESSION['wifi_client_interface'] = $iface;
            $_SESSION['ap_interface'] = $client_iface; 
        } 
}

