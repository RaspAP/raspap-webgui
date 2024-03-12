<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

if (isset($_POST['csrf_token'])) {
    if (csrfValidateRequest() && !CSRFValidate()) {
        handleInvalidCSRFToken();
    }
    $root = getenv("DOCUMENT_ROOT");
    exec('sudo '.RASPI_CONFIG.'/system/debuglog.sh -i '.$root, $return);

    $logOutput = implode(PHP_EOL, $return);
    $tempDir = sys_get_temp_dir();
    $filePath = $tempDir . DIRECTORY_SEPARATOR . RASPI_DEBUG_LOG;
    $handle = fopen($filePath, "w");
    fwrite($handle, $logOutput);
    fclose($handle);
    echo json_encode($filePath);
} else {
    handleInvalidCSRFToken();
}
