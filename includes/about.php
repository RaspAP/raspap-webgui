<?php

require_once "app/lib/Parsedown.php";

/**
 * Displays info about the RaspAP project
 */
function DisplayAbout()
{
    $Parsedown = new Parsedown();
    $strContent = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/BACKERS.md');
    $sponsorsHtml = $Parsedown->text($strContent);

    $strContent = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/CONTRIBUTING.md');
    $contributingHtml = $Parsedown->text($strContent);

    $strContent = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/COOKBOOK.md');
    $cookbookHtml = $Parsedown->text($strContent);

    echo renderTemplate(
        "about", compact(
            'sponsorsHtml',
            'contributingHtml',
            'cookbookHtml'
        )
    );
}

