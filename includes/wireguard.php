<?php

require_once 'includes/status_messages.php';
require_once 'config.php';

/**
 * Manage WireGuard configuration
 */
function DisplayWireGuardConfig()
{
    $status = new StatusMessages();
    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['savewgettings'])) {
            if (isset($_POST['authUser'])) {
                $authUser = strip_tags(trim($_POST['authUser']));
            }
            if (isset($_POST['authPassword'])) {
                $authPassword = strip_tags(trim($_POST['authPassword']));
            }
        } elseif (isset($_POST['startwg'])) {
            $status->addMessage('Attempting to start WireGuard', 'info');
            exec('sudo /bin/systemctl start wg-quick@wg0', $return);
            exec('sudo /bin/systemctl enable wg-quick@wg0', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['stopwg'])) {
            $status->addMessage('Attempting to stop WireGuard', 'info');
            exec('sudo /bin/systemctl stop wg-quick@wg0', $return);
            exec('sudo /bin/systemctl disable wg-quick@wg0', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }

    exec('pidof wg | wc -l', $wgstatus);

    $serviceStatus = $wgstatus[0] == 0 ? "down" : "up";

    echo renderTemplate(
        "wireguard", compact(
            "status",
            "serviceStatus"
        )
    );
}

