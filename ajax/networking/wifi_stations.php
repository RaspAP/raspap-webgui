<?php

require('../../includes/csrf.php');
include_once('../../includes/config.php');
include_once('../../includes/functions.php');
include_once('../../includes/wifi_functions.php');

$networks = [];
$network  = null;
$ssid     = null;

knownWifiStations($networks);
nearbyWifiStations($networks, !isset($_REQUEST["refresh"]));
connectedWifiStations($networks);

echo renderTemplate('wifi_stations', compact('networks'));
