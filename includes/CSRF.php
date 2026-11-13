<?php

namespace RaspAP\Tokens;

class CSRF
{
    protected static ?CSRFTokenizer $instance = null;

    /*
     * Get the CSRFTokenizer instance (singleton)
     *
     * @return CSRFTokenizer
     */
    public static function instance(): CSRFTokenizer
    {
        if (self::$instance === null) {
            self::$instance = new CSRFTokenizer();
        }
        return self::$instance;
    }

    public static function token(): string
    {
        return self::instance()->getToken();
    }

    public static function verify(): bool
    {
        if (!isset($_POST['csrf_token'])) {
            return false;
        }
        return self::instance()->csrfValidateRequest() &&
           self::instance()->CSRFValidate($_POST['csrf_token']);
    }

    public static function metaTag(): string
    {
        return self::instance()->CSRFMetaTag();
    }

    public static function hiddenField(): string
    {
        return self::instance()->CSRFTokenFieldTag();
    }

    public static function handleInvalidToken(): void
    {
        self::instance()->handleInvalidCSRFToken();
    }

    /**
     * Requires the request to use POST, so a state-changing endpoint is
     * covered by the CSRF token check (validateRequest() only validates the
     * unsafe methods). A GET or other method is refused with 405, which
     * stops a cross-site request from triggering the action with no token.
     *
     * @return void
     */
    public static function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }
    }

    /**
     * Validates a CSRF Request
     *
     * @return bool
     */
    public static function validateRequest(): bool
    {
        $methods = ['POST', 'PUT', 'DELETE', 'PATCH'];
        return in_array($_SERVER['REQUEST_METHOD'], $methods) &&
           self::instance()->csrfValidateRequest();
    }
}

if (\RaspAP\Tokens\CSRF::validateRequest()) {
    if (!\RaspAP\Tokens\CSRF::verify()) {
        error_log("CSRF verification failed: Token missing or invalid");
        \RaspAP\Tokens\CSRF::handleInvalidToken();
    }
}

