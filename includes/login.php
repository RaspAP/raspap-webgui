<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

/**
 * Handler for administrative user login
 */
function DisplayLogin()
{
    // initialize auth object
    $auth = new \RaspAP\Auth\HTTPAuth;
    $status = null;
    $redirectUrl = null;

    // handle page action
    if (RASPI_AUTH_ENABLED) {
        if (isset($_POST['login-auth'])) {
            // authenticate user
            $username = $_POST['username'];
            $password = $_POST['password'];
            $redirectUrl = $_POST['redirect-url'];
            if ($auth->login($username, $password)) {
                $config = $auth->getAuthConfig();
                header('Location: ' . $redirectUrl);
                die();
            } else {
                $status = "Login failed";
            }
        }
    }

    echo renderTemplate(
        "login", compact(
            "status",
            "redirectUrl"
        )
    );
}

