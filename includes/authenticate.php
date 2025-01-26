<?php

if (RASPI_AUTH_ENABLED) {
    $auth = new \RaspAP\Auth\HTTPAuth;

    if (!$auth->isLogged()) {
        $auth->authenticate();
    }
}

