<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

exec('cat '. RASPI_HOSTAPD_CONFIG, $hostapdconfig);
$arrConfig = array();

foreach ($hostapdconfig as $hostapdconfigline) {
    if (strlen($hostapdconfigline) === 0) {
        continue;
    }
    $arrLine = explode("=", $hostapdconfigline);
    if (count($arrLine) >= 2) {
        $arrConfig[$arrLine[0]]=$arrLine[1];
    }
};
$channel = intval($arrConfig['channel']);
echo json_encode($channel);

