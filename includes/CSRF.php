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

