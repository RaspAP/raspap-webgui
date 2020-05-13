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

echo renderTemplate('wifi_stations', compact('networks'));
