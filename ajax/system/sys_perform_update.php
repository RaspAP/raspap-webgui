<?php

require '../../includes/csrf.php';

if (isset($_POST['csrf_token'])) {
    if (csrfValidateRequest() && !CSRFValidate()) {
        handleInvalidCSRFToken();
    }
    // set installer path + options
    $path = getenv("DOCUMENT_ROOT");
    $opts = " --update --path $path --yes";
    $installer = "sudo /etc/raspap/system/app-update.sh";
    $execUpdate = $installer.$opts;

    $response = shell_exec($execUpdate);
    echo json_encode($response);

} else {
    handleInvalidCSRFToken();
}

