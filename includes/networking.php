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

    $routeInfo = getRouteInfo();

    echo renderTemplate("networking", compact("status", "interfaces", "routeInfo"));
}
