<?php

require_once 'includes/functions.php';

/**
 *
 *
 */
function DisplayThemeConfig(&$extraFooterScripts)
{
    $themes = availableThemes();
    $validTheme = in_array($_COOKIE['theme'], array_keys($themes));
    $selectedTheme = $validTheme ? $_COOKIE['theme'] : null;

    echo renderTemplate("themes", compact("themes", "selectedTheme"));

    $extraFooterScripts[] = array('src'=>'dist/huebee/huebee.pkgd.min.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'app/js/huebee.js', 'defer'=>false);
}
