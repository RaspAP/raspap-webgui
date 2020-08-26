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
            // Validate input
            $good_input = true;
            $peer_id = 1;
            if (isset($_POST['peer_id'])) {
                $peer_id = escapeshellarg($_POST['peer_id']);
            }
            if (isset($_POST['wg_endpoint'])) {
                if (!filter_var($_POST['wg_endpoint'], FILTER_VALIDATE_IP)) {
                    $status->addMessage('Invalid value for endpoint address', 'danger');
                    $good_input = false;
                } else {
                    $wg_endpoint = escapeshellarg($_POST['wg_endpoint']);
                }
            }
            if (isset($_POST['wg_allowedips'])) {
                if (!filter_var($_POST['wg_allowedips'], FILTER_VALIDATE_IP)) {
                    $status->addMessage('Invalid value for allowed IPs', 'danger');
                    $good_input = false;
                } else {
                    $wg_allowedips = escapeshellarg($_POST['wg_allowedips']);
                }
            }
            if (isset($_POST['wg_pkeepalive'])) {
                if (strlen($_POST['wg_pkeepalive']) > 4 || !is_numeric($_POST['wg_pkeepalive'])) {
                    $status->addMessage('Invalid value for persistent keepalive', 'danger');
                    $good_input = false;
                } else {
                    $wg_pkeepalive = escapeshellarg($_POST['wg_pkeepalive']);
                }
            }
            if (isset($_POST['wg_peerpubkey'])) {
                $wg_endpoint = strip_tags(trim($_POST['wg_peerpubkey']));
            }
            // Save settings
            if ($good_input) {
                file_put_contents("/tmp/wgdata", $config);
                system('sudo cp /tmp/wgdata '.RASPI_WIREGUARD_CONFIG, $return);
                foreach ($return as $line) {
                    $status->addMessage($line, 'info');
                }
            }
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

    // fetch wg config
    exec('sudo cat '. RASPI_WIREGUARD_CONFIG, $return);
    $conf = ParseConfig($return);
    $wg_port = $conf['ListenPort'];
    $wg_ipaddress = $conf['Address'];
    $wg_pubkey = $conf['PublicKey'];
    $wg_endpoint = $conf['Endpoint'];
    $wg_allowedips = $conf['AllowedIPs'];
    $wg_pkeepalive = $conf['PersistentKeepalive'];

    // fetch service status
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
            "wg_port",
            "wg_ipaddress",
            "wg_pubkey",
            "wg_endpoint",
            "wg_allowedips",
            "wg_pkeepalive"
        )
    );
}

