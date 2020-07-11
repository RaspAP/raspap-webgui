<?php

require_once '../../includes/config.php';
require_once '../../includes/defaults.php';
require_once '../../includes/functions.php';

// prevent direct file access
if (!isset($_SERVER['HTTP_REFERER'])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

function qr_encode($str)
{
    return preg_replace('/(?<!\\\)([\":;,])/', '\\\\\1', $str);
}

$hostapd = parse_ini_file(RASPI_HOSTAPD_CONFIG, false, INI_SCANNER_RAW);

// assume wpa encryption and get the passphrase
$type = "WPA";
$password = isset($hostapd['wpa_psk']) ? $hostapd['wpa_psk'] : $hostapd['wpa_passphrase'];

// use wep if configured
$wep_default_key = intval($hostapd['wep_default_key']);
$wep_key = 'wep_key' . $wep_default_key;
if (array_key_exists($wep_key, $hostapd)) {
    $type = "WEP";
    $password = $hostapd[$wep_key];
}

// if password is still empty, assume nopass
if (empty($password)) {
    $type = "nopass";
}

$ssid = $hostapd['ssid'];
$hidden = intval($hostapd['ignore_broadcast_ssid']) != 0 ? "H:true" : "";

$ssid = qr_encode($ssid);
$password = qr_encode($password);

$data = "WIFI:S:$ssid;T:$type;P:$password;$hidden;";
$command = "qrencode -t svg -m 0 -o - " . mb_escapeshellarg($data);
$svg = shell_exec($command);

$config_mtime  = filemtime(RASPI_HOSTAPD_CONFIG);
$last_modified = gmdate('D, d M Y H:i:s ', $config_mtime) . 'GMT';
$etag = hash('sha256', $data);
$content_length = strlen($svg);

header("Content-Type: image/svg+xml");
header("Content-Length: $content_length");
header("Last-Modified: $last_modified");
header("ETag: \"$etag\"");
header("X-QR-Code-Content: $data");
echo shell_exec($command);

