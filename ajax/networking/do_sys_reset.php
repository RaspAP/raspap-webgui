<?php

require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

if (isset($_POST['csrf_token'])) {
    if (csrfValidateRequest() && !CSRFValidate()) {
        handleInvalidCSRFToken();
    }
    $return = 0;
    $path = "../../config";
    $configs = array(
        array("src" => $path .'/hostapd.conf', "tmp" => "/tmp/hostapddata", "dest" => RASPI_HOSTAPD_CONFIG),
        array("src" => $path .'/dhcpcd.conf', "tmp" => "/tmp/dhcpddata", "dest" => RASPI_DHCPCD_CONFIG),
        array("src" => $path .'/090_wlan0.conf', "tmp" => "/tmp/dnsmasqdata", "dest" => RASPI_DNSMASQ_PREFIX.'wlan0.conf'),
        array("src" => $path .'/090_raspap.conf', "tmp" => "/tmp/dnsmasqdata", "dest" => RASPI_DNSMASQ_PREFIX.'raspap.conf'),
    );
    
    foreach ($configs as $config) {
        try {
            $tmp = file_get_contents($config["src"]);
            file_put_contents($config["tmp"], $tmp);
            system("sudo cp ".$config["tmp"]. " ".$config["dest"]);
        } catch (Exception $e) {
            $return = $e->getCode();
        }
    }
    $jsonData = ['return'=>$return];
    echo json_encode($jsonData);

} else {
    handleInvalidCSRFToken();
}

