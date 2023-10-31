<?php 

require '../../includes/csrf.php';

$filePath = $_GET['filePath'];
$filename = "raspap_debug.log";

if (isset($filePath) && strpos($filePath, $filename) !== false) {
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

