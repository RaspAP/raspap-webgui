<?php
/**
 *
 *
 */
function DisplayThemeConfig(&$extraFooterScripts)
{
    $themes = [
        "default"    => "RaspAP (default)",
        "hackernews" => "HackerNews"
    ];
    $themeFiles = [
        "default"    => "custom.php",
        "hackernews" => "hackernews.css"
    ];
    $selectedTheme = array_search($_COOKIE['theme'], $themeFiles);

    echo renderTemplate("themes", compact("themes", "selectedTheme"));

    $extraFooterScripts[] = array('src'=>'dist/huebee/huebee.pkgd.min.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'app/js/huebee.js', 'defer'=>false);
}
