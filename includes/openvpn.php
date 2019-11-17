<?php

include_once('includes/status_messages.php');

/**
 *
 * Manage OpenVPN configuration
 *
 */
function DisplayOpenVPNConfig()
{
    $status = new StatusMessages();
    $errors = false;
    if (isset($_POST['SaveOpenVPNSettings'])) {
        // TODO: validate $authUser, $authPassword
        // Validate input
        if (empty($_POST['authUser'])) {
            $status->addMessage('Username cannot be empty', 'danger');
            $errors = true;
        } else {
            $authUser = $_POST['authUser'];
        }
        if (empty($_POST['authPassword'])) {
            $status->addMessage('Password cannot be empty', 'danger');
            $errors = true;
        } else {
            $authPassword = $_POST['authPassword'];
        }
        if (!$errors) {
            $return = SaveOpenVPNConfig($status, $_FILES['customFile'], $authUser, $authPassword);
        }
    } elseif (isset($_POST['StartOpenVPN'])) {
        $status->addMessage('Attempting to start OpenVPN', 'info');
        exec('sudo /bin/systemctl start openvpn-client@client', $return);
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }
    } elseif (isset($_POST['StopOpenVPN'])) {
        $status->addMessage('Attempting to stop OpenVPN', 'info');
        exec('sudo /bin/systemctl stop openvpn-client@client', $return);
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }
    }

    $auth = file(RASPI_OPENVPN_CLIENT_LOGIN, FILE_IGNORE_NEW_LINES);
    exec('pidof openvpn | wc -l', $openvpnstatus);

    $serviceStatus = $openvpnstatus[0] == 0 ? "down" : "up";

    // parse client auth credentials
    if (!empty($auth)) {
        $authUser = $auth[0];
        $authPassword = $auth[1];
    }

    echo renderTemplate("openvpn", compact(
        "status",
        "serviceStatus",
        "openvpnstatus",
        "authUser",
        "authPassword"
    ));
}

/**
 *
 * Validates uploaded .ovpn file, adds auth-user-pass and
 * stores auth credentials in login.conf. Copies files from
 * tmp to OpenVPN
 *
 * @param object $status
 * @param object $file
 * @param string $authUser
 * @param string $authPassword
 * @return object $status
 */
function SaveOpenVPNConfig($status, $file, $authUser, $authPassword)
{
    $tmp_ovpnclient = '/tmp/ovpnclient.ovpn';
    $tmp_authdata = '/tmp/authdata';

    try {
        // If undefined or multiple files, treat as invalid
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters');
        }

        // Parse returned errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('OpenVPN configuration file not sent');
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded filesize limit');
            default:
                throw new RuntimeException('Unknown errors');
        }

        // Validate extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if ($ext != 'ovpn') {
            throw new RuntimeException('Invalid file extension');
        }

        // Validate MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if (false === $ext = array_search(
            $finfo->file($file['tmp_name']),
            array(
                'ovpn' => 'text/plain'
            ),
            true
        )) {
            throw new RuntimeException('Invalid file format');
        }

        // Validate filesize
        define('KB', 1024);
        if ($file['size'] > 128*KB) {
            throw new RuntimeException('File size limit exceeded');
        }

        // Use safe filename, save to /tmp
        if (!move_uploaded_file(
            $file['tmp_name'],
            sprintf(
                '/tmp/%s.%s',
                'ovpnclient',
                $ext
            )
        )) {
            throw new RuntimeException('Unable to move uploaded file');
        }
        // Good upload, update /tmp client conf with auth-user-pass
        exec("sudo /etc/raspap/openvpn/configauth.sh $tmp_ovpnclient " .RASPI_WIFI_CLIENT_INTERFACE, $return);
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }
        // Copy tmp client config to /etc/openvpn
        system("sudo cp $tmp_ovpnclient " . RASPI_OPENVPN_CLIENT_CONFIG, $return);

        // Copy tmp authdata to /etc/openvpn/login.conf
        $auth = $authUser .PHP_EOL . $authPassword .PHP_EOL;
        file_put_contents($tmp_authdata, $auth);
        system("sudo cp $tmp_authdata " . RASPI_OPENVPN_CLIENT_LOGIN, $return);

        if ($return ==0) {
            $status->addMessage('OpenVPN .conf file uploaded successfully', 'info');
        } else {
            $status->addMessage('Unable to save OpenVPN settings', 'danger');
        }
        return $status;
    } catch (RuntimeException $e) {
        $status->addMessage($e->getMessage(), 'danger');
        return $status;
    }
}
