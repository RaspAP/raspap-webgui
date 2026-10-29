<?php

/**
 * Authentication class
 *
 * @description Basic HTTP authentication class for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 * @see         https://www.php.net/manual/en/features.http-auth.php
 */

declare(strict_types=1);

namespace RaspAP\Auth;

class HTTPAuth
{
    /**
     * Stored login credentials
     * @var array $auth_config
     */
    protected $auth_config;

    /**
     * Default login credentials
     * @var array $auth_default
     */
    private $auth_default = array(
        'admin_user' => 'admin',
        'admin_pass' => '$2y$10$YKIyWAmnQLtiJAy6QgHQ.eCpY4m.HCEbiHaTgN6.acNC6bDElzt.i'
    );

    // Constructor
    public function __construct()
    {
        $this->auth_config = $this->getAuthConfig();
    }

    /*
     * Determines if user is logged in
     * return boolean
     */
    public function isLogged()
    {
        return isset($_SESSION['user_id']);
    }

    /*
     * Authenticate a user using HTTP basic auth
     */
    public function authenticate()
    {
        if (!$this->isLogged()) {
            $redirectUrl = $_SERVER['REQUEST_URI'];
            if (strpos($redirectUrl, '/login') === false) {
                header('Location: /login?action=' . urlencode($redirectUrl));
                exit();
            }
        }
    }

    /*
     * Attempt to login a user with supplied credentials
     * @var string $user
     * @var string $pass
     * @var string $role optional
     * return boolean
     */
    public function login(string $user, string $pass, ?string $role = null)
    {
        if ($this->isValidCredentials($user, $pass, $role)) {
            $_SESSION['user_id'] = $user;
            return true;
        }
        return false;
    }

    /*
     * Logs out the administrative user
     */
    public function logout(): void
    {
        $locale = $_SESSION['locale'] ?? 'en_GB.UTF-8'; // save locale
        session_regenerate_id(true); // generate a new session id
        session_unset(); // unset all session variables
        session_destroy(); // destroy the session
        session_start();
        $_SESSION['locale'] = $locale;
        setcookie('locale', $locale, time() + (86400 * 30), '/', '', false, true);
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $redirectUrl = $_SERVER['REQUEST_URI'];
        if (strpos($redirectUrl, '/login') === false) {
            header('Location: ' . $basePath . '/login?action=' . urlencode(basename($redirectUrl)));
            exit();
        }
    }

    /*
     * Gets the current authentication config
     * return array $config
     */
    public function getAuthConfig()
    {
        $config = $this->auth_default;

        if (file_exists(RASPI_CONFIG . '/raspap.auth')) {
            if ($auth_details = fopen(RASPI_CONFIG . '/raspap.auth', 'r')) {
                $config['admin_user'] = trim(fgets($auth_details));
                $config['admin_pass'] = trim(fgets($auth_details));
                fclose($auth_details);
            }
        }
        if (file_exists(RASPI_CONFIG . '/limited.auth')) {
            if ($auth_details = fopen(RASPI_CONFIG . '/limited.auth', 'r')) {
                $config['limited_user'] = trim(fgets($auth_details));
                $config['limited_pass'] = trim(fgets($auth_details));
                fclose($auth_details);
            }
        }
        return $config;
    }

    /*
     * Gets the current non-privileged user setting
     * return boolean $nonprivilegedBit
     */
    public function isNonPrivileged()
    {
        $nonprivilegedBit = 0;
        $file = RASPI_CONFIG . '/raspap.auth';
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (count($lines) == 3) {
                $nonprivilegedBit = trim($lines[2]);
            }
        }
        return $nonprivilegedBit;
    }

    /*
     * Validates a set of credentials
     * @var string $user
     * @var string $pass
     * @var string $role optional
     * return boolean
     */
    protected function isValidCredentials(string $user, string $pass, ?string $role = null)
    {
        return $this->validateUser($user, $role) && $this->validatePassword($pass, $role);
    }

    /**
     * Validates a user
     *
     * @param string $user
     * @param string $role optional
     */
    protected function validateUser(string $user, ?string $role = null)
    {
        if ($role === 'admin') {
            return $user === $this->auth_config['admin_user'];
        }
        return $user == $this->auth_config['limited_user'];
    }

    /**
     * Validates a password
     *
     * @param string $pass
     * @param string $role optional
     */
    protected function validatePassword(string $pass, ?string $role = null)
    {
        if ($role === 'admin') {
            return password_verify($pass, $this->auth_config['admin_pass']);
        }
        return password_verify($pass, $this->auth_config['limited_pass']);
    }

}
