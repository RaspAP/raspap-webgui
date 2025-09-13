<?php

require_once 'includes/config.php';

use RaspAP\Networking\Hotspot\WiFiManager;

$wifi = new WiFiManager();
$wifi->getWifiInterface();

/**
 * Displays wireguard server & peer configuration
 */
function DisplayWireGuardConfig()
{
    $parseFlag = true;
    $status = new \RaspAP\Messages\StatusMessage;
    if (!RASPI_MONITOR_ENABLED) {
        $optRules     = isset($_POST['wgRules']) ? $_POST['wgRules'] : null;
        $optInterface = isset($_POST['wgInterface']) ? $_POST['wgInterface'] : null;
        $optConf      = isset($_POST['wgCnfOpt']) ? $_POST['wgCnfOpt'] : null;
        $optSrvEnable = isset($_POST['wgSrvEnable']) ? $_POST['wgSrvEnable'] : null;
        $optLogEnable = isset($_POST['wgLogEnable']) ? $_POST['wgLogEnable'] : null;
        $optKSwitch   = isset($_POST['wgKSwitch']) ? $_POST['wgKSwitch'] : null;
        if (isset($_POST['savewgsettings']) && $optConf == 'manual' && $optSrvEnable == 1 ) {
            SaveWireGuardConfig($status);
        } elseif (isset($_POST['savewgsettings']) && $optConf == 'upload' && is_uploaded_file($_FILES["wgFile"]["tmp_name"])) {
            SaveWireGuardUpload($status, $_FILES['wgFile'], $optRules, $optKSwitch, $optInterface);
        } elseif (isset($_POST['savewgsettings']) && isset($_POST['wg_penabled']) ) {
            SaveWireGuardConfig($status);
        } elseif (isset($_POST['startwg'])) {
            $status->addMessage('Attempting to start WireGuard', 'info');
            exec('sudo /bin/systemctl enable wg-quick@wg0', $return);
            exec('sudo /bin/systemctl start wg-quick@wg0', $return);
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
        CheckWireGuardLog( $optLogEnable, $status );
    }

    // fetch server config
    exec('sudo cat '. RASPI_WIREGUARD_CONFIG, $return);
    $conf = ParseConfig($return, $parseFlag);
    $wg_srvpubkey = exec('sudo cat '. RASPI_WIREGUARD_PATH .'wg-server-public.key', $return);
    $wg_srvport = ($conf['ListenPort'] ?? '') === ''
        ? getDefaultNetValue('wireguard','server','ListenPort')
        : $conf['ListenPort'];
    $wg_srvipaddress = ($conf['Address'] == '') ? getDefaultNetValue('wireguard','server','Address') : $conf['Address'];
    $wg_srvdns = ($conf['DNS'] == '') ? getDefaultNetValue('wireguard','server','DNS') : $conf['DNS'];
    if (is_array($wg_srvdns)) {
        $wg_srvdns = implode(', ', $wg_srvdns);
    }
    $wg_peerpubkey = exec('sudo cat '. RASPI_WIREGUARD_PATH .'wg-peer-public.key', $return);
    if (sizeof($conf) >0) {
        $wg_senabled = true;
    }

    // fetch client config
    exec('sudo cat '. RASPI_WIREGUARD_PATH.'client.conf', $preturn);
    $conf = ParseConfig($preturn, $parseFlag);
    $wg_pipaddress = ($conf['Address'] == '') ? getDefaultNetValue('wireguard','peer','Address') : $conf['Address'];
    $wg_plistenport = ($conf['ListenPort'] == '') ? getDefaultNetValue('wireguard','peer','ListenPort') : $conf['ListenPort'];
    $wg_pendpoint = ($conf['Endpoint'] == '') ? getDefaultNetValue('wireguard','peer','Endpoint') : $conf['Endpoint'];
    $wg_pallowedips = ($conf['AllowedIPs'] == '') ? getDefaultNetValue('wireguard','peer','AllowedIPs') : $conf['AllowedIPs'];
    $wg_pkeepalive = ($conf['PersistentKeepalive'] == '') ? getDefaultNetValue('wireguard','peer','PersistentKeepalive') : $conf['PersistentKeepalive'];
    if (sizeof($conf) >0) {
        $wg_penabled = true;
    }

    // fetch service status
    exec('systemctl is-active wg-quick@wg0', $wgstatus);
    $serviceStatus = $wgstatus[0] == 'inactive' ? "down" : "up";
    $wg_state = ($wgstatus[0] == 'active' ? true : false );
    $public_ip = get_public_ip();

    // fetch uploaded file configs
    exec("sudo ls ".RASPI_WIREGUARD_PATH, $clist);
    $configs = preg_grep('/^((?!wg0).)*\.conf/', $clist);
    exec("sudo readlink ".RASPI_WIREGUARD_CONFIG." | xargs basename", $ret);
    $conf_default = empty($ret) ? "none" : $ret[0];

    // fetch wg log
    exec('sudo chmod o+r /tmp/wireguard.log');
    if (file_exists('/tmp/wireguard.log')) {
        $log = file_get_contents('/tmp/wireguard.log');
    } else {
        $log = '';
    }
    $peer_id = $peer_id ?? "1";

    // fetch available interfaces
    exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);
    sort($interfaces);

    echo renderTemplate(
        "wireguard", compact(
            "status",
            "wg_state",
            "serviceStatus",
            "public_ip",
            "interfaces",
            "optRules",
            "optKSwitch",
            "optLogEnable",
            "peer_id",
            "wg_srvpubkey",
            "wg_srvport",
            "wg_srvipaddress",
            "wg_srvdns",
            "wg_senabled",
            "wg_penabled",
            "wg_pipaddress",
            "wg_plistenport",
            "wg_peerpubkey",
            "wg_pendpoint",
            "wg_pallowedips",
            "wg_pkeepalive",
            "configs",
            "conf_default",
            "log"
        )
    );
}

/**
 * Validates uploaded .conf file, adds iptables post-up and
 * post-down rules.
 *
 * @param  object $status
 * @param  object $file
 * @param  boolean $optRules
 * @param  boolean $optKSwitch
 * @param  string $optInterface
 * @return object $status
 */
function SaveWireGuardUpload($status, $file, $optRules, $optKSwitch, $optInterface)
{
    define('KB', 1024);
    $tmp_destdir = '/tmp/';
    $auth_flag = 0;

    try {
        // If undefined or multiple files, treat as invalid
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters');
        }

        $upload = \RaspAP\Uploader\FileUpload::factory('wg',$tmp_destdir);
        $upload->set_max_file_size(64*KB);
        $upload->set_allowed_mime_types(array('text/plain'));
        $upload->file($file);

        $validation = new validation;
        $upload->callbacks($validation, array('check_name_length'));
        $results = $upload->upload();

        if (!empty($results['errors'])) {
            throw new RuntimeException($results['errors'][0]);
        }

        // Valid upload, get file contents
        $tmp_wgconfig = $results['full_path'];
        $tmp_contents = file_get_contents($tmp_wgconfig);

        // Check for existing iptables rules
        if ((isset($optRules) || isset($optKSwitch)) && preg_match('/PostUp|PostDown|PreDown/m',$tmp_contents)) {
            $status->addMessage('Existing iptables rules found in WireGuard configuration - not added', 'info');
        } else {
            // Set rules from default config
            if (isset($optRules)) {
                $rules[] = 'PostUp = '.getDefaultNetValue('wireguard','server','PostUp');
                $rules[] = 'PostDown = '.getDefaultNetValue('wireguard','server','PostDown');
                $rules = preg_replace('/wlan0/m', $optInterface, $rules);
            }
            if (isset($optKSwitch)) {
                // Get ap static ip_addr from system config, fallback to default if undefined
                $jsonData = json_decode(getNetConfig($optInterface), true);
                $ip_addr = ($jsonData['StaticIP'] == '') ? getDefaultNetValue('dhcp', $optInterface, 'static ip_address') : $jsonData['StaticIP'];
                $mask = ($jsonData['SubnetMask'] == '') ? getDefaultNetValue('dhcp', $optInterface, 'subnetmask') : $jsonData['SubnetMask'];

                // if empty, try to detect IP/mask from system
                if (empty($ip_addr) || empty($mask)) {
                    $ipDetails = shell_exec("ip -4 -o addr show dev " . escapeshellarg($optInterface));
                    if (preg_match('/inet (\d+\.\d+\.\d+\.\d+)\/(\d+)/', $ipDetails, $matches)) {
                        $ip_addr = $matches[1];
                        $cidr = $matches[2];
                    } else {
                        $ip_addr = '0.0.0.0';
                        $cidr = '24';
                    }
                } else {
                    $cidr = mask2cidr($mask);
                }
                $cidr_ip = strpos($ip_addr, '/') === false ? "$ip_addr/$cidr" : $ip_addr;

                $rules[] = 'PostUp = '.getDefaultNetValue('wireguard','server','PostUpEx');
                $rules[] = 'PreDown = '.getDefaultNetValue('wireguard','server','PreDown');
                $rules = preg_replace('/%s/m', $cidr_ip, $rules);
            }
            if ((isset($rules) && count($rules) > 0)) {
                $rules[] = '';
                $rules = join(PHP_EOL, $rules);
                $tmp_contents = preg_replace('/^\s*$/ms', $rules, $tmp_contents, 1);
                file_put_contents($tmp_wgconfig, $tmp_contents);
                $status->addMessage('iptables rules added to WireGuard configuration', 'info');
            }
        }

        // Move processed file from /tmp and create symlink
        $client_wg = RASPI_WIREGUARD_PATH.pathinfo($file['name'], PATHINFO_FILENAME).'.conf';
        chmod($tmp_wgconfig, 0644);
        system("sudo mv $tmp_wgconfig $client_wg", $return);
        system("sudo rm ".RASPI_WIREGUARD_CONFIG, $return);
        system("sudo ln -s $client_wg ".RASPI_WIREGUARD_CONFIG, $return);

        if ($return ==0) {
            $status->addMessage('WireGuard configuration uploaded successfully', 'info');
        } else {
            $status->addMessage('Unable to save WireGuard configuration', 'danger');
        }
        return $status;

    } catch (RuntimeException $e) {
        $status->addMessage($e->getMessage(), 'danger');
        return $status;
    }
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
    // Validate server input
    if ($_POST['wgSrvEnable'] == 1) {
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
        if (isset($_POST['wg_srvdns'])) {
            if (!filter_var($_POST['wg_srvdns'],FILTER_VALIDATE_IP)) {
                $status->addMessage('Invalid value for DNS', 'danger');
                $good_input = false;
            }
        }
    }
    // Validate peer input
    if ($_POST['wg_penabled'] == 1) {
        if (isset($_POST['wg_pipaddress'])) {
            if (!validateCidr($_POST['wg_pipaddress'])) {
                $status->addMessage('Invalid value for peer IP address', 'danger');
                $good_input = false;
            }
        }
        if (isset($_POST['wg_pendpoint']) && strlen(trim($_POST['wg_pendpoint']) >0 )) {
            $wg_pendpoint_seg = substr($_POST['wg_pendpoint'],0,strpos($_POST['wg_pendpoint'],':'));
            $host_port = explode(':', $wg_pendpoint_seg);
            $hostname = $host_port[0];
            if (!filter_var($hostname, FILTER_VALIDATE_IP) &&
                !filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
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
    }

    // Save settings
    if ($good_input) {
        // server (wg0.conf)
        if ($_POST['wgSrvEnable'] == 1) {
            // fetch server private key from filesytem
            $wg_srvprivkey = exec('sudo cat '. RASPI_WIREGUARD_PATH .'wg-server-private.key', $return);
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
        } else {
            # remove selected conf + keys
            system('sudo rm '. RASPI_WIREGUARD_PATH .'wg-server-private.key', $return);
            system('sudo rm '. RASPI_WIREGUARD_PATH .'wg-server-public.key', $return);
            system('sudo rm '. RASPI_WIREGUARD_CONFIG, $return);
        }
        // client1 (client.conf)
        if ($_POST['wg_penabled'] == 1) {
            // fetch peer private key from filesystem 
            $wg_peerprivkey = exec('sudo cat '. RASPI_WIREGUARD_PATH .'wg-peer-private.key', $return);
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
        } else {
            # remove selected conf + keys
            system('sudo rm '. RASPI_WIREGUARD_PATH .'wg-peer-private.key', $return);
            system('sudo rm '. RASPI_WIREGUARD_PATH .'wg-peer-public.key', $return);
            system('sudo rm '. RASPI_WIREGUARD_PATH.'client.conf', $return);
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

/**
 *
 * @return object $status
 */
function CheckWireGuardLog( $opt, $status )
{
   // handle log option
    if ( $opt == "1") {
        exec("sudo journalctl --identifier wg-quick > /tmp/wireguard.log");
        $status->addMessage('WireGuard debug log updated', 'success');
    }
    return $status;
}

