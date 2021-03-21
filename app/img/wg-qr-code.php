<?php

require_once '../../includes/config.php';
require_once '../../includes/defaults.php';
require_once '../../includes/functions.php';

// prevent direct file access
if (!isset($_SERVER['HTTP_REFERER'])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

exec("sudo cat " .RASPI_WIREGUARD_PATH.'client.conf', $return);
$peer_conf = implode(PHP_EOL,$return);
$peer_conf.= PHP_EOL;
$command = "qrencode -t svg -m 0 -o - " . mb_escapeshellarg($peer_conf);
$svg = shell_exec($command);
$etag = hash('sha256', $peer_conf);
$content_length = strlen($svg);
$last_modified = date("Y-m-d H:i:s");

header("Content-Type: image/svg+xml");
header("Content-Length: $content_length");
header("Last-Modified: $last_modified");
header("ETag: \"$etag\"");
header("X-QR-Code-Content: $peer_conf");
echo shell_exec($command);

