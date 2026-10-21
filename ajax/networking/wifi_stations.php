<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/defaults.php';
require_once '../../includes/functions.php';

use RaspAP\Networking\Hotspot\WiFiManager;
use RaspAP\Networking\NetworkManagerClient;

$wifi = new WiFiManager();

$networks = [];
$network  = null;
$ssid     = null;

$wifi->knownWifiStations($networks);
$wifi->nearbyWifiStations($networks, !isset($_REQUEST["refresh"]));
$wifi->connectedWifiStations($networks);
$wifi->sortNetworksByRSSI($networks);

foreach ($networks as $ssid => $network) $networks[$ssid]["ssidutf8"] = $wifi->ssid2utf8( $ssid );

// When NetworkManager manages the client interface, the wpa_cli network IDs
// do not belong to the RaspAP networks. The forms then use a unique number
// for each network, and the actions use the SSID.
$nm = new NetworkManagerClient();
$clientIface = $_SESSION['wifi_client_interface'] ?? null;
$nmProfiles = [];
if ($nm->isManaged($clientIface)) {
    $i = 0;
    foreach ($networks as $ssid => $network) {
        $networks[$ssid]['index'] = $i++;
    }
    // NetworkManager connections that RaspAP did not create
    foreach ($nm->listProfiles($clientIface) as $profile) {
        if (!$profile['raspap']) {
            $profile['ssidutf8'] = $wifi->ssid2utf8($profile['ssid']);
            $nmProfiles[] = $profile;
        }
    }
}

$connected = array_filter($networks, function($n) { return $n['connected']; } );
$known     = array_filter($networks, function($n) { return !$n['connected'] && $n['configured']; } );
$nearby    = array_filter($networks, function($n) { return !$n['configured']; } );

echo renderTemplate('wifi_stations', compact('networks', 'connected', 'known', 'nearby', 'nmProfiles'), true);
