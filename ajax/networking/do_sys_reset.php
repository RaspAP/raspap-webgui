<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/config.php';
require_once '../../includes/session.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

$return = 0;
$path = "../../config";
// the default files, installed through the config file helper
$configs = array(
    array("src" => $path .'/hostapd.conf', "kind" => "hostapd", "name" => null),
    array("src" => $path .'/dhcpcd.conf', "kind" => "dhcpcd", "name" => null),
    array("src" => $path .'/090_wlan0.conf', "kind" => "dnsmasq", "name" => "wlan0"),
    array("src" => $path .'/090_raspap.conf', "kind" => "dnsmasq", "name" => "raspap"),
);

foreach ($configs as $config) {
    $content = file_get_contents($config["src"]);
    if ($content === false || !\RaspAP\System\ConfigFile::install($config["kind"], $config["name"], $content)) {
        $return = 1;
    }
}
$jsonData = ['return'=>$return];
echo json_encode($jsonData);

