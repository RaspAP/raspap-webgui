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
    $role = !$auth->isNonPrivileged() ? 'admin' : null;
    $status = null;
    $redirectUrl = null;

    // handle page action
    if (RASPI_AUTH_ENABLED) {
        if (isset($_POST['login-auth'])) {
            // authenticate user
            $username = (string)($_POST['username'] ?? '');
            $password = (string)($_POST['password'] ?? '');
            $redirectUrl = safeRedirectUrl($_POST['redirect-url'] ?? null);
            $client = $_SERVER['REMOTE_ADDR'] ?? '';
            $limiter = getLoginLimiter();
            $wait = $limiter->waitTime($client);
            if ($wait > 0) {
                $status = loginLockMessage($wait);
            } elseif ($auth->login($username, $password, $role)) {
                $limiter->success($client);
                $config = $auth->getAuthConfig();
                header('Location: ' . $redirectUrl);
                die();
            } else {
                $limiter->failure($client);
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

/**
 * Returns the redirect URL after the login, if it is a URL of this site
 *
 * Only a path is accepted (for example "/dashboard" or "dashboard"), not a
 * URL with a scheme or a host. Otherwise the login goes to "/".
 *
 * @param mixed $url
 * @return string
 */
function safeRedirectUrl($url): string
{
    if (!is_string($url) || $url === '' || strncmp($url, '//', 2) === 0 ||
        !preg_match('#^/?[A-Za-z0-9_./?=&%-]*$#', $url)) {
        return '/';
    }
    return $url;
}
