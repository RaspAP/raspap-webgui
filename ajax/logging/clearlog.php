<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

// the logs that the web interface can clear
$logfiles = ['/tmp/hostapd.log', '/tmp/openvpn.log', '/var/log/dnsmasq.log'];

if (isset($_POST['logfile'])) {
    if (in_array($_POST['logfile'], $logfiles, true)) {
        // truncate requested log file
        exec('sudo truncate -s 0 '.escapeshellarg($_POST['logfile']), $return);
    } else {
        $return = 1;
    }
    echo json_encode($return);
}
