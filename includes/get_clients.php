<?php

require_once 'includes/functions.php';
require_once 'includes/wifi_functions.php';

function getClients($simple=true)
{
    exec('ifconfig -a | grep -oP "^(?!lo)(\w*)"', $rawdevs); // all devices except loopback
    $path=RASPI_CLIENT_SCRIPT_PATH;
    $cl=array();
    if (!empty($rawdevs) && is_array($rawdevs)) {
        $cl["clients"]=count($rawdevs);
        // search for possibly not connected modem 
        exec("find /sys/bus/usb/devices/usb*/ -name dev ", $devtty); // search for ttyUSB
        $devtty = preg_only_match("/(ttyUSB0)/", $devtty);
        if (empty(preg_only_match("/(ppp)[0-9]/", $rawdevs))) {
            if (!empty($devtty)) {
                $rawdevs[]="ppp0";
                exec("udevadm info --name='$devtty' 2> /dev/null");
            }
        }
        foreach ($rawdevs as $i => $dev) {
            $cl["device"][$i]["name"]=$dev;
            $nam = (preg_match("/^(\w+)[0-9]$/",$dev,$nam) === 1) ? $nam=$nam[1] : "";
            $cl["device"][$i]["type"]=$ty=getClientType($dev);
            unset($udevinfo);
            exec("udevadm info /sys/class/net/$dev 2> /dev/null", $udevinfo);
            if ($nam == "ppp" && isset($devtty)) {
                exec("udevadm info --name='$devtty' 2> /dev/null", $udevinfo);
            }
            if (!empty($udevinfo) && is_array($udevinfo)) {
                $model = preg_only_match("/ID_MODEL_ENC=(.*)$/", $udevinfo);
                if (empty($model) || preg_match("/^[0-9a-f]{4}$/", $model) === 1) {
                     $model = preg_only_match("/ID_MODEL_FROM_DATABASE=(.*)$/", $udevinfo);
                }
                if (empty($model)) {
                    $model = preg_only_match("/ID_OUI_FROM_DATABASE=(.*)$/", $udevinfo);
                }
                $vendor = preg_only_match("/ID_VENDOR_ENC=(.*)$/", $udevinfo);
                if (empty($vendor) || preg_match("/^[0-9a-f]{4}$/", $vendor) === 1) {
                    $vendor = preg_only_match("/ID_VENDOR_FROM_DATABASE=(.*)$/", $udevinfo);
                }
                $driver = preg_only_match("/ID_NET_DRIVER=(.*)$/", $udevinfo);
                $vendorid = preg_only_match("/ID_VENDOR_ID=(.*)$/", $udevinfo);
                $productid = preg_only_match("/ID_MODEL_ID=(.*)$/", $udevinfo);
            }
            $cl["device"][$i]["model"] = preg_replace("/\\\\x20/", " ", $model);
            $cl["device"][$i]["vendor"] = preg_replace("/\\\\x20/", " ", $vendor);
            $cl["device"][$i]["vid"] = $vendorid;
            $cl["device"][$i]["pid"] = $productid;
            unset($mac);
            exec("cat /sys/class/net/$dev/address 2> /dev/null", $mac);
            $cl["device"][$i]["mac"] = empty($mac) ? "":$mac[0];
            unset($ip);
            exec("ifconfig $dev 2> /dev/null", $ip);
            $cl["device"][$i]["ipaddress"] =  preg_only_match("/.*inet ([0-9\.]+) .*/", $ip);

            switch($ty) {
            case "eth":
                unset($res);
                exec("ip link show $dev 2> /dev/null | grep -oP ' UP '", $res);
                if (empty($res) && empty($ipadd)) {
                    $cl["device"][$i]["connected"] = "n";
                } else {
                    $cl["device"][$i]["connected"] = "y";
                }
                break;
            case "wlan":
                unset($retiw);
                exec("iwconfig $dev 2> /dev/null | sed -rn 's/.*(mode:master).*/1/ip'", $retiw);
                $cl["device"][$i]["isAP"] = !empty($retiw);
                unset($retiw);
                exec("iw dev $dev link 2> /dev/null", $retiw);
                if (!$simple && !empty($ssid=preg_only_match("/.*SSID:\s*([^\"]*).*/", $retiw)) ) {
                    $cl["device"][$i]["connected"] = "y";
                    $cl["device"][$i]["ssid"] = $ssid;
                    $cl["device"][$i]["ssidutf8"] = ssid2utf8($ssid);
                    $cl["device"][$i]["ap-mac"] = preg_only_match("/^Connected to ([0-9a-f\:]*).*$/", $retiw);
                    $sig = preg_only_match("/.*signal: (.*)$/", $retiw);
                    $val = preg_only_match("/^([0-9\.-]*).*$/", $sig);
                    if (!is_numeric($val)) {
                        $val = -100;
                    }
                    if ($val >= -50 ) {
                        $qual=100;
                    } else if ($val < -100) {
                        $qual=0;
                    } else {
                        $qual=round($val*2+200);
                    }
                    $cl["device"][$i]["signal"] = "$sig (".$qual."%)";
                    $cl["device"][$i]["bitrate"] = preg_only_match("/.*bitrate: ([0-9\.]* \w*\/s).*$/", $retiw);
                    $cl["device"][$i]["freq"] = preg_only_match("/.*freq: (.*)$/", $retiw);
                    $cl["device"][$i]["ap-mac"] = preg_only_match("/^Connected to ([0-9a-f\:]*).*$/", $retiw);
                } else {
                    $cl["device"][$i]["connected"] = "n";
                }
                break;
            case "ppp":
                unset($res);
                exec("ip link show $dev 2> /dev/null | grep -oP '( UP | UNKNOWN)'", $res);
                if ($simple) {
                    if (empty($res)) {
                        $cl["device"][$i]["connected"] = "n";
                        $cl["device"][$i]["signal"] =  "-100 dB (0%)";
                    } else {
                        $cl["device"][$i]["connected"] = "y";
                        $cl["device"][$i]["signal"] =  "-0 dB (0%)";
                    }
                    break;
                }
                if (empty($res) && empty($ipadd)) {
                    $cl["device"][$i]["connected"] = "n";
                } else {
                    $cl["device"][$i]["connected"] = "y";
                }
                unset($res);
                exec("$path/info_huawei.sh mode modem", $res);
                $cl["device"][$i]["mode"] = $res[0];
                unset($res);
                exec("$path/info_huawei.sh device modem", $res);
                if ($res[0] != "none" ) {
                    $cl["device"][$i]["model"] = $res[0];
                }
                unset($res);
                exec("$path/info_huawei.sh signal modem", $res);
                $cl["device"][$i]["signal"] = $res[0];
                unset($res);
                exec("$path/info_huawei.sh operator modem", $res);
                $cl["device"][$i]["operator"] = $res[0];
                break;
            case "hilink":
				$pin=$user=$pw="";
				getMobileLogin($pin,$pw,$user);
				$opts=$pin.' '.$user.' '.$pw;
                unset($res);
                //              exec("ip link show $dev 2> /dev/null | grep -oP ' UP '",$res);
                exec("ifconfig -a | grep -i $dev -A 1 | grep -oP '(?<=inet )([0-9]{1,3}\.){3}'", $apiadd);
                $apiadd = !empty($apiadd) ? $apiadd[0]."1" : "";
                unset($res);
                exec("$path/info_huawei.sh mode hilink $apiadd \"$opts\" ", $res);
                $cl["device"][$i]["mode"] = $res[0];
                unset($res);
                exec("$path/info_huawei.sh device hilink $apiadd \"$opts\" ", $res);
                if ($res[0] != "none" ) {
                    $cl["device"][$i]["model"] = $res[0];
                }
                unset($res);
                exec("$path/info_huawei.sh signal hilink $apiadd \"$opts\" ", $res);
                $cl["device"][$i]["signal"] = $res[0];
                unset($ipadd);
                exec("$path/info_huawei.sh ipaddress hilink $apiadd \"$opts\" ", $ipadd);
                if (!empty($ipadd) && $ipadd[0] !== "none" ) {
                    $cl["device"][$i]["connected"] = "y";
                    $cl["device"][$i]["wan_ip"] = $ipadd[0];
                } else {
                    $cl["device"][$i]["connected"] = "n";
                    $cl["device"][$i]["wan_ip"] = "-";
                }
                unset($res);
                exec("$path/info_huawei.sh operator hilink $apiadd \"$opts\" ", $res);
                $cl["device"][$i]["operator"] = $res[0];
                break;
            case "phone":
            case "usb":
                $cl["device"][$i]["connected"] = "y";
                break;
            default:
            }
            if (!isset($cl["device"][$i]["signal"])) {
                $cl["device"][$i]["signal"]= $cl["device"][$i]["connected"] == "n" ? "-100 dB (0%)": "0 dB (100%)";;
            }
            if (!isset($cl["device"][$i]["isAP"])) {
                $cl["device"][$i]["isAP"]=false;
            }
        }
    }
    return $cl;
}

function getClientType($dev) {
    loadClientConfig();
    // check if device type stored in DEVTYPE or raspapType (from UDEV rule) protperty of the device
    exec("udevadm info /sys/class/net/$dev 2> /dev/null", $udevadm);
    $type="none";
    if (!empty($udevadm)) {
         $type=preg_only_match("/raspapType=(\w*)/i",$udevadm);
        if (empty($type)) {
            $type=preg_only_match("/DEVTYPE=(\w*)/i",$udevadm);
        }
    }
    if (empty($type) || $type == "none" || array_search($type, $_SESSION["net-device-name-prefix"]) === false) {
        // no device type yet -> get device type from device name 
        if (preg_match("/^(\w+)[0-9]$/",$dev,$nam) === 1) $nam=$nam[1];
        else $nam="none";
        if (($n = array_search($nam, $_SESSION["net-device-name-prefix"])) === false) $n = count($_SESSION["net-device-types"])-1;
        $type = $_SESSION["net-device-types"][$n];
    }
    return $type;
}

function getMobileLogin(&$pin,&$pw,&$user) {
	if (file_exists(($f = RASPI_MOBILEDATA_CONFIG))) {
		$dat = parse_ini_file($f);
		$pin = (isset($dat["pin"]) && preg_match("/^[0-9]*$/", $dat["pin"])) ? "-p ".$dat["pin"] : "";
		$user = (isset($dat["router_user"]) && !empty($dat["router_user"]) ) ? "-u ".$dat["router_user"] : "";
		$pw = (isset($dat["router_pw"]) && !empty($dat["router_pw"]) ) ? "-P ".$dat["router_pw"] : "";
	}
}

function loadClientConfig()
{
    // load network device config file for UDEV rules into $_SESSION
    if (!isset($_SESSION["udevrules"])) {
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
            $_SESSION["udevrules"]= null;
        }
        $_SESSION["net-device-types"][]="none";
        $_SESSION["net-device-types-info"][]="unknown";
        $_SESSION["net-device-name-prefix"][]="none";
    }
}

function findCurrentClientIndex($clients)
{
    $devid = -1;
    if (!empty($clients)) {
        $ncl=$clients["clients"];
        if ($ncl > 0) {
            $ty=-1;
            foreach ($clients["device"] as $i => $dev) {               
                $id=array_search($dev["type"], $_SESSION["net-device-types"]);
                if ($id >=0 && $_SESSION["udevrules"]["network_devices"][$id]["clientid"] > $ty && !$dev["isAP"]) {
                    $ty=$id;
                    $devid=$i;
                }
            }
        }
    }
    return $devid;
}

function waitClientConnected($dev, $timeout=10)
{
    do {
        exec('ifconfig -a | grep -i '.$dev.' -A 1 | grep -oP "(?<=inet )([0-9]{1,3}\.){3}[0-9]{1,3}"', $res);
        $connected= !empty($res);
        if (!$connected) {
            sleep(1);
        }
    } while (!$connected && --$timeout > 0);
    return $connected;
}

function setClientState($state)
{
    $clients=getClients();
    if (($idx = findCurrentClientIndex($clients)) >= 0) {
        $dev = $clients["device"][$idx];
        exec('ifconfig -a | grep -i '.$dev["name"].' -A 1 | grep -oP "(?<=inet )([0-9]{1,3}\.){3}[0-9]{1,3}"', $res);
        if (!empty($res)) {
            $connected=$res[0];
        }
        switch($dev["type"]) {
        case "wlan":
            if ($state =="up") {
                exec('sudo ip link set '.$dev["name"].' up');
            }
            if (!empty($connected) && $state =="down") {
                exec('sudo ip link set '.$dev["name"].' down');
            }
            break;
        case "hilink":
            preg_match("/^([0-9]{1,3}\.){3}/", $connected, $ipadd);
            $ipadd = $ipadd[0].'1'; // ip address of the Hilink api
            $mode = ($state == "up") ? 1 : 0;
            $pin=$user=$pw="";
			getMobileLogin($pin,$pw,$user);
            exec('sudo '.RASPI_CLIENT_SCRIPT_PATH.'/onoff_huawei_hilink.sh -c '.$mode.' -h '.$ipadd.' '.$pin.' '.$user.' '.$pw);
            break;
        case "ppp":
            if ($state == "up") {
                exec('sudo ifup '.$dev["name"]);
            }
            if (!empty($connected) && $state == "down") {
                exec('sudo ifdown '.$dev["name"]);
            }
            break;
        default:
            break;
        }
        if ($state=="up") {
            waitClientConnected($dev["name"], 15);
        }
    }
}
