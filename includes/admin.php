<?php

function DisplayAuthConfig($username)
{
    $status = new \RaspAP\Messages\StatusMessage;
    $auth = new \RaspAP\Auth\HTTPAuth;
    $config = $auth->getAuthConfig();
    $key = 'RASPI_MONITOR_ENABLED';
    $avatar = '';

    if (!file_exists($_COOKIE['avatar'] ?? '')) {
        $avatar = '<span class="embed-avatar"><i class="fa fa-user-circle avatar-placeholder"></i></span>';
    } else {
        $avatar = '<span class="embed-avatar" style="background-image: url(\''. $_COOKIE['avatar'] . '\'"></span>';
    }

    if (isset($_POST['authchangepw'])) {
        $password = $config['admin_pass'];
        if (password_verify($_POST['oldpass'], $password)) {
            if ($_POST['newpass'] !== $_POST['newpassagain']) {
                $status->addMessage('New passwords do not match', 'danger');
            } else {
                updateAdminCredentials(null, $_POST['newpass'], null, $config, $status);
            }
        } else {
            $status->addMessage('Old password does not match', 'danger');
        }
    } elseif (isset($_POST['adminlogout'])) {
        $auth->logout();
    } elseif (isset($_POST['authsavesettings'])) {
        $new_username=trim($_POST['username']);
        $nonprivEnabled = ($_POST['nonprivEnabled']);
        if ($new_username == '') {
            $status->addMessage('Username must not be empty', 'danger');
        } else {
            updateAdminCredentials($new_username, null, $nonprivEnabled, $config, $status);
        }
    } elseif (isset($_POST['authlogin'])) {
        $user = trim($_POST['username']);
        $pass = trim($_POST['password']);
        if ($auth->login($user, $pass, 'admin')) {
            $config = $auth->getAuthConfig();
            updateAdminCredentials(null, null, 0, $config, $status);
            $return = setConfigurationOption($key, 'false');
            $_SESSION['user_id'] = $user;
        } else {
            $status->addMessage('Admin login failed. Please try again.', 'danger');
        }
    } elseif (isset($_POST['authlogout'])) {
        $user = trim($_POST['modal_limited_user']);
        $pass = trim($_POST['modal_limited_pass']);
        updateAdminCredentials($config['admin_user'], null, true, $config, $status);
        updateLimitCredentials($user, $pass, $config, $status);
        if ($auth->isLogged()) {
            $auth->logout();
            $return = setConfigurationOption($key, 'true');
            $_SESSION['user_id'] = $user;
            if ($return === true) {
                $status->addMessage('Limited privilege user mode enabled', 'success');
            } else {
                $status->addMessage('Failed to enable limited privilege user mode', 'danger');
            }
        }
    } elseif (isset($_POST['resetAvatar'])) {
        define("IMAGE_DIR", $_SERVER['DOCUMENT_ROOT'] . '/app/img/avatars/*.*');
        array_map('unlink', glob(IMAGE_DIR));
    }
    $config = $auth->getAuthConfig();
    $username = $config['admin_user'];
    $limited_user = $config['limited_user'] ?? '';
    $nonprivEnabled = $auth->isNonPrivileged();
    $isLoggedIn = $auth->isLogged();
    echo renderTemplate(
        "admin", compact(
            "status",
            "username",
            "limited_user",
            "isLoggedIn",
            "nonprivEnabled",
            "avatar"
        )
    );
}

/**
 * Persists a set of credentials to the filesystem
 *
 * @param string $username optional
 * @param string $password optional
 * @param string $filePath
 * @param string $role
 * @param array $config
 * @param obj $status
 * @param bool|null $nonprivenabled optional
 * @return obj
 */
function updateCredentials($username = null, $password = null, $filePath, $role, $config, $status, $nonprivenabled = null)
{
    if (!file_exists($filePath)) {
        $tmpauth = fopen($filePath, 'w');
        fclose($tmpauth);
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    $auth_username = $lines[0] ?? null;
    $auth_password_hash = $lines[1] ?? null;

    if ($username !== null) {
        $auth_username = $username;
        $_SESSION['user_id'] = $username;
    }

    if ($password !== null) {
        $auth_password_hash = password_hash($password, PASSWORD_BCRYPT);
    } elseif ($username !== null && is_null($auth_password_hash)) {
        $auth_password_hash = $config['admin_pass'] ?? null;
    }

    // Fallback to default username if none is set
    if (empty($auth_username)) {
        $auth_username = 'admin';
    }

    // Handle nonprivenabled bit for admin role
    if ($role === 'Admin') {
        $nonprivenabled = $nonprivenabled ?? 0;
    }

    // Write updated credentials
    if ($fhandle = fopen($filePath, 'w')) {
        fwrite($fhandle, $auth_username . PHP_EOL);
        fwrite($fhandle, $auth_password_hash . PHP_EOL);
        if ($role === 'Admin') {
            fwrite($fhandle, $nonprivenabled . PHP_EOL);
        }
        fclose($fhandle);
        $status->addMessage("$role credentials updated successfully");
    } else {
        $status->addMessage("Failed to update $role credentials", 'danger');
    }

    return $status;
}

/**
 * Updates admin credentials
 *
 * @param string $username optional
 * @param string $password optional
 * @param boolean $nonprivenabled optional
 * @param array $config
 * @param obj $status
 */
function updateAdminCredentials($username = null, $password = null, $nonprivenabled = null, $config, $status)
{
    return updateCredentials($username, $password, RASPI_ADMIN_DETAILS, 'Admin', $config, $status, $nonprivenabled);
}

/**
 * Updates limited user credentials
 *
 * @param string $username optional
 * @param string $password optional
 * @param array $config
 * @param obj $status
 */
function updateLimitCredentials($username = null, $password = null, $config, $status)
{
    return updateCredentials($username, $password, RASPI_CONFIG . '/limited.auth', 'Limited user', $config, $status);
}
