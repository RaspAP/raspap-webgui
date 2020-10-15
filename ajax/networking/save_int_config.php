<?php

require '../../includes/csrf.php';

require_once '../../includes/config.php';
require_once '../../includes/functions.php';

if (isset($_POST['interface'])) {
    $int = $_POST['interface'];
    $cfg = [];
    $file = $int.".ini";
    $cfgfile="/etc/wvdial.conf";
    if ( $int == "mobiledata") {
      $cfg['pin'] = $_POST["pin-mobile"];
      $cfg['apn'] = $_POST["apn-mobile"];
      $cfg['apn_user'] = $_POST["apn-user-mobile"];
      $cfg['apn_pw'] = $_POST["apn-pw-mobile"];
      if (file_exists($cfgfile)) {
        if($cfg["pin"] !== "") exec('sudo /bin/sed -i  "s/CPIN=\".*\"/CPIN=\"'.$cfg["pin"].'\"/gi" '.$cfgfile);
        if($cfg["apn"] !== "") exec('sudo /bin/sed -i "s/\"IP\"\,\".*\"/\"IP\"\,\"'.$cfg["apn"].'\"/gi" '.$cfgfile);
        if($cfg["apn_user"] !== "") exec('sudo /bin/sed -i "s/^username = .*$/Username = '.$cfg["apn_user"].'/gi" '.$cfgfile);
        if($cfg["apn_pw"] !== "") exec('sudo /bin/sed -i "s/^password = .*$/Password = '.$cfg["apn_pw"].'/gi" '.$cfgfile);
      }
    } else if ( preg_match("/netdevices/",$int)) {
        if(!isset($_POST['opts']) ) {
          $jsonData = ['return'=>0,'output'=>['No valid data to add/delete udev rule ']];
          echo json_encode($jsonData);
          return;
        } else {
          $opts=explode(" ",$_POST['opts'] );
          $dev=$opts[0];
          $mode=$opts[1];
          $vid=$_POST["int-vid-".$dev];
          $pid=$_POST["int-pid-".$dev];
          $mac=$_POST["int-mac-".$dev];
          $name=$_POST["int-name-".$dev];
          $udevfile="/etc/udev/rules.d/80-net-devices.rules";
          // delete current entry
          exec('sudo sed -i "/^.*'.$vid.'.*'.$pid.'.*$/d" '.$udevfile);
          exec('sudo sed -i "/^.*'.$mac.'.*$/d" '.$udevfile);
          if( !empty($name)) {
             if( !empty($mac) ) $rule='SUBSYSTEM=="net", ACTION=="add", ATTR{address}=="'.$mac.'", NAME="'.$name.'"';
             else if( !empty($vid) )      $rule='SUBSYSTEM=="net", ACTION=="add", ATTRS{idVendor}=="'.$vid.'", ATTRS{idProduct}=="'.$pid.'", NAME="'.$name.'"';
             if ( !empty($rule) ) exec('echo \''.$rule.'\' | sudo /usr/bin/tee -a '.$udevfile);
          }
          $jsonData = ['return'=>0,'output'=>['Udev rules changed for device '.$dev]];
          echo json_encode($jsonData);
          return;
        }
    } else {
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
