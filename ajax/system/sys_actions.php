<?php

require '../../includes/csrf.php';

$action = escapeshellcmd($_POST['a']);

if (isset($action)) {

    switch($action) {
    case "reboot":
        $response = shell_exec("sudo /sbin/reboot");
        break;
    case "shutdown":
        $response = shell_exec("sudo /sbin/shutdown -h now");
        break;
    default:
        $response = 'Unknown action: '.$action;
    }
    echo json_encode($response);
}

