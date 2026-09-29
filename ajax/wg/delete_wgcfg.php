<?php

require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

if (isset($_POST['cfg_id'])) {
    $wgcfg_id = preg_replace('/[^A-Za-z0-9\-_]/', '', $_POST['cfg_id']);
    if (empty($wgcfg_id) || $wgcfg_id === 'wg0') {
        http_response_code(400);
        echo json_encode(['return' => 1]);
        exit;
    }
    $wgcfg_file = RASPI_WIREGUARD_PATH .$wgcfg_id. '.conf';
    exec("sudo rm ".escapeshellarg($wgcfg_file), $return);
    $jsonData = ['return'=>$return];
    echo json_encode($jsonData);
}

