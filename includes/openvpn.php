<?php

require_once 'includes/status_messages.php';
require_once 'includes/config.php';
require_once 'includes/wifi_functions.php';
require_once 'app/lib/uploader.php';

getWifiInterface();

/**
 * Manage OpenVPN configuration
 */
function DisplayOpenVPNConfig()
{
    $status = new StatusMessages();
    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveOpenVPNSettings'])) {
            if (isset($_POST['authUser'])) {
                $authUser = strip_tags(trim($_POST['authUser']));
            }
            if (isset($_POST['authPassword'])) {
                $authPassword = strip_tags(trim($_POST['authPassword']));
            }
            if (is_uploaded_file( $_FILES["customFile"]["tmp_name"])) {
                $return = SaveOpenVPNConfig($status, $_FILES['customFile'], $authUser, $authPassword);
            }
        } elseif (isset($_POST['StartOpenVPN'])) {
            $status->addMessage('Attempting to start OpenVPN', 'info');
            exec('sudo /bin/systemctl start openvpn-client@client', $return);
            exec('sudo /bin/systemctl enable openvpn-client@client', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopOpenVPN'])) {
            $status->addMessage('Attempting to stop OpenVPN', 'info');
            exec('sudo /bin/systemctl stop openvpn-client@client', $return);
            exec('sudo /bin/systemctl disable openvpn-client@client', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }

    exec('pidof openvpn | wc -l', $openvpnstatus);
    $serviceStatus = $openvpnstatus[0] == 0 ? "down" : "up";
    $auth = file(RASPI_OPENVPN_CLIENT_LOGIN, FILE_IGNORE_NEW_LINES);
    $public_ip = get_public_ip();

    // parse client auth credentials
    if (!empty($auth)) {
        $auth = array_filter($auth, 'filter_comments');
        $authUser = current($auth);
        $authPassword = next($auth);
    }
    $clients = preg_grep('/_client.(conf)$/', scandir(pathinfo(RASPI_OPENVPN_CLIENT_CONFIG, PATHINFO_DIRNAME)));
    exec("readlink ".RASPI_OPENVPN_CLIENT_CONFIG." | xargs basename", $ret);
    $conf_default =  empty($ret) ? "none" : $ret[0];

    $logEnable = 0;
    if (!empty($_POST) && !isset($_POST['log-openvpn'])) {
        $logOutput = "";
        $f = @fopen("/tmp/openvpn.log", "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }
    } elseif (isset($_POST['log-openvpn']) || filesize('/tmp/openvpn.log') >0) {
        $logEnable = 1;
        exec("sudo /etc/raspap/openvpn/openvpnlog.sh", $logOutput);
        $logOutput = file_get_contents('/tmp/openvpn.log');
    }

    echo renderTemplate(
        "openvpn", compact(
            "status",
            "serviceStatus",
            "openvpnstatus",
            "logEnable",
            "logOutput",
            "public_ip",
            "authUser",
            "authPassword",
            "clients",
            "conf_default"
        )
    );
}

/**
 * Validates uploaded .ovpn file, adds auth-user-pass and
 * stores auth credentials in login.conf. Copies files from
 * tmp to OpenVPN
 *
 * @param  object $status
 * @param  object $file
 * @param  string $authUser
 * @param  string $authPassword
 * @return object $status
 */
function SaveOpenVPNConfig($status, $file, $authUser, $authPassword)
{
    define('KB', 1024);
    $tmp_destdir = '/tmp/';
    $auth_flag = 0;

    try {
        // If undefined or multiple files, treat as invalid
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters');
        }

        $upload = \RaspAP\Uploader\Upload::factory('ovpn',$tmp_destdir);
        $upload->set_max_file_size(64*KB);
        $upload->set_allowed_mime_types(array('ovpn' => 'text/plain'));
        $upload->file($file);

        $validation = new validation;
        $upload->callbacks($validation, array('check_name_length'));
        $results = $upload->upload();

        if (!empty($results['errors'])) {
            throw new RuntimeException($results['errors'][0]);
        }

        // Good file upload, update auth credentials if present
        if (!empty($authUser) && !empty($authPassword)) {
            $auth_flag = 1;
            $tmp_authdata = $tmp_destdir .'ovpn/authdata';
            $auth = $authUser .PHP_EOL . $authPassword .PHP_EOL;
            file_put_contents($tmp_authdata, $auth);
            chmod($tmp_authdata, 0644);
            $client_auth = RASPI_OPENVPN_CLIENT_PATH.pathinfo($file['name'], PATHINFO_FILENAME).'_login.conf';
            system("sudo mv $tmp_authdata $client_auth", $return);
            system("sudo rm ".RASPI_OPENVPN_CLIENT_LOGIN, $return);
            system("sudo ln -s $client_auth ".RASPI_OPENVPN_CLIENT_LOGIN, $return);
            if ($return !=0) {
                $status->addMessage('Unable to save client auth credentials', 'danger');
            }
        }

        // Set iptables rules and, optionally, auth-user-pass
        $tmp_ovpn = $results['full_path'];
        exec("sudo /etc/raspap/openvpn/configauth.sh $tmp_ovpn $auth_flag " .$_SESSION['ap_interface'], $return);
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }

        // Move uploaded ovpn config from /tmp and create symlink
        $client_ovpn = RASPI_OPENVPN_CLIENT_PATH.pathinfo($file['name'], PATHINFO_FILENAME).'_client.conf';
        chmod($tmp_ovpn, 0644);
        system("sudo mv $tmp_ovpn $client_ovpn", $return);
        system("sudo rm ".RASPI_OPENVPN_CLIENT_CONFIG, $return);
        system("sudo ln -s $client_ovpn ".RASPI_OPENVPN_CLIENT_CONFIG, $return);

        if ($return ==0) {
            $status->addMessage('OpenVPN client.conf uploaded successfully', 'info');
        } else {
            $status->addMessage('Unable to save OpenVPN client config', 'danger');
        }

        return $status;
    } catch (RuntimeException $e) {
        $status->addMessage($e->getMessage(), 'danger');
        return $status;
    }
}

