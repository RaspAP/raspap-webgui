<?php

require_once '../../includes/status_messages.php';
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (isset($_POST['cfg_id'])) {
    $status = new StatusMessages();

    $ovpncfg_id = $_POST['cfg_id'];
    $ovpncfg_path = pathinfo(RASPI_OPENVPN_CLIENT_LOGIN, PATHINFO_DIRNAME).'/';
    $ovpncfg_files = $ovpncfg_path .$ovpncfg_id.'_*.conf';

    // move currently active profile
    $meta = file_get_meta(RASPI_OPENVPN_CLIENT_LOGIN,'#\sfilename\s(.*)');
    $ovpncfg_client = $ovpncfg_path .$meta.'_client.conf';
    $ovpncfg_login = $ovpncfg_path .$meta.'_login.conf';
    exec("sudo mv ".RASPI_OPENVPN_CLIENT_CONFIG." $ovpncfg_client", $return);
    exec("sudo mv ".RASPI_OPENVPN_CLIENT_LOGIN." $ovpncfg_login", $return);

    // replace with selected profile
    $ovpncfg_client = $ovpncfg_path .$ovpncfg_id.'_client.conf';
    $ovpncfg_login = $ovpncfg_path .$ovpncfg_id.'_login.conf';
    exec("sudo mv $ovpncfg_client ".RASPI_OPENVPN_CLIENT_CONFIG, $return);
    exec("sudo mv $ovpncfg_login ".RASPI_OPENVPN_CLIENT_LOGIN, $return);

    // restart service
    $status->addMessage('Attempting to restart OpenVPN', 'info');
    exec('sudo /bin/systemctl stop openvpn-client@client', $return);
    exec('sudo /bin/systemctl enable openvpn-client@client', $return);
    exec('sudo /bin/systemctl start openvpn-client@client', $return);

    foreach ($return as $line) {
        $status->addMessage($line, 'info');
    }
    $return = $status;
    $jsonData = ['return'=>$return];
    echo json_encode($jsonData);
}

