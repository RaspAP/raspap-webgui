<?php
/**
 * Sets locale information for i18n support, with secure input validation.
 *
 * Rudimentary language detection is performed via the browser.
 * Accept-Language returns a list of weighted values with a quality (or 'q') parameter.
 * A better method would parse the list of preferred languages and match this with
 * the languages supported by our platform.
 * @see https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
 *
 * Uses $validLocales to mitigate OS Command Injection (CWE-78)
 * @see Vulnerability Report for JVN #27202136
 */

// Set locale from POST, if provided and valid
$validLocales = array_keys(getLocales());
if (!empty($_POST['locale']) && in_array($_POST['locale'], $validLocales, true)) {
    $_SESSION['locale'] = $_POST['locale'];
}

// Set locale from browser detection, if not already set
if (empty($_SESSION['locale'])) {
    $_SESSION['locale'] = detectBrowserLocale();
}

// Enforce only valid locale values in session
if (!in_array($_SESSION['locale'], $validLocales, true)) {
    $_SESSION['locale'] = 'en_GB.UTF-8';
}

// Apply locale settings
putenv("LANG=" . escapeshellarg($_SESSION['locale']));
setlocale(LC_ALL, $_SESSION['locale']);
bindtextdomain(LOCALE_DOMAIN, LOCALE_ROOT);
bind_textdomain_codeset(LOCALE_DOMAIN, 'UTF-8');
textdomain(LOCALE_DOMAIN);

function getLocales(): array
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

function detectBrowserLocale(): string
{
    if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) || strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) < 2) {
        return 'en_GB.UTF-8';
    }

    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    return match ($lang) {
        'de' => 'de_DE.UTF-8',
        'fr' => 'fr_FR.UTF-8',
        'it' => 'it_IT.UTF-8',
        'pt' => 'pt_BR.UTF-8',
        'sv' => 'sv_SE.UTF-8',
        'nl' => 'nl_NL.UTF-8',
        'zh' => ($_SERVER['HTTP_ACCEPT_LANGUAGE'] === 'zh_TW') ? 'zh_TW.UTF-8' : 'zh_CN.UTF-8',
        'cs' => 'cs_CZ.UTF-8',
        'ru' => 'ru_RU.UTF-8',
        'es' => 'es_MX.UTF-8',
        'fi' => 'fi_FI.UTF-8',
        'da' => 'da_DK.UTF-8',
        'tr' => 'tr_TR.UTF-8',
        'id' => 'id_ID.UTF-8',
        'ko' => 'ko_KR.UTF-8',
        'ja' => 'ja_JP.UTF-8',
        'vi' => 'vi_VN.UTF-8',
        'el' => 'el_GR.UTF-8',
        'pl' => 'pl_PL.UTF-8',
        'sk' => 'sk_SK.UTF-8',
        default => 'en_GB.UTF-8',
    };
}

