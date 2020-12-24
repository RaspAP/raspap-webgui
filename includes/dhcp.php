<?php

require_once 'includes/status_messages.php';
require_once 'config.php';

/**
 * Manage DHCP configuration
 */
function DisplayDHCPConfig()
{
    $status = new StatusMessages();
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
    getWifiInterface();
    $serviceStatus = $dnsmasq_state ? "up" : "down";
    exec('cat '. RASPI_DNSMASQ_PREFIX.'raspap.conf', $return);
    $conf = ParseConfig($return);
    exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);
    exec('cat ' . RASPI_DNSMASQ_LEASES, $leases);
    $ap_iface = $_SESSION['ap_interface'];

    echo renderTemplate(
        "dhcp", compact(
            "status",
            "serviceStatus",
            "dnsmasq_state",
            "ap_iface",
            "conf",
            "dhcpHost",
            "interfaces",
            "leases"
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
    $iface = $_POST['interface'];
    $return = 1;

    // handle disable dhcp option
    if (!isset($_POST['dhcp-iface']) && file_exists(RASPI_DNSMASQ_PREFIX.$iface.'.conf')) {
        // remove dhcp + dnsmasq configs for selected interface
        $return = removeDHCPConfig($iface,$status);
        $return = removeDnsmasqConfig($iface,$status);
    } else {
        $errors = validateDHCPInput();
        if (empty($errors)) {
            $return = updateDHCPConfig($iface,$status);
        } else {
            $status->addMessage($errors, 'danger');
        }
        if ($return == 1) {
            $status->addMessage('Dnsmasq configuration failed to be updated.', 'danger');
            return false;
        }

        if (($_POST['dhcp-iface'] == "1")) {
            $return = updateDnsmasqConfig($iface,$status);
        }
        if ($return == 0) {
            $status->addMessage('Dnsmasq configuration updated successfully.', 'success');
        } else {
            $status->addMessage('Dnsmasq configuration failed to be updated.', 'danger');
            return false;
        }
        return true;
    }
}

/**
 * Validates DHCP user input from the $_POST object
 *
 * @return string $errors
 */
function validateDHCPInput()
{
    define('IFNAMSIZ', 16);
    $iface = $_POST['interface'];
    if (!preg_match('/^[a-zA-Z0-9]+$/', $iface)
        || strlen($iface) >= IFNAMSIZ
    ) {
        $errors .= _('Invalid interface name.').'<br />'.PHP_EOL;
    }
    if (!filter_var($_POST['StaticIP'], FILTER_VALIDATE_IP) && !empty($_POST['StaticIP'])) {
        $errors .= _('Invalid static IP address.').'<br />'.PHP_EOL;
    }
    if (!filter_var($_POST['SubnetMask'], FILTER_VALIDATE_IP) && !empty($_POST['SubnetMask'])) {
        $errors .= _('Invalid subnet mask.').'<br />'.PHP_EOL;
    }
    if (!filter_var($_POST['DefaultGateway'], FILTER_VALIDATE_IP) && !empty($_POST['DefaultGateway'])) {
        $errors .= _('Invalid default gateway.').'<br />'.PHP_EOL;
        var_dump($_POST['DefaultGateway']);
        die();
    }
    if (($_POST['dhcp-iface'] == "1")) {
        if (!filter_var($_POST['RangeStart'], FILTER_VALIDATE_IP) && !empty($_POST['RangeStart'])) {
            $errors .= _('Invalid DHCP range start.').'<br />'.PHP_EOL;
        }
        if (!filter_var($_POST['RangeEnd'], FILTER_VALIDATE_IP) && !empty($_POST['RangeEnd'])) {
            $errors .= _('Invalid DHCP range end.').'<br />'.PHP_EOL;
        }
        if (!ctype_digit($_POST['RangeLeaseTime']) && $_POST['RangeLeaseTimeUnits'] !== 'infinite') {
            $errors .= _('Invalid DHCP lease time, not a number.').'<br />'.PHP_EOL;
        }
        if (!in_array($_POST['RangeLeaseTimeUnits'], array('m', 'h', 'd', 'infinite'))) {
            $errors .= _('Unknown DHCP lease time unit.').'<br />'.PHP_EOL;
        }
        if ($_POST['Metric'] !== '' && !ctype_digit($_POST['Metric'])) {
            $errors .= _('Invalid metric value, not a number.').'<br />'.PHP_EOL;
        }
    }
    return $errors;
}

/**
 * Updates a dnsmasq configuration
 *
 * @param string $iface
 * @param object $status
 * @return boolean $result
 */
function updateDnsmasqConfig($iface,$status)
{
    $config = '# RaspAP '.$iface.' configuration'.PHP_EOL;
    $config .= 'interface='.$iface.PHP_EOL.
        'dhcp-range='.$_POST['RangeStart'].','.$_POST['RangeEnd'].
        ',255.255.255.0,';
    if ($_POST['RangeLeaseTimeUnits'] !== 'infinite') {
        $config .= $_POST['RangeLeaseTime'];
    }
    $config .= $_POST['RangeLeaseTimeUnits'].PHP_EOL;
    for ($i=0; $i < count($_POST["static_leases"]["mac"]); $i++) {
        $mac = trim($_POST["static_leases"]["mac"][$i]);
        $ip  = trim($_POST["static_leases"]["ip"][$i]);
        if ($mac != "" && $ip != "") {
            $config .= "dhcp-host=$mac,$ip".PHP_EOL;
        }
    }
    if ($_POST['no-resolv'] == "1") {
        $config .= "no-resolv".PHP_EOL;
    }
    foreach ($_POST['server'] as $server) {
        $config .= "server=$server".PHP_EOL;
    }
    if ($_POST['DNS1']) {
        $config .= "dhcp-option=6," . $_POST['DNS1'];
        if ($_POST['DNS2']) {
            $config .= ','.$_POST['DNS2'];
        }
        $config .= PHP_EOL;
    }
    file_put_contents("/tmp/dnsmasqdata", $config);
    $msg = file_exists(RASPI_DNSMASQ_PREFIX.$iface.'.conf') ? 'updated' : 'added';
    system('sudo cp /tmp/dnsmasqdata '.RASPI_DNSMASQ_PREFIX.$iface.'.conf', $result);
    if ($result == 0) {
        $status->addMessage('Dnsmasq configuration for '.$iface.' '.$msg.'.', 'success');
    }

    // write default 090_raspap.conf
    $config = '# RaspAP default config'.PHP_EOL;
    $config .='log-facility=/tmp/dnsmasq.log'.PHP_EOL;
    $config .='conf-dir=/etc/dnsmasq.d'.PHP_EOL;
    // handle log option
    if ($_POST['log-dhcp'] == "1") {
        $config .= "log-dhcp".PHP_EOL;
    }
    if ($_POST['log-queries'] == "1") {
      $config .= "log-queries".PHP_EOL;
    }
    $config .= PHP_EOL;
    file_put_contents("/tmp/dnsmasqdata", $config);
    system('sudo cp /tmp/dnsmasqdata '.RASPI_DNSMASQ_PREFIX.'raspap.conf', $result);

    return $result;
}

/**
 * Updates a dhcp configuration
 *
 * @param string $iface
 * @param object $status
 * @return boolean $result
 */
function updateDHCPConfig($iface,$status)
{
    $cfg[] = '# RaspAP '.$iface.' configuration';
    $cfg[] = 'interface '.$iface;
    if (isset($_POST['StaticIP'])) {
        $mask = ($_POST['SubnetMask'] !== '' && $_POST['SubnetMask'] !== '0.0.0.0') ? '/'.mask2cidr($_POST['SubnetMask']) : null;
        $cfg[] = 'static ip_address='.$_POST['StaticIP'].$mask;
    }
    if (isset($_POST['DefaultGateway'])) {
      $cfg[] = 'static routers='.$_POST['DefaultGateway'];
    }
    if ($_POST['DNS1'] !== '' || $_POST['DNS2'] !== '') {
        $cfg[] = 'static domain_name_server='.$_POST['DNS1'].' '.$_POST['DNS2'];
    }
    if ($_POST['Metric'] !== '') {
      $cfg[] = 'metric '.$_POST['Metric'];
    }
    if ($_POST['Fallback'] == 1) {
        $cfg[] = 'profile static_'.$iface;
        $cfg[] = 'fallback static_'.$iface;
    }
    $dhcp_cfg = file_get_contents(RASPI_DHCPCD_CONFIG);
    if (!preg_match('/^interface\s'.$iface.'$/m', $dhcp_cfg)) {
        $cfg[] = PHP_EOL;
        $cfg = join(PHP_EOL, $cfg);
        $dhcp_cfg .= $cfg;
        $status->addMessage('DHCP configuration for '.$iface.' added.', 'success');
    } else {
        $cfg = join(PHP_EOL, $cfg);
        $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$iface.'\s.*?(?=\s*^\s*$)/ms', $cfg, $dhcp_cfg, 1);
        $status->addMessage('DHCP configuration for '.$iface.' updated.', 'success');
    }
    file_put_contents("/tmp/dhcpddata", $dhcp_cfg);
    system('sudo cp /tmp/dhcpddata '.RASPI_DHCPCD_CONFIG, $result);

    return $result;
}

