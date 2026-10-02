<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

if (isset($_POST['cfg_id'])) {
    $ovpncfg_id = $_POST['cfg_id'];
    if (!preg_match('/^[A-Za-z0-9_\-][A-Za-z0-9._\-]*$/D', $ovpncfg_id)) {
        http_response_code(400);
        echo json_encode(['return' => 1]);
        exit;
    }
    $ovpncfg_client = RASPI_OPENVPN_CLIENT_PATH.$ovpncfg_id.'_client.conf';
    $ovpncfg_login = RASPI_OPENVPN_CLIENT_PATH.$ovpncfg_id.'_login.conf';
    exec("sudo rm -f ".escapeshellarg($ovpncfg_client)." ".escapeshellarg($ovpncfg_login), $return);
    $jsonData = ['return'=>$return];
    echo json_encode($jsonData);
}
