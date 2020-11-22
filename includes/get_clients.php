<?php

require_once 'includes/functions.php';

function getClients($simple=true) {
    exec ('ifconfig -a | grep -oP "^(?!lo)(\w*)"',$rawdevs); // all devices except loopback
    $path=RASPI_CLIENT_SCRIPT_PATH;
    $cl=array();
    if(!empty($rawdevs) && is_array($rawdevs)) {
      $cl["clients"]=count($rawdevs);
      // search for possibly not connected modem 
      exec("find /sys/bus/usb/devices/usb*/ -name dev ",$devtty); // search for ttyUSB
      $devtty = preg_only_match("/(ttyUSB0)/",$devtty);
      if( empty(preg_only_match("/(ppp)[0-9]/",$rawdevs))) {
        if(!empty($devtty)) {
          $rawdevs[]="ppp0";
          exec("udevadm info --name='$devtty' 2> /dev/null");
        }
      }
      foreach ($rawdevs as $i => $dev) {
        $cl["device"][$i]["name"]=$dev;
        if (preg_match("/^(\w+)[0-9]$/",$dev,$nam) === 1) $nam=$nam[1];
        else $nam="none";
        if (($n = array_search($nam,$_SESSION["net-device-name-prefix"])) === false) $n = count($_SESSION["net-device-types"])-1;
        $ty = $_SESSION["net-device-types"][$n];
        $cl["device"][$i]["type"]=$ty;
        unset($udevinfo);
        exec("udevadm info /sys/class/net/$dev 2> /dev/null",$udevinfo);
        if ( $nam == "ppp" && isset($devtty))  exec("udevadm info --name='$devtty' 2> /dev/null", $udevinfo);
        if(!empty($udevinfo) && is_array($udevinfo)) {
          $model = preg_only_match("/ID_MODEL_ENC=(.*)$/",$udevinfo);
          if(empty($model) || preg_match("/^[0-9a-f]{4}$/",$model) === 1) {
             $model = preg_only_match("/ID_MODEL_FROM_DATABASE=(.*)$/",$udevinfo);
          }
          $vendor = preg_only_match("/ID_VENDOR_ENC=(.*)$/",$udevinfo);
          if(empty($vendor) || preg_match("/^[0-9a-f]{4}$/",$vendor) === 1) {
             $vendor = preg_only_match("/ID_VENDOR_FROM_DATABASE=(.*)$/",$udevinfo);
          }
          $driver = preg_only_match("/ID_NET_DRIVER=(.*)$/",$udevinfo);
          $vendorid = preg_only_match("/ID_VENDOR_ID=(.*)$/",$udevinfo);
          $productid = preg_only_match("/ID_MODEL_ID=(.*)$/",$udevinfo);
        }
        $cl["device"][$i]["model"] = preg_replace("/\\\\x20/"," ",$model);
        $cl["device"][$i]["vendor"] = preg_replace("/\\\\x20/"," ",$vendor);
        $cl["device"][$i]["vid"] = $vendorid;
        $cl["device"][$i]["pid"] = $productid;
        unset($mac);
        exec("cat /sys/class/net/$dev/address 2> /dev/null",$mac);
        $cl["device"][$i]["mac"] = empty($mac) ? "":$mac[0];
        unset($ip);
        exec("ifconfig $dev 2> /dev/null",$ip);
        $cl["device"][$i]["ipaddress"] =  preg_only_match("/.*inet ([0-9\.]+) .*/",$ip);

        switch($ty) {
           case "eth":
              unset($res);
              exec("ip link show $dev 2> /dev/null | grep -oP ' UP '",$res);
              if(empty($res) && empty($ipadd)) $cl["device"][$i]["connected"] = "n";
              else $cl["device"][$i]["connected"] = "y";
              break;
           case "wlan":
              unset($retiw);
              exec("iwconfig $dev 2> /dev/null | sed -rn 's/.*(mode:master).*/1/ip'",$retiw);
              $cl["device"][$i]["isAP"] = !empty($retiw);
              unset($retiw);
              exec("iw dev $dev link 2> /dev/null",$retiw);
              if(!$simple && !empty($ssid=preg_only_match("/.*SSID: (\w*).*/",$retiw)) ) {
                 $cl["device"][$i]["connected"] = "y";
                 $cl["device"][$i]["ssid"] = $ssid;
                 $cl["device"][$i]["ap-mac"] = preg_only_match("/^Connected to ([0-9a-f\:]*).*$/",$retiw);
                 $sig = preg_only_match("/.*signal: (.*)$/",$retiw);
                 $val = preg_only_match("/^([0-9\.-]*).*$/",$sig);
                 if (!is_numeric($val)) $val = -100;
                 if( $val >= -50 ) $qual=100;
                 else if( $val < -100) $qual=0;
                 else $qual=round($val*2+200);
                 $cl["device"][$i]["signal"] = "$sig (".$qual."%)";
                 $cl["device"][$i]["bitrate"] = preg_only_match("/.*bitrate: ([0-9\.]* \w*\/s).*$/",$retiw);
                 $cl["device"][$i]["freq"] = preg_only_match("/.*freq: (.*)$/",$retiw);
                 $cl["device"][$i]["ap-mac"] = preg_only_match("/^Connected to ([0-9a-f\:]*).*$/",$retiw);
              } else $cl["device"][$i]["connected"] = "n";
              break;
           case "ppp":
              unset($res);
              exec("ip link show $dev 2> /dev/null | grep -oP '( UP | UNKNOWN)'",$res);
              if($simple) {
                if(empty($res)) {
                  $cl["device"][$i]["connected"] = "n";
                  $cl["device"][$i]["signal"] =  "-100 dB (0%)";
                } else {
                  $cl["device"][$i]["connected"] = "y";
                  $cl["device"][$i]["signal"] =  "-0 dB (0%)";
                }
                break;
              }
              if(empty($res) && empty($ipadd)) $cl["device"][$i]["connected"] = "n";
              else $cl["device"][$i]["connected"] = "y";
              unset($res);
              exec("$path/info_huawei.sh mode modem",$res);
              $cl["device"][$i]["mode"] = $res[0];
              unset($res);
              exec("$path/info_huawei.sh device modem",$res);
              if( $res[0] != "none" ) $cl["device"][$i]["model"] = $res[0];
              unset($res);
              exec("$path/info_huawei.sh signal modem",$res);
              $cl["device"][$i]["signal"] = $res[0];
              unset($res);
              exec("$path/info_huawei.sh operator modem",$res);
              $cl["device"][$i]["operator"] = $res[0];
              break;
           case "hilink":
              unset($res);
//              exec("ip link show $dev 2> /dev/null | grep -oP ' UP '",$res);
              exec("ifconfig -a | grep -i $dev -A 1 | grep -oP '(?<=inet )([0-9]{1,3}\.){3}'",$apiadd);
              $apiadd = !empty($apiadd) ? $apiadd[0]."1" : "";
              unset($res);
              exec("$path/info_huawei.sh mode hilink $apiadd",$res);
              $cl["device"][$i]["mode"] = $res[0];
              unset($res);
              exec("$path/info_huawei.sh device hilink $apiadd",$res);
              if( $res[0] != "none" ) $cl["device"][$i]["model"] = $res[0];
              unset($res);
              exec("$path/info_huawei.sh signal hilink $apiadd",$res);
              $cl["device"][$i]["signal"] = $res[0];
              unset($ipadd);
              exec("$path/info_huawei.sh ipaddress hilink $apiadd",$ipadd);
              if(!empty($ipadd) && $ipadd[0] !== "none" ) {
                $cl["device"][$i]["connected"] = "y";
                $cl["device"][$i]["wan_ip"] = $ipadd[0];
              }
              else  $cl["device"][$i]["connected"] = "n";
              unset($res);
              exec("$path/info_huawei.sh operator hilink $apiadd",$res);
              $cl["device"][$i]["operator"] = $res[0];
              break;
           case "phone":
              break;
           default:
        }
      }
    }
    return $cl;
}

function load_client_config() {
// load network device config file for UDEV rules into $_SESSION
    if(true) {
//    if(!isset($_SESSION["udevrules"])) {
      $_SESSION["net-device-types"]=array();
      $_SESSION["net-device-name-prefix"]=array();
      try {
          $udevrules = file_get_contents(RASPI_CLIENT_CONFIG_PATH);
          $_SESSION["udevrules"] = json_decode($udevrules, true);
          // get device types
          foreach ($_SESSION["udevrules"]["network_devices"] as $dev) {
             $_SESSION["net-device-name-prefix"][]=$dev["name_prefix"];
             $_SESSION["net-device-types"][]=$dev["type"];
             $_SESSION["net-device-types-info"][]=$dev["type_info"];
          }
      } catch (Exception $e) {
          $_SESSION["udevrules"]= NULL;
      }
      $_SESSION["net-device-types"][]="none";
      $_SESSION["net-device-types-info"][]="unknown";
      $_SESSION["net-device-name-prefix"][]="none";
    }
}

?>

