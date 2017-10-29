<?php
function mask2cidr($mask){
  $long = ip2long($mask);
  $base = ip2long('255.255.255.255');
  return 32-log(($long ^ $base)+1,2);
}

function write_php_ini($array, $file) {
    $res = array();
    foreach($array as $key => $val) {
        if(is_array($val)) {
            $res[] = "[$key]";
            foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
        }
        else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
    }
    if(safefilerewrite($file, implode("\r\n", $res))) {
        return true;
    } else {
        return false;
    }
}

function safefilerewrite($fileName, $dataToSave) {
    if ($fp = fopen($fileName, 'w')) {
        $startTime = microtime(TRUE);
        do {
            $canWrite = flock($fp, LOCK_EX);
            // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
            if(!$canWrite) usleep(round(rand(0, 100)*1000));
        } while ((!$canWrite)and((microtime(TRUE)-$startTime) < 5));

        //file was locked so now we can store information
        if ($canWrite) {
            fwrite($fp, $dataToSave);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return true;
    } else {
        return false;
    }
}

    session_start();
    include_once('../../includes/config.php');
    include_once('../../includes/functions.php');
    if(isset($_POST['interface']) && isset($_POST['csrf_token']) && CSRFValidate()) {
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

        if(write_php_ini($cfg,RASPI_CONFIG_NETWORKING.'/'.$file)) {
            $jsonData = ['return'=>0,'output'=>['Successfully Updated Network Configuration']];
        } else {
            $jsonData = ['return'=>1,'output'=>['Error saving network configuration to file']];
        }
    } else {
        $jsonData = ['return'=>2,'output'=>'Unable to detect interface'];
    }
    echo json_encode($jsonData);
?>
