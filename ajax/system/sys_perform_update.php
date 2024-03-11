<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

if (isset($_POST['csrf_token'])) {
    if (csrfValidateRequest() && !CSRFValidate()) {
        handleInvalidCSRFToken();
    }
    // set installer path + options
    $path = getenv("DOCUMENT_ROOT");
    $opts = " --update --yes --path $path";
    $installer = "sudo /etc/raspap/system/raspbian.sh";
    $execUpdate = $installer.$opts;

    $response = shell_exec($execUpdate);
    echo json_encode($response);

} else {
    handleInvalidCSRFToken();
}
