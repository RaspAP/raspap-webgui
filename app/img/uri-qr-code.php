<?php

if (!isset($_GET['uri']) || !filter_var($_GET['uri'], FILTER_VALIDATE_URL)) {
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid or missing URI parameter");
}

$uri = $_GET['uri'];
$command = "qrencode -t svg -m 0 -o - " . escapeshellarg($uri);

$svg = shell_exec($command);
if ($svg === null) {
    error_log("QR generation failed for URI: $uri");
    header("HTTP/1.1 500 Internal Server Error");
    exit("Failed to generate QR code");
}

$etag = hash('sha256', $uri);
$content_length = strlen($svg);
$last_modified = gmdate("D, d M Y H:i:s") . " GMT";

header("Content-Type: image/svg+xml");
header("Content-Length: $content_length");
header("Last-Modified: $last_modified");
header("ETag: \"$etag\"");
header("X-QR-Code-Content: " . htmlspecialchars($uri, ENT_QUOTES, 'UTF-8'));

echo $svg;

