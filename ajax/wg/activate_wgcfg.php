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
    if (empty($wgcfg_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid configuration id']);
        exit;
    }
    $wg_conf = RASPI_WIREGUARD_PATH.$wgcfg_id.'.conf';

    // remove existing wg config and symbolically link the selected one
    system("sudo rm ".RASPI_WIREGUARD_CONFIG, $return);
    system("sudo ln -s ".escapeshellarg($wg_conf)." ".RASPI_WIREGUARD_CONFIG, $return);
    
    // restart service
    exec('sudo /bin/systemctl stop wg-quick@wg0', $return);
    sleep(1);
    exec('sudo /bin/systemctl start wg-quick@wg0', $return);

    echo json_encode($return);
}

