<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../includes/defaults.php';
require_once '../../includes/functions.php';
require_once '../../includes/wifi_functions.php';

$networks = [];
$network  = null;
$ssid     = null;

knownWifiStations($networks);
nearbyWifiStations($networks, !isset($_REQUEST["refresh"]));
connectedWifiStations($networks);
sortNetworksByRSSI($networks);
foreach ($networks as $ssid => $network) $networks[$ssid]["ssidutf8"] = ssid2utf8( $ssid ); 

$connected = array_filter($networks, function($n) { return $n['connected']; } );
$known     = array_filter($networks, function($n) { return !$n['connected'] && $n['configured']; } );
$nearby    = array_filter($networks, function($n) { return !$n['configured']; } );

echo renderTemplate('wifi_stations', compact('networks', 'connected', 'known', 'nearby'), true);
