<?php

include_once('functions.php');
include_once('session.php');

if (csrfValidateRequest() && !CSRFValidate()) {
    handleInvalidCSRFToken();
}
