<?php
/**
 * Sets locale information for i18n support
 */

/**
 * Rudimentary language detection via the browser.
 * Accept-Language returns a list of weighted values with a quality (or 'q') parameter.
 * A better method would parse the list of preferred languages and match this with
 * the languages supported by our platform.
 *
 * Refer to: https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
 */
if (empty($_SESSION['locale']) && strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) >= 2) {
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    switch ($lang) {
    case "de":
        $locale = "de_DE.UTF-8";
        break;
    case "fr":
        $locale = "fr_FR.UTF-8";
        break;
    case "it":
        $locale = "it_IT.UTF-8";
        break;
    case "pt":
        $locale = "pt_BR.UTF-8";
        break;
    case "sv":
        $locale = "sv_SE.UTF-8";
        break;
    case "nl":
        $locale = "nl_NL.UTF-8";
        break;
    case "zh":
        if ($_SERVER['HTTP_ACCEPT_LANGUAGE'] == 'zh_TW') {
            $locale = "zh_TW.UTF-8";
        } else {
            $locale = "zh_CN.UTF-8";
        }
        break;
    case "cs":
        $locale = "cs_CZ.UTF-8";
        break;
    case "ru":
        $locale = "ru_RU.UTF-8";
        break;
    case "es":
        $locale = "es_MX.UTF-8";
        break;
    case "fi":
        $locale = "fi_FI.UTF-8";
        break;
    case "da":
        $locale = "da_DK.UTF-8";
        break;
    case "tr":
        $locale = "tr_TR.UTF-8";
        break;
    case "id":
        $locale = "id_ID.UTF-8";
        break;
    case "ko":
        $locale = "ko_KR.UTF-8";
        break;
    case "ja":
        $locale = "ja_JP.UTF-8";
        break;
    case "vi":
        $locale = "vi_VN.UTF-8";
        break;
    case "el":
        $locale = "el_GR.UTF-8";
        break;
    case "pl":
        $locale = "pl_PL.UTF-8";
        break;
    case "sk":
        $locale = "sk_SK.UTF-8";
        break;
    default:
        $locale = "en_GB.UTF-8";
        break;
    }

    $_SESSION['locale'] = $locale;
}

// Note: the associated locale must be installed on the RPi
// Use: 'sudo raspi-configure' and select 'Localisation Options'

// activate the locale setting
putenv("LANG=" . $_SESSION['locale']);
setlocale(LC_ALL, $_SESSION['locale']);

bindtextdomain(LOCALE_DOMAIN, LOCALE_ROOT);
bind_textdomain_codeset(LOCALE_DOMAIN, 'UTF-8');

textdomain(LOCALE_DOMAIN);

function getLocales()
{
    $arrLocales = array(
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
    );
    return $arrLocales;
}
