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
        "terminal"   => "Terminal"
    ];
    $themeFiles = [
        "default"    => "custom.css",
        "hackernews" => "hackernews.css",
        "terminal"   => "terminal.css"
    ];
    $selectedTheme = array_search($_COOKIE['theme'], $themeFiles);

    echo renderTemplate("themes", compact("themes", "selectedTheme"));
}
