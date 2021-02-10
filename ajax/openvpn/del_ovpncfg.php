<?php

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (isset($_POST['cfg_id'])) {
    $ovpncfg_id = $_POST['cfg_id'];
    $ovpncfg_files = pathinfo(RASPI_OPENVPN_CLIENT_LOGIN, PATHINFO_DIRNAME).'/'.$ovpncfg_id.'_*.conf';
    exec("sudo rm $ovpncfg_files", $return);
    $jsonData = ['return'=>$return];
    echo json_encode($jsonData);
}

