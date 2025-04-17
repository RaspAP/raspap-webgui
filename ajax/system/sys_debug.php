<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

$root = getenv("DOCUMENT_ROOT");
exec('sudo '.RASPI_CONFIG.'/system/debuglog.sh -i '.$root, $return);

$logOutput = implode(PHP_EOL, $return);
$tempDir = sys_get_temp_dir();
$filePath = $tempDir . DIRECTORY_SEPARATOR . RASPI_DEBUG_LOG;
$handle = fopen($filePath, "w");
fwrite($handle, $logOutput);
fclose($handle);
echo json_encode($filePath);

