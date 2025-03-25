<?php

function DisplayAuthConfig($username)
{
    $status = new \RaspAP\Messages\StatusMessage;
    $auth = new \RaspAP\Auth\HTTPAuth;
    $config = $auth->getAuthConfig();
    $username = $config['admin_user'];
    $password = $config['admin_pass'];

    if (isset($_POST['UpdateAdminPassword'])) {
        if (password_verify($_POST['oldpass'], $password)) {
            $new_username=trim($_POST['username']);
            if ($_POST['newpass'] !== $_POST['newpassagain']) {
                $status->addMessage('New passwords do not match', 'danger');
            } elseif ($new_username == '') {
                $status->addMessage('Username must not be empty', 'danger');
            } else {
                if (!file_exists(RASPI_ADMIN_DETAILS)) {
                    $tmpauth = fopen(RASPI_ADMIN_DETAILS, 'w');
                    fclose($tmpauth);
                }

                if ($auth_file = fopen(RASPI_ADMIN_DETAILS, 'w')) {
                    fwrite($auth_file, $new_username.PHP_EOL);
                    fwrite($auth_file, password_hash($_POST['newpass'], PASSWORD_BCRYPT).PHP_EOL);
                    fclose($auth_file);
                    $username = $new_username;
                    $_SESSION['user_id'] = $username;
                    $status->addMessage('Admin password updated');
                } else {
                    $status->addMessage('Failed to update admin password', 'danger');
                }
            }
        } else {
            $status->addMessage('Old password does not match', 'danger');
        }
    } elseif (isset($_POST['logout'])) {
        $auth->logout();
    }

    echo renderTemplate(
        "admin", compact(
            "status",
            "username"
        )
    );
}
