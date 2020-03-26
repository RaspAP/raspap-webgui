<?php

require_once 'includes/status_messages.php';
require_once 'app/lib/system.php';
require_once 'config.php';

/**
 *
 *
 */
function DisplayAdBlockConfig()
{
    $status = new StatusMessages();
    $system = new System();

    exec('pidof dnsmasq | wc -l', $dnsmasq);
    $dnsmasq_state = ($dnsmasq[0] > 0);
    $serviceStatus = $dnsmasq_state ? "up" : "down";
    
    echo renderTemplate(
        "adblock", compact(
            "status",
            "serviceStatus"
        )
    );
}

/**
 * BZ: todo
 *
 */
function SaveAdBlockConfig()
{

}
