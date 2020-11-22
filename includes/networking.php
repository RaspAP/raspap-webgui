<?php

require_once 'includes/status_messages.php';
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

    foreach ($interfaces as $interface) {
        exec("ip a show $interface", $$interface);
    }
    load_client_config();
    $clients=getClients();
    echo renderTemplate("networking", compact("status", "interfaces", "clients"));
}

?>
