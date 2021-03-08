<?php

require_once 'includes/status_messages.php';
require_once 'config.php';

/**
 * Displays wireguard server & peer configuration
 */
function DisplayWireGuardConfig()
{
    $status = new StatusMessages();
    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['savewgsettings'])) {
            SaveWireGuardConfig($status);
        } elseif (isset($_POST['startwg'])) {
            $status->addMessage('Attempting to start WireGuard', 'info');
            exec('sudo /bin/systemctl start wg-quick@wg0', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['stopwg'])) {
            $status->addMessage('Attempting to stop WireGuard', 'info');
            exec('sudo /bin/systemctl stop wg-quick@wg0', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }

    // fetch wg config
    exec('sudo cat '. RASPI_WIREGUARD_CONFIG, $return);
    $conf = ParseConfig($return);
    $wg_srvpubkey = exec('sudo cat '. RASPI_WIREGUARD_PATH .'wg-server-public.key', $return);
    $wg_srvport = ($conf['ListenPort'] == '') ? getDefaultNetValue('wireguard','server','ListenPort') : $conf['ListenPort'];
    $wg_srvipaddress = ($conf['Address'] == '') ? getDefaultNetValue('wireguard','server','Address') : $conf['Address'];
    $wg_srvdns = ($conf['DNS'] == '') ? getDefaultNetValue('wireguard','server','DNS') : $conf['DNS'];
    $wg_peerpubkey = $conf['PublicKey'];

    // todo: iterate multiple peer configs
    exec('sudo cat '. RASPI_WIREGUARD_PATH.'client.conf', $preturn);
    $conf = ParseConfig($preturn);
    $wg_pipaddress = ($conf['Address'] == '') ? getDefaultNetValue('wireguard','peer','Address') : $conf['Address'];
    $wg_plistenport = ($conf['ListenPort'] == '') ? getDefaultNetValue('wireguard','peer','ListenPort') : $conf['ListenPort'];
    $wg_pendpoint = ($conf['Endpoint'] == '') ? getDefaultNetValue('wireguard','peer','Endpoint') : $conf['Endpoint'];
    $wg_pallowedips = ($conf['AllowedIPs'] == '') ? getDefaultNetValue('wireguard','peer','AllowedIPs') : $conf['AllowedIPs'];
    $wg_pkeepalive = ($conf['PersistentKeepalive'] == '') ? getDefaultNetValue('wireguard','peer','PersistentKeepalive') : $conf['PersistentKeepalive'];
    if (sizeof($conf) >0) {
        $wg_penabled = true;
    }

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
            "peer_id",
            "wg_srvpubkey",
            "wg_srvport",
            "wg_srvipaddress",
            "wg_srvdns",
            "wg_penabled",
            "wg_pipaddress",
            "wg_plistenport",
            "wg_peerpubkey",
            "wg_pendpoint",
            "wg_pallowedips",
            "wg_pkeepalive"
        )
    );
}

/**
 * Validate user input, save wireguard configuration
 *
 * @param object $status
 * @return boolean
 */
function SaveWireGuardConfig($status)
{
    // Set defaults
    $good_input = true;
    $peer_id = 1;
    // Validate input
    if (isset($_POST['wg_srvport'])) {
        if (strlen($_POST['wg_srvport']) > 5 || !is_numeric($_POST['wg_srvport'])) {
            $status->addMessage('Invalid value for server local port', 'danger');
            $good_input = false;
        }
    }
    if (isset($_POST['wg_plistenport'])) {
        if (strlen($_POST['wg_plistenport']) > 5 || !is_numeric($_POST['wg_plistenport'])) {
            $status->addMessage('Invalid value for peer local port', 'danger');
            $good_input = false;
        }
    }
    if (isset($_POST['wg_srvipaddress'])) {
        if (!validateCidr($_POST['wg_srvipaddress'])) {
            $status->addMessage('Invalid value for server IP address', 'danger');
            $good_input = false;
        }
    }
    if (isset($_POST['wg_pipaddress'])) {
        if (!validateCidr($_POST['wg_pipaddress'])) {
            $status->addMessage('Invalid value for peer IP address', 'danger');
            $good_input = false;
        }
    }
    if (isset($_POST['wg_srvdns'])) {
        if (!filter_var($_POST['wg_srvdns'],FILTER_VALIDATE_IP)) {
            $status->addMessage('Invalid value for DNS', 'danger');
            $good_input = false;
        }
    }
    if (isset($_POST['wg_pendpoint']) && strlen(trim($_POST['wg_pendpoint']) >0 )) {
        $wg_pendpoint_seg = substr($_POST['wg_pendpoint'],0,strpos($_POST['wg_pendpoint'],':'));
        if (!filter_var($wg_pendpoint_seg,FILTER_VALIDATE_IP)) {
            $status->addMessage('Invalid value for endpoint address', 'danger');
            $good_input = false;
        }
    }
    if (isset($_POST['wg_pallowedips']) && strlen(trim($_POST['wg_pallowedips']) >0)) {
        if (!validateCidr($_POST['wg_pallowedips'])) {
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
        // fetch private keys from filesytem
        $wg_srvprivkey = exec('sudo cat '. RASPI_WIREGUARD_PATH .'wg-server-private.key', $return);
        $wg_peerprivkey = exec('sudo cat '. RASPI_WIREGUARD_PATH .'wg-peer-private.key', $return);

        // server (wg0.conf)
        $config[] = '[Interface]';
        $config[] = 'Address = '.$_POST['wg_srvipaddress'];
        $config[] = 'ListenPort = '.$_POST['wg_srvport'];
        $config[] = 'DNS = '.$_POST['wg_srvdns'];
        $config[] = 'PrivateKey = '.$wg_srvprivkey;
        $config[] = 'PostUp = '.getDefaultNetValue('wireguard','server','PostUp');
        $config[] = 'PostDown = '.getDefaultNetValue('wireguard','server','PostDown');
        $config[] = '';
        $config[] = '[Peer]';
        $config[] = 'PublicKey = '.$_POST['wg-peer'];
        $config[] = 'AllowedIPs = '.$_POST['wg_pallowedips'];
        if ($_POST['wg_pkeepalive'] !== '') {
            $config[] = 'PersistentKeepalive = '.trim($_POST['wg_pkeepalive']);
        }
        $config[] = '';
        $config = join(PHP_EOL, $config);

        file_put_contents("/tmp/wgdata", $config);
        system('sudo cp /tmp/wgdata '.RASPI_WIREGUARD_CONFIG, $return);

        // client1 (client.conf)
        $config = [];
        $config[] = '[Interface]';
        $config[] = 'Address = '.trim($_POST['wg_pipaddress']);
        $config[] = 'PrivateKey = '.$wg_peerprivkey;
        $config[] = 'ListenPort = '.$_POST['wg_plistenport'];
        $config[] = '';
        $config[] = '[Peer]';
        $config[] = 'PublicKey = '.$_POST['wg-server'];
        $config[] = 'AllowedIPs = '.$_POST['wg_pallowedips'];
        $config[] = 'Endpoint = '.$_POST['wg_pendpoint'];
        if ($_POST['wg_pkeepalive'] !== '') {
            $config[] = 'PersistentKeepalive = '.trim($_POST['wg_pkeepalive']);
        }
        $config[] = '';
        $config = join(PHP_EOL, $config);

        file_put_contents("/tmp/wgdata", $config);
        system('sudo cp /tmp/wgdata '.RASPI_WIREGUARD_PATH.'client.conf', $return);

        // handle log option
        if ($_POST['wg_log'] == "1") {
            exec("sudo /bin/systemctl status wg-quick@wg0 | sudo tee /tmp/wireguard.log > /dev/null");
        }
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }
        if ($return == 0) {
            $status->addMessage('WireGuard configuration updated successfully', 'success');
        } else {
            $status->addMessage('WireGuard configuration failed to be updated', 'danger');
        }
    }
}

