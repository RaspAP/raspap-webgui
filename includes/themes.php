<?php
/**
*
*
*/
function DisplayThemeConfig()
{

    $cselected = '';
    $hselected = '';
    $tselected = '';

    switch ($_COOKIE['theme']) {
        case "custom.css":
            $cselected = ' selected="selected"';
            break;
        case "hackernews.css":
            $hselected = ' selected="selected"';
            break;
        case "terminal.css":
            $tselected = ' selected="selected"';
            break;
    }

    echo renderTemplate("themes", compact("cselected", "hselected", "tselected"));
}
