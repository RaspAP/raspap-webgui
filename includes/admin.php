<?php

require_once 'includes/status_messages.php';

function DisplayAuthConfig($username, $password)
{
    $status = new StatusMessages();
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
                    $status->addMessage('Admin password updated');
                } else {
                    $status->addMessage('Failed to update admin password', 'danger');
                }
            }
        } else {
            $status->addMessage('Old password does not match', 'danger');
        }
    }

    echo renderTemplate("admin", compact("status", "username"));
}
