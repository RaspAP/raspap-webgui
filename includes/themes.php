<?php
/**
 *
 *
 */
function DisplayThemeConfig()
{
    $themes = [
        "default"    => "RaspAP (default)",
        "hackernews" => "HackerNews",
        "lightsout"  => "Lights Out"
    ];
    $themeFiles = [
        "default"    => "custom.css",
        "hackernews" => "hackernews.css",
        "lightsout"  => "lightsout.css"
    ];
    $selectedTheme = array_search($_COOKIE['theme'], $themeFiles);

    echo renderTemplate("themes", compact("themes", "selectedTheme"));
}
