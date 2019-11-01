<?php

include_once('includes/status_messages.php');

/**
*
* Manage DHCP configuration
*
*/
function DisplayDHCPConfig()
{

    $status = new StatusMessages();
    if (isset($_POST['savedhcpdsettings'])) {
        $errors = '';
        define('IFNAMSIZ', 16);
        if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST['interface']) ||
        strlen($_POST['interface']) >= IFNAMSIZ) {
              $errors .= _('Invalid interface name.').'<br />'.PHP_EOL;
        }

        if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $_POST['RangeStart']) &&
        !empty($_POST['RangeStart'])) {  // allow ''/null ?
              $errors .= _('Invalid DHCP range start.').'<br />'.PHP_EOL;
        }

        if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $_POST['RangeEnd']) &&
        !empty($_POST['RangeEnd'])) {  // allow ''/null ?
              $errors .= _('Invalid DHCP range end.').'<br />'.PHP_EOL;
        }

        if (!ctype_digit($_POST['RangeLeaseTime']) && $_POST['RangeLeaseTimeUnits'] !== 'infinite') {
            $errors .= _('Invalid DHCP lease time, not a number.').'<br />'.PHP_EOL;
        }

        if (!in_array($_POST['RangeLeaseTimeUnits'], array('m', 'h', 'd', 'infinite'))) {
            $errors .= _('Unknown DHCP lease time unit.').'<br />'.PHP_EOL;
        }

        $return = 1;
        if (empty($errors)) {
            $config = 'interface='.$_POST['interface'].PHP_EOL.
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

            if ($_POST['DNS1']){
                $config .= "dhcp-option=6," . $_POST['DNS1'];
                if ($_POST['DNS2']){
                    $config .= ','.$_POST['DNS2'];
                }
                $config .= PHP_EOL;
            }

            file_put_contents("/tmp/dnsmasqdata", $config);
            system('sudo cp /tmp/dnsmasqdata '.RASPI_DNSMASQ_CONFIG, $return);
        } else {
            $status->addMessage($errors, 'danger');
        }

        if ($return == 0) {
            $status->addMessage('Dnsmasq configuration updated successfully', 'success');
        } else {
            $status->addMessage('Dnsmasq configuration failed to be updated.', 'danger');
        }
    }

    exec('pidof dnsmasq | wc -l', $dnsmasq);
    $dnsmasq_state = ($dnsmasq[0] > 0);

    if (isset($_POST['startdhcpd'])) {
        if ($dnsmasq_state) {
            $status->addMessage('dnsmasq already running', 'info');
        } else {
            exec('sudo /etc/init.d/dnsmasq start', $dnsmasq, $return);
            if ($return == 0) {
                $status->addMessage('Successfully started dnsmasq', 'success');
                $dnsmasq_state = true;
            } else {
                $status->addMessage('Failed to start dnsmasq', 'danger');
            }
        }
    } elseif (isset($_POST['stopdhcpd'])) {
        if ($dnsmasq_state) {
            exec('sudo /etc/init.d/dnsmasq stop', $dnsmasq, $return);
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

    $serviceStatus = $dnsmasq_state ? "up" : "down";

    exec('cat '. RASPI_DNSMASQ_CONFIG, $return);
    $conf = ParseConfig($return);
    $arrRange = explode(",", $conf['dhcp-range']);
    $RangeStart = $arrRange[0];
    $RangeEnd = $arrRange[1];
    $RangeMask = $arrRange[2];
    $leaseTime = $arrRange[3];
    $dhcpHost = $conf["dhcp-host"];
    $dhcpHost = empty($dhcpHost) ? [] : $dhcpHost;
    $dhcpHost = is_array($dhcpHost) ? $dhcpHost : [ $dhcpHost ];

    $DNS1 = '';
    $DNS2 = '';
    if (isset($conf['dhcp-option'])){
        $arrDns = explode(",", $conf['dhcp-option']);
        if ($arrDns[0] == '6'){
            if (count($arrDns) > 1){
                $DNS1 = $arrDns[1];
            }
            if (count($arrDns) > 2){
                $DNS2 = $arrDns[2];
            }
        }
    }
  
    $hselected = '';
    $mselected = '';
    $dselected = '';
    $infiniteselected = '';
    preg_match('/([0-9]*)([a-z])/i', $leaseTime, $arrRangeLeaseTime);
    if ($leaseTime === 'infinite') {
        $infiniteselected = ' selected="selected"';
    } else {
        switch ($arrRangeLeaseTime[2]) {
            case 'h':
                $hselected = ' selected="selected"';
                break;
            case 'm':
                $mselected = ' selected="selected"';
                break;
            case 'd':
                $dselected = ' selected="selected"';
                break;
        }
    }

    exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);
    exec('cat ' . RASPI_DNSMASQ_LEASES, $leases);

    echo renderTemplate("dhcp", compact(
        "status",
        "serviceStatus",
        "RangeStart",
        "RangeEnd",
        "DNS1",
        "DNS2",
        "arrRangeLeaseTime",
        "mselected",
        "hselected",
        "dselected",
        "infiniteselected",
        "dnsmasq_state",
        "conf",
        "dhcpHost",
        "interfaces",
        "leases"
    ));
}
