<?php

require_once 'includes/status_messages.php';
require_once 'includes/internetRoute.php';

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

    echo renderTemplate("networking", compact(
        "status",
        "interfaces",
        "routeInfo",
        "bridgedEnabled")
    );
}
