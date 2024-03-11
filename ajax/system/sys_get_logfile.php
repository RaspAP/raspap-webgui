<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

$tempDir = sys_get_temp_dir();
$filePath = $tempDir . DIRECTORY_SEPARATOR . RASPI_DEBUG_LOG;

if (isset($filePath)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename='.basename($filePath));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.filesize($filePath));
    readfile($filePath);
    exit();
} else {
    header('Location: '.'/system_info');
    exit();
}
