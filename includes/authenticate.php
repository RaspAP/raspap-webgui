<?php

if (RASPI_AUTH_ENABLED) {
    $user = $_SERVER['PHP_AUTH_USER'] ?? '';
    $pass = $_SERVER['PHP_AUTH_PW'] ?? '';

    $auth = new \RaspAP\Auth\HTTPAuth;

    if (!$auth->isLogged()) {
        if ($auth->login($user, $pass)) {
            $config = $auth->getAuthConfig();
        } else {
            $auth->authenticate();
        }
    }
}
