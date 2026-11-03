<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

if (isset($_POST['logfile'])) {
    // the logs that the web interface can clear
    $logs = [
        '/var/log/dnsmasq.log' => 'dnsmasq',
        '/tmp/hostapd.log' => 'hostapd',
        '/tmp/openvpn.log' => 'openvpn'
    ];
    $name = is_string($_POST['logfile']) ? ($logs[$_POST['logfile']] ?? null) : null;

    if ($name !== null) {
        // truncate requested log file
        exec("sudo /usr/local/sbin/raspap-logs.sh clear $name", $return, $status);
        if ($status !== 0) {
            $return = 1;
        }
    } else {
        $return = 1;
    }
    echo json_encode($return);
}
