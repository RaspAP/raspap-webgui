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
            # Todo: validate input
            if (isset($_POST['authUser'])) {
                $peer_id = strip_tags(trim($_POST'peer_id']));
            }
            if (isset($_POST['wg_endpoint'])) {
                $wg_endpoint = strip_tags(trim($_POST['wg_endpoint']));
            }
            if (isset($_POST['wg_allowedips'])) {
                $wg_allowedips = strip_tags(trim($_POST['wg_allowedips']));
            }
            if (isset($_POST['wg_pkeepalive'])) {
                $wg_pkeepalive = strip_tags(trim($_POST['wg_pkeepalive']));
            }
            if (isset($_POST['wg_peerpubkey'])) {
                $wg_endpoint = strip_tags(trim($_POST['wg_peerpubkey']));
            }
            file_put_contents("/tmp/wgdata", $config);
            system('sudo cp /tmp/wgdata '.RASPI_WIREGUARD_CONFIG, $return);

            if ($return == 0) {
                $status->addMessage('Wireguard configuration updated successfully', 'success');
            } else {
                $status->addMessage('Wireguard configuration failed to be updated.', 'danger');
            }

        } elseif (isset($_POST['startwg'])) {
            $status->addMessage('Attempting to start WireGuard', 'info');
            exec('sudo /usr/bin/wg-quick up wg0', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['stopwg'])) {
            $status->addMessage('Attempting to stop WireGuard', 'info');
            exec('sudo /usr/bin/wg-quick down wg0', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }

    exec('pidof wg-crypt-wg0 | wc -l', $wgstatus);

    $serviceStatus = $wgstatus[0] == 0 ? "down" : "up";
    $wg_state = ($wgstatus[0] > 0);

    echo renderTemplate(
        "wireguard", compact(
            "status",
            "wg_state",
            "serviceStatus",
            "endpoint_enable",
            "peer_id",
            "wg_endpoint",
            "wg_allowedips",
            "wg_pkeepalive",
            "wg_peerpubkey"
        )
    );
}

