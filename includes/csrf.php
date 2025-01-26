<?php

require_once 'functions.php';

if (csrfValidateRequest() && !CSRFValidate()) {
    handleInvalidCSRFToken();
}
