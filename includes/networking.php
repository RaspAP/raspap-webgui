<?php

require_once 'includes/internetRoute.php';

/**
 *
 *
 */
function DisplayNetworkingConfig()
{
    $status = new \RaspAP\Messages\StatusMessage;

    exec("ls /sys/class/net | grep -v lo", $interfaces);
    $routeInfo = getRouteInfo(true);
    $routeInfoRaw = getRouteInfoRaw();
    $arrHostapdConf = parse_ini_file(RASPI_CONFIG.'/hostapd.ini');
    $bridgedEnabled = $arrHostapdConf['BridgedEnable'];

    echo renderTemplate("networking", compact(
        "status",
        "interfaces",
        "routeInfo",
        "routeInfoRaw",
        "bridgedEnabled")
    );
}
