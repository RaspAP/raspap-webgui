<?php

require_once 'functions.php';
require_once 'session.php';

if (csrfValidateRequest() && !CSRFValidate()) {
    handleInvalidCSRFToken();
}
