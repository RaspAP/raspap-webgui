<?php
/*
 * Shows the QR code of the login help page on the login page.
 *
 * The page takes no input. The URL is RASPI_LOGIN_HELP_URL (defaults.php,
 * override it in config.php). qrencode makes the SVG once, and the private
 * cache directory keeps it. The cache file name contains a hash of the URL,
 * so a new URL gives a new QR code.
 */

require_once '../../includes/config.php';
require_once '../../includes/defaults.php';
require_once '../../includes/functions.php';

$uri = RASPI_LOGIN_HELP_URL;
$hash = hash('sha256', $uri);

$svg = '';
$cacheDir = get_private_cache_dir();
$cacheFile = $cacheDir === null ? null : $cacheDir . '/login-help-qr-' . $hash . '.svg';
if ($cacheFile !== null && is_file($cacheFile) && !is_link($cacheFile)) {
    $svg = (string)file_get_contents($cacheFile);
}

if (strpos($svg, '<svg') === false) {
    $svg = (string)shell_exec('qrencode -t svg -m 0 -o - ' . escapeshellarg($uri));
    if (strpos($svg, '<svg') === false) {
        error_log('QR generation failed for the login help URL');
        header('HTTP/1.1 500 Internal Server Error');
        exit('Failed to generate QR code');
    }
    if ($cacheFile !== null) {
        $tmpFile = tempnam($cacheDir, 'qr');
        if ($tmpFile !== false) {
            if (file_put_contents($tmpFile, $svg) === false || !rename($tmpFile, $cacheFile)) {
                @unlink($tmpFile);
            }
        }
    }
}

header('Content-Type: image/svg+xml');
header('Content-Length: ' . strlen($svg));
header('Cache-Control: public, max-age=86400');
header('ETag: "' . $hash . '"');
echo $svg;
