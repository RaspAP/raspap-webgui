<?php

require('../../includes/csrf.php');

include_once('../../includes/config.php');
include_once('../../includes/functions.php');

if (isset($_POST['interface'])) {
    $int = $_POST['interface'];
    $cfg = [];
    $file = $int.".ini";
    $ip = $_POST[$int.'-ipaddress'];
    $netmask = mask2cidr($_POST[$int.'-netmask']);
    $dns1 = $_POST[$int.'-dnssvr'];
    $dns2 = $_POST[$int.'-dnssvralt'];


    $cfg['interface'] = $int;
    $cfg['routers'] = $_POST[$int.'-gateway'];
    $cfg['ip_address'] = $ip."/".$netmask;
    $cfg['domain_name_server'] = $dns1." ".$dns2;
    $cfg['static'] = $_POST[$int.'-static'];
    $cfg['failover'] = $_POST[$int.'-failover'];

    if (write_php_ini($cfg, RASPI_CONFIG_NETWORKING.'/'.$file)) {
        $jsonData = ['return'=>0,'output'=>['Successfully Updated Network Configuration']];
    } else {
        $jsonData = ['return'=>1,'output'=>['Error saving network configuration to file']];
    }
} else {
    $jsonData = ['return'=>2,'output'=>'Unable to detect interface'];
}

echo json_encode($jsonData);
