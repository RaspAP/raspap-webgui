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

if (isset($_POST['savedhcpdsettings'])) {
    $status = new \RaspAP\UI\LiveFormStatusMessage($liveForm);
    $dhcpcd = new \RaspAP\Networking\Hotspot\DhcpcdManager();
    $dnsmasq = new \RaspAP\Networking\Hotspot\DnsmasqManager();
    $iface = $_POST['interface'];

    if (!isset($_POST['dhcp-iface']) && file_exists(RASPI_DNSMASQ_PREFIX.$iface.'.conf')) {
        $liveForm->sendUpdateMessage(sprintf(_('Removing DHCP Configuration for %s'), $iface), 50);

        // remove dhcp + dnsmasq configs for selected interface
        $return = $dhcpcd->remove($iface, $status);
        $return = $dnsmasq->remove($iface, $status);
    } else {
        $liveForm->sendUpdateMessage(_('Validating DHCP Configuration'), 10);
        $errors = $dhcpcd->validate($_POST);
        if (empty($errors)) {
            $liveForm->sendUpdateMessage(sprintf(_('Saving DHCP Configuration for %s'), $iface), 40);
            $dhcp_cfg = $dhcpcd->buildConfigEx($iface, $_POST, $status);
            $dhcpcd->saveConfig($dhcp_cfg, $iface, $status);
            $liveForm->sendUpdateMessage(_('DHCP Configuration Saved'), 50);
        } else {
            $liveForm->sendUpdateMessage(_('DHCP Configuration Invalid with the following errors:'), 70);
            foreach ($errors as $error) {
                $liveForm->sendUpdateMessage($error);
            }
            $liveForm->saveStatusMessage(_('DHCP Configuration Invalid'), 'danger', true);
            $liveForm->sendFailedMessage();
        }

        // dnsmasq
        if (($_POST['dhcp-iface'] == "1") || (isset($_POST['mac']))) {
            $liveForm->sendUpdateMessage(_('Validating dnsmasq Configuration'), 60);
            $errors = $dnsmasq->validate($_POST);
            if (empty($errors)) {
                $liveForm->sendUpdateMessage(sprintf(_('Building dnsmasq Configuration for %s'), $iface), 70);
                $config = $dnsmasq->buildConfigEx($iface, $_POST);
                $liveForm->sendUpdateMessage(sprintf(_('Saving dnsmasq Configuration for %s'), $iface));
                $return = $dnsmasq->saveConfig($config, $iface);

                $liveForm->sendUpdateMessage(_('Building default dnsmasq Configuration'), 80);
                $config = $dnsmasq->buildDefault($_POST);
                $liveForm->sendUpdateMessage(_('Saving default dnsmasq Configuration'));
                $return = $dnsmasq->saveConfigDefault($config);
            } else {
                $liveForm->sendUpdateMessage(_('DNSMASQ Configuration Invalid with the following errors:'), 70);
                foreach ($errors as $error) {
                    $liveForm->sendUpdateMessage($error);
                }
                $liveForm->saveStatusMessage(_('DNSMASQ Configuration Invalid'), 'danger', true);
                $liveForm->sendFailedMessage();
            }
        }
    }

    $liveForm->sendUpdateMessage(_('DHCP Configuration Saved'), 90);
    $liveForm->saveStatusMessage(_('DHCP Configuration Saved'), 'success', true);
    $liveForm->sendCompleteMessage();
} elseif (isset($_POST['startdhcpd']) || isset($_POST['stopdhcpd']) || isset($_POST['restartdhcpd'])) {
    $liveForm->sendUpdateMessage(_('Checking current status'), 30);
    exec('pidof dnsmasq | wc -l', $dnsmasq);
    $dnsmasq_state = ($dnsmasq[0] > 0);

    if (isset($_POST['startdhcpd'])) {
        $liveForm->sendUpdateMessage(_('Starting DHCP Service'), 50);
        if ($dnsmasq_state) {
            $liveForm->sendUpdateMessage(_('DNSMASQ is already running'), 90);
            $liveForm->saveStatusMessage(_('DNSMASQ is already running'), 'info', true);
            $liveForm->sendCompleteMessage();
        } else {
            exec('sudo /bin/systemctl start dnsmasq.service', $dnsmasq, $return);
            if ($return == 0) {
                $liveForm->sendUpdateMessage(_('Successfully started dnsmasq'), 90);
                $liveForm->saveStatusMessage(_('Successfully started dnsmasq'), 'success', true);
                $liveForm->sendCompleteMessage();
            } else {
                $liveForm->sendUpdateMessage(_('Failed to start dnsmasq'), 90);
                $liveForm->saveStatusMessage(_('Failed to start dnsmasq'), 'danger', true);
                $liveForm->sendFailedMessage();
            }
        }
    } elseif (isset($_POST['stopdhcpd'])) {
        $liveForm->sendUpdateMessage(_('Stopping DHCP Service'), 50);
        if ($dnsmasq_state) {
            exec('sudo /bin/systemctl stop dnsmasq.service', $dnsmasq, $return);
            if ($return == 0) {
                $liveForm->sendUpdateMessage(_('Successfully stopped dnsmasq'), 90);
                $liveForm->saveStatusMessage(_('Successfully stopped dnsmasq'), 'success', true);
                $liveForm->sendCompleteMessage();
            } else {
                $liveForm->sendUpdateMessage(_('Failed to stop dnsmasq'), 90);
                $liveForm->saveStatusMessage(_('Failed to stop dnsmasq'), 'danger', true);
                $liveForm->sendFailedMessage();
            }
        } else {
            $liveForm->sendUpdateMessage(_('DNSMASQ is already stopped'), 90);
            $liveForm->saveStatusMessage(_('DNSMASQ is already stopped'), 'info', true);
            $liveForm->sendCompleteMessage();
        }
    } elseif (isset($_POST['restartdhcpd'])) {
        $liveForm->sendUpdateMessage(_('Restarting DHCP Service'), 50);
        if ($dnsmasq_state) {
            exec('sudo /bin/systemctl restart dnsmasq.service', $dnsmasq, $return);
            if ($return == 0) {
                $liveForm->sendUpdateMessage(_('Successfully restarted dnsmasq'), 90);
                $liveForm->saveStatusMessage(_('Successfully restarted dnsmasq'), 'success', true);
                $liveForm->sendCompleteMessage();
            } else {
                $liveForm->sendUpdateMessage(_('Failed to restart dnsmasq'), 90);
                $liveForm->saveStatusMessage(_('Failed to restart dnsmasq'), 'danger', true);
                $liveForm->sendFailedMessage();
            }
        } else {
            $liveForm->sendUpdateMessage(_('DNSMASQ is already stopped'), 90);
            $liveForm->saveStatusMessage(_('DNSMASQ is already stopped'), 'info', true);
            $liveForm->sendCompleteMessage();
        }
    }
}

$liveForm->saveStatusMessage(_('No Instructions to Complete'), 'warning');
$liveForm->sendCompleteMessage();

} catch (\Throwable $e) {
    $liveForm->sendUpdateMessage(sprintf(_('An error occurred: %s'), $e->getMessage()), 100);
    $liveForm->saveStatusMessage(_('An error occurred'), 'danger', true);
    $liveForm->sendFailedMessage();
}
