<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

if (isset($_POST['logfile'])) {
    $logfile = escapeshellarg($_POST['logfile']);

    // truncate requested log file
    exec("sudo truncate -s 0 $logfile", $return);
    echo json_encode($return);
}
