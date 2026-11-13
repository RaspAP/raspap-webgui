<?php

require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

if (isset($_POST['csrf_token'])) {
    $key = 'RASPI_MONITOR_ENABLED';

    $auth = new \RaspAP\Auth\HTTPAuth;
    if ($auth->isLogged()) {
        $auth->logout();
        $return = setConfigurationOption($key, 'true');
    }
    echo json_encode($return); 
} else {
    handleInvalidCSRFToken();
}
