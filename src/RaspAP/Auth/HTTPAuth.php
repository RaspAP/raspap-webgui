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

    /**
     * Default admin password, in plain text
     */
    public const DEFAULT_ADMIN_PASS = 'secret';

    /**
     * Scripts a user may still reach before they change the default password
     */
    private const PASSWORD_CHANGE_ALLOWED = array(
        'ajax/auth/admin_logout.php',
        'ajax/session/do_check_session.php',
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
            if (!$this->isLoginRoute()) {
                header('Location: /login?action=' . urlencode($redirectUrl));
                exit();
            }
        }
    }

    /*
     * Determines if the current request targets the login route.
     *
     * The check is on the request path only, never the query string, so a
     * value such as "?x=/login" cannot keep an unauthenticated request from
     * being redirected to the login page.
     * return boolean
     */
    private function isLoginRoute(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        return basename(rtrim($path, '/')) === 'login';
    }

    /*        
     * Determines if the admin password is still the default one
     * return boolean
     */
    public function isDefaultPassword(): bool
    {
        return password_verify(self::DEFAULT_ADMIN_PASS, $this->auth_config['admin_pass']);
    }

    /*
     * Determines if the logged in user must change the default password.
     * The check runs once per login session, since password_verify() is slow.
     * return boolean
     */
    public function isPasswordChangeRequired(): bool
    {
        if (!$this->isLogged()) {
            return false;
        }
        if (!isset($_SESSION['password_change_required'])) {
            $_SESSION['password_change_required'] = !$this->isNonPrivileged() && $this->isDefaultPassword();
        }
        return $_SESSION['password_change_required'];
    }

    /*
     * Marks the default password as changed for this session
     */
    public function passwordChanged(): void
    {
        session_regenerate_id(true);
        $_SESSION['password_change_required'] = false;
    }

    /*
     * Sends a user who must change the default password to the login page.
     * Other requests (ajax endpoints, QR codes) are refused.
     */
    public function enforcePasswordChange(): void
    {
        if (!$this->isPasswordChangeRequired()) {
            return;
        }
        $script = ltrim($_SERVER['SCRIPT_NAME'] ?? '', '/');
        if ($script === 'index.php') {
            if (($_SERVER['PATH_INFO'] ?? '') !== '/login') {
                header('Location: /login');
                exit();
            }
            return;
        }
        if (in_array($script, self::PASSWORD_CHANGE_ALLOWED, true)) {
            return;
        }
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Password change required']);
        exit();
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
            // a new session id, so a session id from before the login is not valid
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user;
            // check for the default password again on the next request
            unset($_SESSION['password_change_required']);
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
        if (!$this->isLoginRoute()) {
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
