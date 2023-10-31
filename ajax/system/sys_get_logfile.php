<?php 

require '../../includes/csrf.php';
require_once '../../includes/config.php';

$filePath = $_GET['filePath'];

if (isset($filePath) && strpos($filePath, RASPI_DEBUG_LOG) !== false) {
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

