<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

if (isset($_POST['cfg_id'])) {
    $ovpncfg_id = $_POST['cfg_id'];
    // Validate cfg_id: only allow alphanumeric, hyphens, underscores (prevent path traversal and argument injection)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $ovpncfg_id)) {
        echo json_encode(['return' => 'Invalid configuration ID']);
        exit;
    }
    $ovpncfg_dir = escapeshellarg(pathinfo(RASPI_OPENVPN_CLIENT_LOGIN, PATHINFO_DIRNAME));
    exec("sudo rm " . $ovpncfg_dir . "/" . escapeshellarg($ovpncfg_id . "_*.conf"), $return);
    $jsonData = ['return'=>$return];
    echo json_encode($jsonData);
}
