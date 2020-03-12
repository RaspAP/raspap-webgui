<?php

require_once 'includes/status_messages.php';

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
    echo renderTemplate("networking", compact("status", "interfaces"));
}
