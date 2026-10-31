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
    $ovpncfg_keypass = RASPI_OPENVPN_CLIENT_PATH.$ovpncfg_id.'_keypass.conf';

    // the active configuration stays, client.conf links to it
    if (basename((string)@readlink(RASPI_OPENVPN_CLIENT_CONFIG)) === basename($ovpncfg_client)) {
        http_response_code(409);
        echo json_encode(['return' => 1]);
        exit;
    }

    // raspap.sudoers allows rm without options. The login and keypass files
    // are optional, so only the client file decides the result.
    exec("sudo rm ".escapeshellarg($ovpncfg_client)." 2>/dev/null", $output, $return);
    exec("sudo rm ".escapeshellarg($ovpncfg_login)." 2>/dev/null");
    exec("sudo rm ".escapeshellarg($ovpncfg_keypass)." 2>/dev/null");
    $jsonData = ['return'=>$return];
    echo json_encode($jsonData);
}
