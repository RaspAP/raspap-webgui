<?php

require_once 'includes/status_messages.php';
require_once 'includes/internetRoute.php';
require_once 'includes/functions.php';
require_once 'includes/get_clients.php';

/**
 *
 *
 */
function DisplayNetworkingConfig()
{

    $status = new StatusMessages();

    exec("ls /sys/class/net | grep -v lo", $interfaces);
    $routeInfo = getRouteInfo(true);
    $arrHostapdConf = parse_ini_file(RASPI_CONFIG.'/hostapd.ini');
    $bridgedEnabled = $arrHostapdConf['BridgedEnable'];

    foreach ($interfaces as $interface) {
        exec("ip a show $interface", $$interface);
    }
    loadClientConfig();
    $clients=getClients();
    echo renderTemplate("networking", compact(
        "status",
        "interfaces",
        "routeInfo",
        "bridgedEnabled",
		"clients")
    );
}

?>