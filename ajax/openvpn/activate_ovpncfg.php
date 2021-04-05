<?php

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (isset($_POST['cfg_id'])) {
    $ovpncfg_id = $_POST['cfg_id'];
    $ovpncfg_path = pathinfo(RASPI_OPENVPN_CLIENT_CONFIG, PATHINFO_DIRNAME).'/';
    $ovpncfg_files = $ovpncfg_path .$ovpncfg_id.'_*.conf';

    // move currently active profile
    $meta = file_get_meta(RASPI_OPENVPN_CLIENT_CONFIG,'#\sfilename\s(.*)');
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
    exec("sudo /bin/systemctl stop openvpn-client@client", $return);
    sleep(1);
    exec("sudo /bin/systemctl enable openvpn-client@client", $return);
    sleep(1);
    exec("sudo /bin/systemctl start openvpn-client@client", $return);

    echo json_encode($return);
}

