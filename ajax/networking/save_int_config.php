<?php

require '../../includes/csrf.php';

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (isset($_POST['interface'])) {
    $int = $_POST['interface'];
    $cfg = [];
    $file = $int.".ini";
    if ( $int !== "mobiledata") {
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
    } else {
      $cfg['pin'] = $_POST["pin-mobile"];
      $cfg['apn'] = $_POST["apn-mobile"];
      $cfg['apn_user'] = $_POST["apn-user-mobile"];
      $cfg['apn_pw'] = $_POST["apn-pw-mobile"];
	  if (file_exists("/etc/wvdial.conf")) {
        if($cfg["pin"] !== "") exec('sudo /bin/sed -i  "s/CPIN=\".*\"/CPIN=\"'.$cfg["pin"].'\"/gi" /etc/wvdial.conf');
        if($cfg["apn"] !== "") exec('sudo /bin/sed -i "s/\"IP\"\,\".*\"/\"IP\"\,\"'.$cfg["apn"].'\"/gi" /etc/wvdial.conf');
        if($cfg["apn_user"] !== "") exec('sudo /bin/sed -i "s/^username = .*$/Username = '.$cfg["apn_user"].'/gi" /etc/wvdial.conf');
        if($cfg["apn_pw"] !== "") exec('sudo /bin/sed -i "s/^password = .*$/Password = '.$cfg["apn_pw"].'/gi" /etc/wvdial.conf');
      }
    }  
    if (write_php_ini($cfg, RASPI_CONFIG_NETWORKING.'/'.$file)) {
        $jsonData = ['return'=>0,'output'=>['Successfully Updated Network Configuration']];
    } else {
        $jsonData = ['return'=>1,'output'=>['Error saving network configuration to file']];
    }
} else {
    $jsonData = ['return'=>2,'output'=>'Unable to detect interface'];
}

echo json_encode($jsonData);
