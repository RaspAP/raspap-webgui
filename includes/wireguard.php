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
        if (isset($_POST['savewgsettings'])) {
            // Set defaults
            $good_input = true;
            $peer_id = 1;
            // Validate input
            if (isset($_POST['wg_port'])) {
                if (strlen($_POST['wg_port']) > 5 || !is_numeric($_POST['wg_port'])) {
                    $status->addMessage('Invalid value for port number', 'danger');
                    $good_input = false;
                }
            }
            if (isset($_POST['wg_ipaddress'])) {
                if (!validateCidr($_POST['wg_ipaddress'])) {
                    $status->addMessage('Invalid value for IP address', 'danger');
                    $good_input = false;
                }
            }
            if (isset($_POST['wg_endpoint']) && strlen(trim($_POST['wg_endpoint']) >0 )) {
                if (!validateCidr($_POST['wg_endpoint'])) {
                    $status->addMessage('Invalid value for endpoint address', 'danger');
                    $good_input = false;
                }
            }
            if (isset($_POST['wg_allowedips'])) {
                if (!validateCidr($_POST['wg_allowedips'])) {
                    $status->addMessage('Invalid value for allowed IPs', 'danger');
                    $good_input = false;
                }
            }
            if (isset($_POST['wg_pkeepalive']) && strlen(trim($_POST['wg_pkeepalive']) >0 )) {
                if (strlen($_POST['wg_pkeepalive']) > 4 || !is_numeric($_POST['wg_pkeepalive'])) {
                    $status->addMessage('Invalid value for persistent keepalive', 'danger');
                    $good_input = false;
                }
            }
            // Save settings
            if ($good_input) {
                $config[] = '[Interface]';
                $config[] = 'Address = '.$_POST['wg_ipaddress'];
                $config[] = 'ListenPort = '.$_POST['wg_port'];

                $config[] = '';
                $config[] = 'PrivateKey = '.$_POST['wg_privkey'];
                $config[] = 'PostUp = iptables -A FORWARD -i %i -j ACCEPT; iptables -A FORWARD -o %i -j ACCEPT; iptables -t nat -A POSTROUTING -o eth0 -j MASQUERADE';
                $config[] = 'PostDown = iptables -D FORWARD -i %i -j ACCEPT; iptables -D FORWARD -o %i -j ACCEPT; iptables -t nat -D POSTROUTING -o eth0 -j MASQUERADE';
                $config[] = '';
                $config[] = '[Peer]';
                $config[] = 'PublicKey = '.$_POST['wg_pubkey'];
                if ($_POST['wg_endpoint'] !== '') {
                    $config[] = 'Endpoint = '.trim($_POST['wg_endpoint']);
                }
                $config[] = 'AllowedIPs = '.$_POST['wg_allowedips'];
                if ($_POST['wg_pkeepalive'] !== '') {
                    $config[] = 'PersistentKeepalive = '.trim($_POST['wg_pkeepalive']);
                }
                $config[] = '';
                $config = join(PHP_EOL, $config);

                file_put_contents("/tmp/wgdata", $config);
                system('sudo cp /tmp/wgdata '.RASPI_WIREGUARD_CONFIG, $return);
                
                // handle log option
                if ($_POST['wg_log'] == "1") {
                    exec("sudo /bin/systemctl status wg-quick@wg0 | sudo tee /tmp/wireguard.log > /dev/null");
                }
                foreach ($return as $line) {
                    $status->addMessage($line, 'info');
                }
                if ($return == 0) {
                    $status->addMessage('Wireguard configuration updated successfully', 'success');
                } else {
                    $status->addMessage('Wireguard configuration failed to be updated.', 'danger');
                }
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
    $wg_srvport = ($conf['ListenPort'] == '') ? getDefaultNetValue('wireguard','server','ListenPort') : $conf['ListenPort'];
    $wg_srvipaddress = ($conf['Address'] == '') ? getDefaultNetValue('wireguard','server','Address') : $conf['Address'];
    $wg_srvpubkey = $conf['PublicKey'];
    $wg_srvprivkey = $conf['PrivateKey'];
    $wg_pendpoint = ($conf['Endpoint'] == '') ? getDefaultNetValue('wireguard','peer','Endpoint') : $conf['Endpoint'];
    $wg_pallowedips = ($conf['AllowedIPs'] == '') ? getDefaultNetValue('wireguard','peer','AllowedIPs') : $conf['AllowedIPs'];
    $wg_pkeepalive = ($conf['PersistentKeepalive'] == '') ? getDefaultNetValue('wireguard','peer','PersistentKeepalive') : $conf['PersistentKeepalive'];

    // fetch service status
    exec('pidof wg-crypt-wg0 | wc -l', $wgstatus);
    $serviceStatus = $wgstatus[0] == 0 ? "down" : "up";
    $wg_state = ($wgstatus[0] > 0);

    echo renderTemplate(
        "wireguard", compact(
            "status",
            "wg_state",
            "serviceStatus",
            "wg_log",
            "endpoint_enable",
            "peer_id",
            "wg_srvport",
            "wg_srvipaddress",
            "wg_srvpubkey",
            "wg_srvprivkey",
            "wg_pendpoint",
            "wg_pallowedips",
            "wg_pkeepalive"
        )
    );
}

