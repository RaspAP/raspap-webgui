<?php

require_once 'includes/functions.php';
require_once 'config.php';

/**
 * Handler for RestAPI settings
 */
function DisplayRestAPI()
{
    // initialize status object
    $status = new \RaspAP\Messages\StatusMessage;

    // set defaults
    $apiKey = "Hx80npaPTol9fKeBnPwX7ib2"; //placeholder

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveAPIsettings'])) {
            if (isset($_POST['txtapikey'])) {
                $apiKey = trim($_POST['txtapikey']);
                if (strlen($apiKey) == 0) {
                    $status->addMessage('Please enter a valid API key', 'danger');
                } else {
                    $return = saveAPISettings($status, $apiKey);
                }
            }
        } elseif (isset($_POST['StartRestAPIservice'])) {
            $status->addMessage('Attempting to start raspap-restapi.service', 'info');
            exec('sudo /bin/systemctl start raspap-restapi', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopRestAPIservice'])) {
            $status->addMessage('Attempting to stop raspap-restapi.service', 'info');
            exec('sudo /bin/systemctl stop raspap-restapi.service', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }
    exec('pidof uvicorn | wc -l', $uvicorn);
    $serviceStatus = $uvicorn[0] == 0 ? "down" : "up";

    echo renderTemplate("restapi", compact(
        "status",
        "apiKey",
        "serviceStatus",
        "serviceLog"
    ));
}

/**
 * Saves RestAPI settings
 *
 * @param object status
 * @param string $apiKey
 */
function saveAPISettings($status, $apiKey)
{
    $status->addMessage('Saving API key', 'info');
    // TODO: update API key. location defined from constant

    return $status;
}
