<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';

if (isset($_POST['csrf_token'])) {
    if (csrfValidateRequest() && !CSRFValidate()) {
        handleInvalidCSRFToken();
    }
    exec( RASPI_CONFIG.'/system/debuglog.sh', $return);
    echo json_encode(end($return));
} else {
    handleInvalidCSRFToken();
}

