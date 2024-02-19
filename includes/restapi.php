<?php

require_once 'includes/functions.php';
require_once 'config.php';

/**
 * Handler for RaspAP's RestAPI settings
 */
function DisplayRestAPI()
{
    $status = new \RaspAP\Messages\StatusMessage;


    echo renderTemplate("restapi", compact(
        "status",
        "apiKey",
    ));
}
