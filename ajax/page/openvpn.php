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

if (isset($_POST['SaveOpenVPNSettings'])) {
    $liveForm->sendUpdateMessage(_('Saving OpenVPN configuration'), 10);

    if (isset($_POST['authUser'])) {
        $authUser = strip_tags(trim($_POST['authUser']));
    }
    if (isset($_POST['authPassword'])) {
        $authPassword = strip_tags(trim($_POST['authPassword']));
    }

    if (is_uploaded_file( $_FILES["customFile"]["tmp_name"])) {
        define('KB', 1024);
        $tmp_destdir = '/tmp/';
        $auth_flag = 0;
        $file = $_FILES['customFile'];

        // If undefined or multiple files, treat as invalid
        if (!isset($file['error']) || is_array($file['error'])) {
            $liveForm->sendUpdateMessage(_('Invalid file parameters'), 90);
            checkOpenVPNLog($liveForm);
            $liveForm->saveStatusMessage(_('Invalid file parameters'), 'danger', true);
            $liveForm->sendFailedMessage();
        }

        $liveForm->sendUpdateMessage(_('Validating uploaded file'), 30);

        $upload = \RaspAP\Uploader\FileUpload::factory('ovpn',$tmp_destdir);
        $upload->set_max_file_size(64*KB);
        $upload->set_allowed_mime_types(array('ovpn' => 'text/plain'));
        $upload->file($file);

        $validation = new validation;
        $upload->callbacks($validation, array('check_name_length'));
        $results = $upload->upload();

        if (!empty($results['errors'])) {
            $liveForm->sendUpdateMessage(_('Invalid file provided:'), 90);
            $liveForm->sendUpdateMessage($results['errors'][0]);
            checkOpenVPNLog($liveForm);
            $liveForm->saveStatusMessage(_('Invalid file provided'), 'danger', true);
            $liveForm->sendFailedMessage();
        }

        // Good file upload, update auth credentials if present
        if (!empty($authUser) && !empty($authPassword)) {
            $liveForm->sendUpdateMessage(_('Updating authentication credentials'), 50);

            $auth_flag = 1;
            $tmp_authdata = $tmp_destdir .'ovpn/authdata';
            $auth = $authUser . PHP_EOL . $authPassword . PHP_EOL;
            file_put_contents($tmp_authdata, $auth);
            chmod($tmp_authdata, 0644);
            $client_auth = escapeshellarg(RASPI_OPENVPN_CLIENT_PATH.pathinfo($file['name'], PATHINFO_FILENAME).'_login.conf');
            system("sudo mv $tmp_authdata $client_auth", $return);
            system("sudo rm ".RASPI_OPENVPN_CLIENT_LOGIN, $return);
            system("sudo ln -s $client_auth ".RASPI_OPENVPN_CLIENT_LOGIN, $return);
            if ($return !=0) {
                $liveForm->sendUpdateMessage(_('Unable to save client auth credentials'), 90);
                checkOpenVPNLog($liveForm);
                $liveForm->saveStatusMessage(_('Unable to save client auth credentials'), 'danger', true);
                $liveForm->sendFailedMessage();
            }
        }

        $liveForm->sendUpdateMessage(null, 60);
        // Set iptables rules and, optionally, auth-user-pass
        $tmp_ovpn = $results['full_path'];
        exec("sudo /etc/raspap/openvpn/configauth.sh $tmp_ovpn $auth_flag " . $_SESSION['ap_interface'], $return);
        foreach ($return as $line) {
            $liveForm->sendUpdateMessage($line);
        }

        $liveForm->sendUpdateMessage(_('Installing uploaded OpenVPN config'), 80);
        // Move uploaded ovpn config from /tmp and create symlink
        $client_ovpn = escapeshellarg(RASPI_OPENVPN_CLIENT_PATH.pathinfo($file['name'], PATHINFO_FILENAME).'_client.conf');
        chmod($tmp_ovpn, 0644);
        system("sudo mv $tmp_ovpn $client_ovpn", $return);
        system("sudo rm ".RASPI_OPENVPN_CLIENT_CONFIG, $return);
        system("sudo ln -s $client_ovpn ".RASPI_OPENVPN_CLIENT_CONFIG, $return);

        if ($return == 0) {
            $liveForm->sendUpdateMessage(_('OpenVPN client.conf uploaded successfully'), 90);
        } else {
            $liveForm->sendUpdateMessage(_('Unable to save OpenVPN client config'), 90);
            checkOpenVPNLog($liveForm);
            $liveForm->saveStatusMessage(_('Unable to save OpenVPN client config'), 'danger', true);
            $liveForm->sendFailedMessage();
        }
    }

    checkOpenVPNLog($liveForm);
    $liveForm->saveStatusMessage(_('Saved settings successfully'), 'success', true);
    $liveForm->sendCompleteMessage();
} elseif (isset($_POST['StartOpenVPN'])) {
    $liveForm->sendUpdateMessage(_('Attempting to start OpenVPN'), 30);

    exec('sudo /bin/systemctl start openvpn-client@client', $return);
    exec('sudo /bin/systemctl enable openvpn-client@client', $return);
    foreach ($return as $line) {
        $liveForm->sendUpdateMessage($line);
    }

    $liveForm->sendUpdateMessage(_('Started OpenVPN'), 90);
    $liveForm->saveStatusMessage(_('OpenVPN started successfully'), 'success', true);
    $liveForm->sendCompleteMessage();
} elseif (isset($_POST['StopOpenVPN'])) {
    $liveForm->sendUpdateMessage(_('Attempting to stop OpenVPN'), 30);

    exec('sudo /bin/systemctl stop openvpn-client@client', $return);
    exec('sudo /bin/systemctl disable openvpn-client@client', $return);
    foreach ($return as $line) {
        $liveForm->sendUpdateMessage($line);
    }

    $liveForm->sendUpdateMessage(_('Stopped OpenVPN'), 90);
    $liveForm->saveStatusMessage(_('OpenVPN stopped successfully'), 'success', true);
    $liveForm->sendCompleteMessage();
}

$liveForm->saveStatusMessage(_('No Instructions to Complete'), 'warning');
$liveForm->sendCompleteMessage();

} catch (\Throwable $e) {
    $liveForm->sendUpdateMessage(sprintf(_('An error occurred: %s'), $e->getMessage()), 100);
    $liveForm->saveStatusMessage(_('An error occurred'), 'danger', true);
    $liveForm->sendFailedMessage();
}

function checkOpenVPNLog($liveForm)
{
    if (!isset($_POST['log-openvpn'])) {
        $liveForm->sendUpdateMessage(_('Clearing OpenVPN log'), 20);
        $f = @fopen("/tmp/openvpn.log", "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }
    } elseif (isset($_POST['log-openvpn']) || (file_exists('/tmp/openvpn.log') && filesize('/tmp/openvpn.log') > 0)) {
        $liveForm->sendUpdateMessage(_('Retrieving OpenVPN log'), 20);
        exec("sudo /etc/raspap/openvpn/openvpnlog.sh", $return);
    }
}