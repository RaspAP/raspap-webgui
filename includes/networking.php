<?php

require_once 'includes/internetRoute.php';
require_once 'includes/functions.php';

/**
 * Displays a networking summary and network diagnostic tools
 */
function DisplayNetworkingConfig(&$extraFooterScripts)
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
    $extraFooterScripts[] = array('src'=>'app/js/vendor/speedtestUI.js', 'defer'=>false);
}
