<?php

namespace RaspAP\Tokens;

class CSRF
{
    protected static ?CSRFTokenizer $instance = null;

    protected static function getInstance(): CSRFTokenizer
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
        return self::instance()->csrfValidateRequest() && self::instance()->CSRFValidate($_POST['csrf_token'] ?? '');
    }

    public static function metaTag(): string
    {
        return self::getInstance()->CSRFMetaTag();
    }

    public static function hiddenField(): string
    {
        return self::getInstance()->CSRFTokenFieldTag();
    }

    public static function handleInvalidToken(): void
    {
        self::instance()->handleInvalidCSRFToken();
    }
}

