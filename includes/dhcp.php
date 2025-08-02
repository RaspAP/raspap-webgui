<?php

require_once 'config.php';

use RaspAP\Networking\Hotspot\DhcpcdManager;
use RaspAP\Networking\Hotspot\DnsmasqManager;
use RaspAP\Networking\Hotspot\WiFiManager;
use RaspAP\Messages\StatusMessage;

/**
 * Displays DHCP configuration
 */
function DisplayDHCPConfig()
{
    $status = new StatusMessage();
    $wifi = new WiFiManager();
    $wifi->getWifiInterface();

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['savedhcpdsettings'])) {
            saveDHCPConfig($status);
        }
    }
    exec('pidof dnsmasq | wc -l', $dnsmasq);
    $dnsmasq_state = ($dnsmasq[0] > 0);

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['startdhcpd'])) {
            if ($dnsmasq_state) {
                $status->addMessage('dnsmasq already running', 'info');
            } else {
                exec('sudo /bin/systemctl start dnsmasq.service', $dnsmasq, $return);
                if ($return == 0) {
                    $status->addMessage('Successfully started dnsmasq', 'success');
                    $dnsmasq_state = true;
                } else {
                    $status->addMessage('Failed to start dnsmasq', 'danger');
                }
            }
        } elseif (isset($_POST['restartdhcpd'])) {
            if ($dnsmasq_state) {
                exec('sudo /bin/systemctl restart dnsmasq.service', $dnsmasq, $return);
                if ($return == 0) {
                    $status->addMessage('Successfully restarted dnsmasq', 'success');
                    $dnsmasq_state = false;
                } else {
                    $status->addMessage('Failed to restart dnsmasq', 'danger');
                }
            } else {
                $status->addMessage('dnsmasq already stopped', 'info');
            }
        } elseif (isset($_POST['stopdhcpd'])) {
            if ($dnsmasq_state) {
                exec('sudo /bin/systemctl stop dnsmasq.service', $dnsmasq, $return);
                if ($return == 0) {
                    $status->addMessage('Successfully stopped dnsmasq', 'success');
                    $dnsmasq_state = false;
                } else {
                    $status->addMessage('Failed to stop dnsmasq', 'danger');
                }
            } else {
                $status->addMessage('dnsmasq already stopped', 'info');
            }
        }
    }
    $ap_iface = $_SESSION['ap_interface'];
    $serviceStatus = $dnsmasq_state ? "up" : "down";
    exec('cat '. RASPI_DNSMASQ_PREFIX.'raspap.conf', $return);
    $log_dhcp = (preg_grep('/log-dhcp/', $return));
    $log_queries = (preg_grep('/log-queries/', $return));
    $conf = ParseConfig($return);
    exec('cat '. RASPI_DNSMASQ_PREFIX.$ap_iface.'.conf', $return);
    $conf = array_merge(ParseConfig($return));
    $hosts = (array)($conf['dhcp-host'] ?? []);
    $upstreamServers = (array)($conf['server'] ?? []);
    exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);
    exec('cat ' . RASPI_DNSMASQ_LEASES, $leases);

    count($log_dhcp) > 0 ? $conf['log-dhcp'] = true : false ;
    count($log_queries) > 0 ? $conf['log-queries'] = true : false ;

    exec('sudo /bin/chmod o+r '.RASPI_DHCPCD_LOG);
    $logdata = getLogLimited(RASPI_DHCPCD_LOG);

    echo renderTemplate(
        "dhcp", compact(
            "status",
            "serviceStatus",
            "dnsmasq_state",
            "ap_iface",
            "conf",
            "hosts",
            "upstreamServers",
            "interfaces",
            "leases",
            "logdata"
        )
    );
}

/**
 * Saves a DHCP configuration
 *
 * @return object $status
 */
function saveDHCPConfig($status)
{
    $dhcpcd = new DhcpcdManager();
    $dnsmasq = new DnsmasqManager();
    $iface = $_POST['interface'];

    // dhcp
    if (!isset($_POST['dhcp-iface']) && file_exists(RASPI_DNSMASQ_PREFIX.$iface.'.conf')) {
        // remove dhcp + dnsmasq configs for selected interface
        $return = $dhcpcd->remove($iface, $status);
        $return = $dnsmasq->remove($iface, $status);
    } else {
        $errors = $dhcpcd->validate($_POST);
        if (empty($errors)) {
            $dhcp_cfg = $dhcpcd->buildConfigEx($iface, $_POST, $status);
            $dhcpcd->saveConfig($dhcp_cfg, $iface, $status);
        } else {
            foreach ($errors as $error) {
                $status->addMessage($error, 'danger');
            }
        }

        // dnsmasq
        if (($_POST['dhcp-iface'] == "1") || (isset($_POST['mac']))) {
            $errors = $dnsmasq->validate($_POST);
            if (empty($errors)) {
                $config = $dnsmasq->buildConfigEx($iface, $_POST);
                $return = $dnsmasq->saveConfig($config, $iface);
                $config = $dnsmasq->buildDefault($_POST);
                $return = $dnsmasq->saveConfigDefault($config);
            } else {
                foreach ($errors as $error) {
                    $status->addMessage($error, 'danger');
                }
            }
        }
        return true;
    }
}

