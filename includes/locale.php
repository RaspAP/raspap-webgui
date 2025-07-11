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
            return 'en_GB.UTF-8';
    }
}

