<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

$liveForm = new \RaspAP\UI\LiveForm();
$liveForm->initAjax();
$liveForm->sendStartMessage();

try {

if (RASPI_MONITOR_ENABLED) {
    $liveForm->sendUpdateMessage(_('RaspAP Monitor Mode Enabled'), 100);
    $liveForm->saveStatusMessage(_('RaspAP Monitor Mode Enabled'), 'warning');
    $liveForm->sendCompleteMessage();
}

$optRules     = isset($_POST['wgRules']) ? $_POST['wgRules'] : null;
$optInterface = isset($_POST['wgInterface']) ? $_POST['wgInterface'] : null;
$optConf      = isset($_POST['wgCnfOpt']) ? $_POST['wgCnfOpt'] : null;
$optSrvEnable = isset($_POST['wgSrvEnable']) ? $_POST['wgSrvEnable'] : null;
$optLogEnable = isset($_POST['wgLogEnable']) ? $_POST['wgLogEnable'] : null;
$optKSwitch   = isset($_POST['wgKSwitch']) ? $_POST['wgKSwitch'] : null;

if (isset($_POST['savewgsettings'])) {
    $liveForm->sendUpdateMessage(_('Saving WireGuard configuration'), 10);

    if ($optConf == 'manual' && $optSrvEnable == 1 ) {
        SaveWireGuardConfig($liveForm, $optLogEnable);
    } elseif ($optConf == 'upload' && is_uploaded_file($_FILES["wgFile"]["tmp_name"])) {
        SaveWireGuardUpload($liveForm, $_FILES['wgFile'], $optRules, $optKSwitch, $optInterface, $optLogEnable);
    } elseif (isset($_POST['wg_penabled']) ) {
        SaveWireGuardConfig($liveForm, $optLogEnable);
    }

    $liveForm->sendUpdateMessage(null, 50);
    CheckWireGuardLog($optLogEnable, $liveForm);

    $liveForm->sendUpdateMessage(_('WireGuard configuration saved successfully'), 90);
    $liveForm->saveStatusMessage(_('WireGuard configuration saved successfully'), 'success', true);
    $liveForm->sendCompleteMessage();
} elseif (isset($_POST['startwg']) || isset($_POST['stopwg'])) {
    if (isset($_POST['startwg'])) {
        $liveForm->sendUpdateMessage(_('Attempting to start WireGuard'), 30);

        exec('sudo /bin/systemctl enable wg-quick@wg0', $return);
        exec('sudo /bin/systemctl start wg-quick@wg0', $return);
        foreach ($return as $line) {
            $liveForm->sendUpdateMessage($line);
        }

        $liveForm->sendUpdateMessage(_('Started WireGuard successfully'), 90);
        $liveForm->saveStatusMessage(_('Started WireGuard successfully'), 'success', true);
        $liveForm->sendCompleteMessage();
    } elseif (isset($_POST['stopwg'])) {
        $liveForm->sendUpdateMessage(_('Attempting to stop WireGuard'), 30);

        exec('sudo /bin/systemctl stop wg-quick@wg0', $return);
        exec('sudo /bin/systemctl disable wg-quick@wg0', $return);
        foreach ($return as $line) {
            $liveForm->sendUpdateMessage($line);
        }

        $liveForm->sendUpdateMessage(_('Stopped WireGuard successfully'), 90);
        $liveForm->saveStatusMessage(_('Stopped WireGuard successfully'), 'success', true);
        $liveForm->sendCompleteMessage();
    }
}

$liveForm->saveStatusMessage(_('No Instructions to Complete'), 'warning');
$liveForm->sendCompleteMessage();

} catch (\Throwable $e) {
    $liveForm->sendUpdateMessage(sprintf(_('An error occurred: %s'), $e->getMessage()), 100);
    $liveForm->saveStatusMessage(_('An error occurred'), 'danger', true);
    $liveForm->sendFailedMessage();
}

/**
 * Validates uploaded .conf file, adds iptables post-up and
 * post-down rules.
 *
 * @param  object $liveForm
 * @param  object $file
 * @param  boolean $optRules
 * @param  boolean $optKSwitch
 * @param  string $optInterface
 * @param  boolean $optLogEnable
 * @return object $status
 */
function SaveWireGuardUpload($liveForm, $file, $optRules, $optKSwitch, $optInterface, $optLogEnable)
{
    define('KB', 1024);
    $tmp_destdir = '/tmp/';
    $auth_flag = 0;

    // If undefined or multiple files, treat as invalid
    if (!isset($file['error']) || is_array($file['error'])) {
        $liveForm->sendUpdateMessage(_('Invalid file parameters'), 90);
        $liveForm->saveStatusMessage(_('Invalid file parameters'), 'danger', true);
        CheckWireGuardLog($optLogEnable, $liveForm);
        $liveForm->sendFailedMessage();
    }

    $liveForm->sendUpdateMessage(_('Validating uploaded file'), 30);

    $upload = \RaspAP\Uploader\FileUpload::factory('wg',$tmp_destdir);
    $upload->set_max_file_size(64*KB);
    $upload->set_allowed_mime_types(array('text/plain'));
    $upload->file($file);

    $validation = new validation;
    $upload->callbacks($validation, array('check_name_length'));
    $results = $upload->upload();

    if (!empty($results['errors'])) {
        $liveForm->sendUpdateMessage(_('Invalid file provided:'), 90);
        $liveForm->sendUpdateMessage($results['errors'][0]);
        $liveForm->saveStatusMessage(_('Invalid file provided'), 'danger', true);
        CheckWireGuardLog($optLogEnable, $liveForm);
        $liveForm->sendFailedMessage();
    }

    // Valid upload, get file contents
    $tmp_wgconfig = $results['full_path'];
    $tmp_contents = file_get_contents($tmp_wgconfig);

    // Check for existing iptables rules
    if ((isset($optRules) || isset($optKSwitch)) && preg_match('/PostUp|PostDown|PreDown/m',$tmp_contents)) {
        $liveForm->sendUpdateMessage(_('Existing iptables rules found in WireGuard configuration - Skipping'));
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
            $ip_addr = ($jsonData['StaticIP'] ?? '') === '' ? getDefaultNetValue('dhcp', $optInterface, 'static ip_address') : $jsonData['StaticIP'];
            $mask = ($jsonData['SubnetMask'] ?? '') === '' ? getDefaultNetValue('dhcp', $optInterface, 'subnetmask') : $jsonData['SubnetMask'];

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

            $liveForm->sendUpdateMessage(_('iptables rules added to WireGuard configuration'));
        }
    }

    $liveForm->sendUpdateMessage(_('Installing uploaded WireGuard config'), 80);
    // Sanitize, move processed file from /tmp and create symlink
    $stem = preg_replace('/[^A-Za-z0-9\-_]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $client_wg = RASPI_WIREGUARD_PATH.$stem.'.conf';
    chmod($tmp_wgconfig, 0644);
    system("sudo mv ".escapeshellarg($tmp_wgconfig)." ".escapeshellarg($client_wg), $return);
    system("sudo rm ".RASPI_WIREGUARD_CONFIG, $return);
    system("sudo ln -s ".escapeshellarg($client_wg)." ".RASPI_WIREGUARD_CONFIG, $return);

    if ($return ==0) {
        $liveForm->sendUpdateMessage(_('WireGuard configuration uploaded successfully'), 90);
    } else {
        $liveForm->sendUpdateMessage(_('Unable to save WireGuard configuration'), 90);
        $liveForm->saveStatusMessage(_('Unable to save WireGuard configuration'), 'danger', true);
        CheckWireGuardLog($optLogEnable, $liveForm);
        $liveForm->sendFailedMessage();
    }

    $liveForm->saveStatusMessage(_('Saved settings successfully'), 'success', true);
    CheckWireGuardLog($optLogEnable, $liveForm);
    $liveForm->sendCompleteMessage();
}

/**
 * Validate user input, save wireguard configuration
 *
 * @param object $liveForm
 * @param boolean $optLogEnable
 * @return boolean
 */
function SaveWireGuardConfig($liveForm, $optLogEnable)
{
    // Set defaults
    $good_input = true;
    $peer_id = 1;

    // Validate server input
    if ($_POST['wgSrvEnable'] == 1) {
        $liveForm->sendUpdateMessage(_('Validating server inputs'), 20);
        if (isset($_POST['wg_srvport'])) {
            if (strlen($_POST['wg_srvport']) > 5 || !is_numeric($_POST['wg_srvport'])) {
                $liveForm->sendUpdateMessage(_('Invalid value for server local port'));
                $good_input = false;
            }
        }
        if (isset($_POST['wg_plistenport'])) {
            if (strlen($_POST['wg_plistenport']) > 5 || !is_numeric($_POST['wg_plistenport'])) {
                $liveForm->sendUpdateMessage(_('Invalid value for peer local port'));
                $good_input = false;
            }
        }
        if (isset($_POST['wg_srvipaddress'])) {
            if (!validateCidr($_POST['wg_srvipaddress'])) {
                $liveForm->sendUpdateMessage(_('Invalid value for server IP address'));
                $good_input = false;
            }
        }
        if (isset($_POST['wg_srvdns'])) {
            if (!filter_var($_POST['wg_srvdns'],FILTER_VALIDATE_IP)) {
                $liveForm->sendUpdateMessage(_('Invalid value for DNS'));
                $good_input = false;
            }
        }
    }

    // Validate peer input
    if ($_POST['wg_penabled'] == 1) {
        $liveForm->sendUpdateMessage(_('Validating peer inputs'), 30);
        if (isset($_POST['wg_pipaddress'])) {
            if (!validateCidr($_POST['wg_pipaddress'])) {
                $liveForm->sendUpdateMessage(_('Invalid value for peer IP address'));
                $good_input = false;
            }
        }
        if (isset($_POST['wg_pendpoint']) && strlen(trim($_POST['wg_pendpoint']) >0 )) {
            $wg_pendpoint_seg = substr($_POST['wg_pendpoint'],0,strpos($_POST['wg_pendpoint'],':'));
            $host_port = explode(':', $wg_pendpoint_seg);
            $hostname = $host_port[0];
            if (
                !filter_var($hostname, FILTER_VALIDATE_IP) &&
                !filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            ) {
                $liveForm->sendUpdateMessage(_('Invalid value for endpoint address'));
                $good_input = false;
            }
        }
        if (isset($_POST['wg_pallowedips']) && strlen(trim($_POST['wg_pallowedips']) >0)) {
            if (!validateCidr($_POST['wg_pallowedips'])) {
                $liveForm->sendUpdateMessage(_('Invalid value for allowed IPs'));
                $good_input = false;
            }
        }
        if (isset($_POST['wg_pkeepalive']) && strlen(trim($_POST['wg_pkeepalive']) >0 )) {
            if (strlen($_POST['wg_pkeepalive']) > 4 || !is_numeric($_POST['wg_pkeepalive'])) {
                $liveForm->sendUpdateMessage(_('Invalid value for persistent keepalive'));
                $good_input = false;
            }
        }
    }

    // Save settings
    if ($good_input) {
        $liveForm->sendUpdateMessage(_('Inputs Valid'));
        // server (wg0.conf)
        if ($_POST['wgSrvEnable'] == 1) {
            $liveForm->sendUpdateMessage(_('Enabling Server'), 40);
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
            $liveForm->sendUpdateMessage(_('Disabling Server'), 40);
            # remove selected conf + keys
            system('sudo rm '. RASPI_WIREGUARD_PATH .'wg-server-private.key', $return);
            system('sudo rm '. RASPI_WIREGUARD_PATH .'wg-server-public.key', $return);
            system('sudo rm '. RASPI_WIREGUARD_CONFIG, $return);
        }
        // client1 (client.conf)
        if ($_POST['wg_penabled'] == 1) {
            $liveForm->sendUpdateMessage(_('Enabling Peer'), 50);
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
            $liveForm->sendUpdateMessage(_('Disabling Peer'), 50);
            # remove selected conf + keys
            system('sudo rm '. RASPI_WIREGUARD_PATH .'wg-peer-private.key', $return);
            system('sudo rm '. RASPI_WIREGUARD_PATH .'wg-peer-public.key', $return);
            system('sudo rm '. RASPI_WIREGUARD_PATH.'client.conf', $return);
        }

        foreach ($return as $line) {
            $liveForm->sendUpdateMessage($line);
        }

        if ($return == 0) {
            $liveForm->sendUpdateMessage(_('WireGuard configuration updated successfully'), 90);
            $liveForm->saveStatusMessage('WireGuard configuration updated successfully', 'success', true);
            CheckWireGuardLog($optLogEnable, $liveForm);
            $liveForm->sendCompleteMessage();
        } else {
            $liveForm->sendUpdateMessage(_('WireGuard configuration failed to be updated'), 90);
            $liveForm->saveStatusMessage(_('WireGuard configuration failed to be updated'), 'danger', true);
            CheckWireGuardLog($optLogEnable, $liveForm);
            $liveForm->sendFailedMessage();
        }
    } else {
        $liveForm->sendUpdateMessage(_('Unable to save WireGurad config due to invalid input'), 90);
        $liveForm->saveStatusMessage(_('Unable to save WireGurad config due to invalid input'), 'danger', true);
        CheckWireGuardLog($optLogEnable, $liveForm);
        $liveForm->sendFailedMessage();
    }
}

function CheckWireGuardLog($opt, $liveForm)
{
   // handle log option
    if ($opt != '1') {
        $liveForm->sendUpdateMessage(_('Clearing WireGuard log'), 20);
        $f = @fopen("/tmp/wireguard.log", "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }
    } elseif ($opt == "1" || (file_exists('/tmp/wireguard.log') && filesize('/tmp/wireguard.log') > 0)) {
        exec("sudo journalctl --identifier wg-quick > /tmp/wireguard.log");
        $liveForm->sendUpdateMessage(_('WireGuard debug log updated'));
    }
}
