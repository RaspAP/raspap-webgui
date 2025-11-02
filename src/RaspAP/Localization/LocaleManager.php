<?php

/**
 * Locale Manager class
 *
 * @description Manages locale/i18n functionality for RaspAP
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Localization;

class LocaleManager
{
    private string $locale;
    private const DEFAULT_LOCALE = 'en_GB.UTF-8';
    private const COOKIE_LIFETIME = 86400 * 30; // 30 days

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->locale = self::DEFAULT_LOCALE;
    }

    /**
     * Get all available locales
     *
     * @return array Associative array of locale codes to language names
     */
    public function getLocales(): array
    {
        return [
            'en_GB.UTF-8' => 'English',
            'cs_CZ.UTF-8' => 'Čeština',
            'zh_TW.UTF-8' => '正體中文 (Chinese traditional)',
            'zh_CN.UTF-8' => '简体中文 (Chinese simplified)',
            'da_DK.UTF-8' => 'Dansk',
            'de_DE.UTF-8' => 'Deutsch',
            'es_MX.UTF-8' => 'Español',
            'fi_FI.UTF-8' => 'Finnish',
            'fr_FR.UTF-8' => 'Français',
            'el_GR.UTF-8' => 'Ελληνικά',
            'id_ID.UTF-8' => 'Indonesian',
            'it_IT.UTF-8' => 'Italiano',
            'ja_JP.UTF-8' => '日本語 (Japanese)',
            'ko_KR.UTF-8' => '한국어 (Korean)',
            'nl_NL.UTF-8' => 'Nederlands',
            'pl_PL.UTF-8' => 'Polskie',
            'pt_BR.UTF-8' => 'Português',
            'ru_RU.UTF-8' => 'Русский',
            'ro_RO.UTF-8' => 'Română',
            'sk_SK.UTF-8' => 'Slovenčina',
            'sv_SE.UTF-8' => 'Svenska',
            'tr_TR.UTF-8' => 'Türkçe',
            'vi_VN.UTF-8' => 'Tiếng Việt (Vietnamese)'
        ];
    }

    /**
     * Get valid locale codes
     *
     * @return array Array of valid locale codes
     */
    public function getValidLocales(): array
    {
        return array_keys($this->getLocales());
    }

    /**
     * Detect browser locale from Accept-Language header
     *
     * Language detection is performed via the browser.
     * Accept-Language returns a list of weighted values with a quality (or 'q') parameter.
     * A better method would parse the list of preferred languages and match this with
     * the languages supported by our platform.
     *
     * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
     * @return string Detected locale code
     */
    public function detectBrowserLocale(): string
    {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) || strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) < 2) {
            return self::DEFAULT_LOCALE;
        }

        $acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $lang = strtolower(substr($acceptLang, 0, 2));

        if ($lang === 'zh' && strpos($acceptLang, 'zh-TW') === 0) {
            return 'zh_TW.UTF-8';
        }

        switch ($lang) {
            case 'de':
                return 'de_DE.UTF-8';
            case 'fr':
                return 'fr_FR.UTF-8';
            case 'it':
                return 'it_IT.UTF-8';
            case 'pt':
                return 'pt_BR.UTF-8';
            case 'sv':
                return 'sv_SE.UTF-8';
            case 'nl':
                return 'nl_NL.UTF-8';
            case 'zh':
                return 'zh_CN.UTF-8';
            case 'cs':
                return 'cs_CZ.UTF-8';
            case 'ru':
                return 'ru_RU.UTF-8';
            case 'es':
                return 'es_MX.UTF-8';
            case 'fi':
                return 'fi_FI.UTF-8';
            case 'da':
                return 'da_DK.UTF-8';
            case 'tr':
                return 'tr_TR.UTF-8';
            case 'id':
                return 'id_ID.UTF-8';
            case 'ko':
                return 'ko_KR.UTF-8';
            case 'ja':
                return 'ja_JP.UTF-8';
            case 'vi':
                return 'vi_VN.UTF-8';
            case 'el':
                return 'el_GR.UTF-8';
            case 'pl':
                return 'pl_PL.UTF-8';
            case 'sk':
                return 'sk_SK.UTF-8';
            default:
                return self::DEFAULT_LOCALE;
        }
    }

    /**
     * Validate if a locale code is valid
     *
     * Uses $validLocales to mitigate OS Command Injection (CWE-78)
     * @see Vulnerability Report for JVN #27202136
     *
     * @param string $locale Locale code to validate
     * @return bool True if valid, false otherwise
     */
    public function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->getValidLocales(), true);
    }

    /**
     * Set the current locale
     *
     * @param string $locale Locale code to set
     * @return bool true if locale was set, false if invalid
     */
    public function setLocale(string $locale): bool
    {
        if (!$this->isValidLocale($locale)) {
            return false;
        }

        $this->locale = $locale;
        return true;
    }

    /**
     * Get the current locale
     *
     * @return string Current locale code
     */
    public function getCurrentLocale(): string
    {
        return $this->locale;
    }

    /**
     * Initialize locale
     *
     * @return void
     */
    public function initialize(): void
    {
        $validLocales = $this->getValidLocales();

        // Set locale from POST, if provided and valid
        if (!empty($_POST['locale']) && in_array($_POST['locale'], $validLocales, true)) {
            $_SESSION['locale'] = $_POST['locale'];
            $this->setLocaleCookie($_POST['locale']);
        }

        // Set locale from cookie or browser detection, if not already set in session
        if (empty($_SESSION['locale'])) {
            if (isset($_COOKIE['locale']) && in_array($_COOKIE['locale'], $validLocales, true)) {
                $_SESSION['locale'] = $_COOKIE['locale'];
            } else {
                $_SESSION['locale'] = $this->detectBrowserLocale();
                $this->setLocaleCookie($_SESSION['locale']);
            }
        }

        // Enforce only valid locale values in session
        if (!in_array($_SESSION['locale'], $validLocales, true)) {
            $_SESSION['locale'] = self::DEFAULT_LOCALE;
            $this->setLocaleCookie($_SESSION['locale']);
        }

        // Update internal state
        $this->locale = $_SESSION['locale'];
    }

    /**
     * Apply locale settings to the system
     *
     * Sets environment variables and configures gettext
     *
     * @return void
     */
    public function applyLocale(): void
    {
        putenv("LANG=" . escapeshellarg($this->locale));
        setlocale(LC_ALL, $this->locale);

        // Use global constants if defined, otherwise use defaults
        $localeDomain = defined('LOCALE_DOMAIN') ? LOCALE_DOMAIN : 'messages';
        $localeRoot = defined('LOCALE_ROOT') ? LOCALE_ROOT : 'locale';

        bindtextdomain($localeDomain, $localeRoot);
        bind_textdomain_codeset($localeDomain, 'UTF-8');
        textdomain($localeDomain);
    }

    /**
     * Set locale cookie
     *
     * @param string $locale Locale code to store in cookie
     * @return void
     */
    private function setLocaleCookie(string $locale): void
    {
        setcookie('locale', $locale, time() + self::COOKIE_LIFETIME, '/', '', false, true);
    }

    /**
     * Initialize and apply locale in one call
     *
     * @return void
     */
    public function initializeAndApply(): void
    {
        $this->initialize();
        $this->applyLocale();
    }
}
