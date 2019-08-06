<?php

include_once('includes/functions.php');
include_once('includes/session.php');

if (csrfValidateRequest() && !CSRFValidate()) {
  handleInvalidCSRFToken();
}

ensureCSRFSessionToken();
header('X-CSRF-Token', $_SESSION['csrf_token']);
