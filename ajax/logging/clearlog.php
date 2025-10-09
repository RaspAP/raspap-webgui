<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

if (isset($_POST['logfile'])) {
    $logfile = escapeshellarg($_POST['logfile']);
    $valid = '/(\/var\/log|\/tmp)/';

    if (preg_match($valid, $logfile)) {
        // truncate requested log file
        exec("sudo truncate -s 0 $logfile", $return);
    } else {
        $return = 1;
    }
    echo json_encode($return);
}
