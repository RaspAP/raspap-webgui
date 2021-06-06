<?php

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (isset($_POST['cfg_id'])) {
    $ovpncfg_id = $_POST['cfg_id'];
    $ovpncfg_client = RASPI_OPENVPN_CLIENT_PATH.$ovpncfg_id.'_client.conf';
    $ovpncfg_login = RASPI_OPENVPN_CLIENT_PATH.$ovpncfg_id.'_login.conf';

    // remove existing client config +login and symbolically link the selected one
    system("sudo rm ".RASPI_OPENVPN_CLIENT_CONFIG, $return);
    system("sudo ln -s $ovpncfg_client ".RASPI_OPENVPN_CLIENT_CONFIG, $return);
    system("sudo rm ".RASPI_OPENVPN_CLIENT_LOGIN, $return);
    system("sudo ln -s $ovpncfg_login ".RASPI_OPENVPN_CLIENT_LOGIN, $return);

    // restart service
    exec("sudo /bin/systemctl stop openvpn-client@client", $return);
    sleep(1);
    exec("sudo /bin/systemctl enable openvpn-client@client", $return);
    sleep(1);
    exec("sudo /bin/systemctl start openvpn-client@client", $return);

    echo json_encode($return);
}

