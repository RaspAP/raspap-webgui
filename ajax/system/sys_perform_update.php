<?php

require '../../includes/csrf.php';

if (isset($_POST['csrf_token'])) {
    if (csrfValidateRequest() && !CSRFValidate()) {
        handleInvalidCSRFToken();
    }
    // set installer path + options
    $path = getenv("DOCUMENT_ROOT");
    $opts = " --update --path $path --yes";
    $installer = "curl -sL https://install.raspap.com | bash -s -- ";
    $execUpdate = $installer.$opts;
    echo json_encode($execUpdate);

} else {
    handleInvalidCSRFToken();
}
