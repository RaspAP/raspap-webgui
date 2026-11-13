<?php

if (session_status() == PHP_SESSION_NONE) {
    // the session cookie: not readable by scripts, not sent with requests
    // from other sites, and only sent over HTTPS when the page uses HTTPS
    session_set_cookie_params([
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

if (!isset($_SESSION['lastActivity'])) {
    $_SESSION['lastActivity'] = time();
}

