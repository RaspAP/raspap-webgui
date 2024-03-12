<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

exec('cat '. RASPI_HOSTAPD_CONFIG, $hostapdconfig);
$arrConfig = array();

foreach ($hostapdconfig as $hostapdconfigline) {
    if (strlen($hostapdconfigline) === 0) {
        continue;
    }
    $arrLine = explode("=", $hostapdconfigline);
    $arrConfig[$arrLine[0]]=$arrLine[1];
};
$channel = intval($arrConfig['channel']);
echo json_encode($channel);
