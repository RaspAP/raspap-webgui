<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

$liveForm = new \RaspAP\UI\LiveForm();
$liveForm->initAjax();
$liveForm->sendStartMessage();

if (RASPI_MONITOR_ENABLED) {
    $liveForm->sendUpdateMessage('RaspAP Monitor Mode Enabled', 100);
    $liveForm->saveStatusMessage('RaspAP Monitor Mode Enabled', 'warning');
    $liveForm->sendCompleteMessage();
}

if (isset($_POST['saveadblocksettings'])) {
    if ($_POST['adblock-enable'] == "1") {
        $liveForm->sendUpdateMessage('Enabling Blocklists', 30);
        $config = 'conf-file=' .RASPI_ADBLOCK_LISTPATH .'domains.txt'.PHP_EOL;
        $config.= 'addn-hosts=' .RASPI_ADBLOCK_LISTPATH .'hostnames.txt'.PHP_EOL;
    } elseif ($_POST['adblock-enable'] == "0") {
        $liveForm->sendUpdateMessage('Disabling Blocklists', 30);
        $config = null;
    }

    if ($_POST['adblock-custom-enable'] == "1") {
        $liveForm->sendUpdateMessage('Enabling Custom Blocklists', 60);
        // validate custom hosts input
        $lines = preg_split('/\r\n|\n|\r/', trim($_POST['adblock-custom-hosts']));
        if (!in_array("", $lines, true)) {
            foreach ($lines as $line) {
                $ip_host = preg_split('/\s+/', $line);
                $index++;
                if (!filter_var($ip_host[0], FILTER_VALIDATE_IP)) {
                    $liveForm->sendUpdateMessage('Invalid custom IP address found on line '.$index);
                    $errors .= _('Invalid custom IP address found on line '.$index);
                    break;
                }
                if (!validate_host($ip_host[1])) {
                    $liveForm->sendUpdateMessage('Invalid custom host found on line '.$index);
                    $errors .= _('Invalid custom host found on line '.$index);
                    break;
                }
            }
        }
        file_put_contents("/tmp/dnsmasq_custom", $_POST['adblock-custom-hosts'].PHP_EOL);
        system("sudo cp /tmp/dnsmasq_custom " .RASPI_ADBLOCK_LISTPATH .'custom.txt', $return);
        $config.= 'addn-hosts=' .RASPI_ADBLOCK_LISTPATH .'custom.txt'.PHP_EOL;
    }

    if (empty($errors)) {
        file_put_contents("/tmp/dnsmasqdata", $config);
        system('sudo cp /tmp/dnsmasqdata '.RASPI_ADBLOCK_CONFIG, $return);
        if ($return == 0) {
            $liveForm->sendUpdateMessage('Successfully updated Adblock configuration', 90);
            $liveForm->saveStatusMessage('Successfully updated Adblock configuration', 'success', true);
            $liveForm->sendCompleteMessage();
        } else {
            $liveForm->sendUpdateMessage('Failed to update Adblock configuration', 90);
            $liveForm->saveStatusMessage('Failed to update Adblock configuration', 'danger', true);
            $liveForm->sendFailedMessage();
        }
    } else {
        $liveForm->saveStatusMessage('Adblock configuration failed to be updated. '.$errors, 'danger', true);
        $liveForm->sendFailedMessage();
    }
} elseif (isset($_POST['restartadblock']) || isset($_POST['startadblock'])) {
    if (isset($_POST['restartadblock'])) {
        $liveForm->sendUpdateMessage('Restarting Adblock', 50);
    } elseif (isset($_POST['startadblock'])) {
        $liveForm->sendUpdateMessage('Starting Adblock', 50);
    }

    exec('sudo /bin/systemctl restart dnsmasq.service', $dnsmasq, $return);
    if ($return == 0) {
        if (isset($_POST['restartadblock'])) {
            $liveForm->saveStatusMessage('Successfully Restarted Adblock', 'success');
            $liveForm->sendCompleteMessage();
        } elseif (isset($_POST['startadblock'])) {
            $liveForm->saveStatusMessage('Successfully Started Adblock', 'success');
            $liveForm->sendCompleteMessage();
        }
    } else {
        if (isset($_POST['restartadblock'])) {
            $liveForm->saveStatusMessage('Failed to Restart Adblock', 'danger');
            $liveForm->sendFailedMessage();
        } elseif (isset($_POST['startadblock'])) {
            $liveForm->saveStatusMessage('Failed to Start Adblock', 'danger');
            $liveForm->sendFailedMessage();
        }
    }
}

$liveForm->saveStatusMessage('No Instructions to Complete', 'warning');
$liveForm->sendCompleteMessage();