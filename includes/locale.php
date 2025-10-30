<?php
/**
 * Sets locale information for i18n support, with validation
 *
 * @see RaspAP\Localization\LocaleManager
 */

use RaspAP\Localization\LocaleManager;

// Initialize locale manager
$localeManager = new LocaleManager();
$localeManager->initializeAndApply();

/**
 * Get all available locales
 *
 * @return array Associative array of locale codes to language names
 */
function getLocales(): array
{
    static $localeManager = null;
    if ($localeManager === null) {
        $localeManager = new LocaleManager();
    }
    return $localeManager->getLocales();
}

/**
 * Detect browser locale from Accept-Language header
 *
 * @return string Detected locale code
 */
function detectBrowserLocale(): string
{
    static $localeManager = null;
    if ($localeManager === null) {
        $localeManager = new LocaleManager();
    }
    return $localeManager->detectBrowserLocale();
}

