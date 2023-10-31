<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';

if (isset($_POST['csrf_token'])) {
    if (csrfValidateRequest() && !CSRFValidate()) {
        handleInvalidCSRFToken();
    }
    exec( RASPI_CONFIG.'/system/debuglog.sh', $return);

    $logOutput = implode(PHP_EOL, $return);
    $filename = "raspap_debug.log";
    $tempDir = sys_get_temp_dir();
    $filePath = $tempDir . DIRECTORY_SEPARATOR . $filename;
    $handle = fopen($filePath, "w");
    fwrite($handle, $logOutput);
    fclose($handle);
    echo json_encode($filePath);

} else {
    handleInvalidCSRFToken();
}

