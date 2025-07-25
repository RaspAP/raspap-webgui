<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/defaults.php';
require_once '../../includes/functions.php';

use RaspAP\Networking\Hotspot\WiFiManager;

$wifi = new WiFiManager();

$networks = [];
$network  = null;
$ssid     = null;

$wifi->knownWifiStations($networks);
$wifi->nearbyWifiStations($networks, !isset($_REQUEST["refresh"]));
$wifi->connectedWifiStations($networks);
$wifi->sortNetworksByRSSI($networks);

foreach ($networks as $ssid => $network) $networks[$ssid]["ssidutf8"] = $wifi->ssid2utf8( $ssid );

$connected = array_filter($networks, function($n) { return $n['connected']; } );
$known     = array_filter($networks, function($n) { return !$n['connected'] && $n['configured']; } );
$nearby    = array_filter($networks, function($n) { return !$n['configured']; } );

echo renderTemplate('wifi_stations', compact('networks', 'connected', 'known', 'nearby'), true);
