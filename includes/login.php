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
    $changeRequired = RASPI_AUTH_ENABLED && $auth->isPasswordChangeRequired();

    // handle page action
    if ($changeRequired) {
        if (isset($_POST['change-pass'])) {
            $status = changeDefaultPassword(
                $auth,
                (string)($_POST['newpass'] ?? ''),
                (string)($_POST['newpassagain'] ?? '')
            );
        }
    } elseif (RASPI_AUTH_ENABLED) {
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
            "redirectUrl",
            "changeRequired"
        )
    );
}

/**
 * Replaces the default admin password, then goes to the dashboard
 *
 * @param \RaspAP\Auth\HTTPAuth $auth
 * @param string $newpass
 * @param string $newpassagain
 * @return string error message
 */
function changeDefaultPassword(\RaspAP\Auth\HTTPAuth $auth, string $newpass, string $newpassagain): string
{
    // 8 characters at least, which also rules out the default password
    if (strlen($newpass) < 8) {
        return _("The new password must have at least 8 characters");
    }
    if ($newpass !== $newpassagain) {
        return _("New passwords do not match");
    }
    $messages = new \RaspAP\Messages\StatusMessage;
    updateAdminCredentials(null, $newpass, null, $auth->getAuthConfig(), $messages);

    // read the file again to confirm that the new password was saved
    $saved = (new \RaspAP\Auth\HTTPAuth)->getAuthConfig();
    if (!password_verify($newpass, $saved['admin_pass'])) {
        return _("Failed to save the new password");
    }
    $auth->passwordChanged();
    header('Location: /');
    die();
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
