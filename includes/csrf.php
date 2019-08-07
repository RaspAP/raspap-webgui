<?php

include_once('functions.php');
include_once('session.php');

if (csrfValidateRequest() && !CSRFValidate()) {
  handleInvalidCSRFToken();
}

ensureCSRFSessionToken();
header('X-CSRF-Token', $_SESSION['csrf_token']);
