<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

if (isset($_POST['cfg_id'])) {
    $ovpncfg_id = $_POST['cfg_id'];
    if (!preg_match('/^[A-Za-z0-9_()][A-Za-z0-9 ._()-]{0,99}$/D', $ovpncfg_id)) {
        http_response_code(400);
        echo json_encode(['return' => 1]);
        exit;
    }
    $ovpncfg_client = RASPI_OPENVPN_CLIENT_PATH.$ovpncfg_id.'_client.conf';

    // the active configuration stays, client.conf links to it
    if (basename((string)@readlink(RASPI_OPENVPN_CLIENT_CONFIG)) === basename($ovpncfg_client)) {
        http_response_code(409);
        echo json_encode(['return' => 1]);
        exit;
    }

    // removes the client, login and keypass files. The helper also refuses
    // to remove the active configuration.
    $return = \RaspAP\Networking\VpnFile::run(['openvpn', 'remove', $ovpncfg_id]) === null ? 1 : 0;
    $jsonData = ['return'=>$return];
    echo json_encode($jsonData);
}
