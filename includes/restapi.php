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

    // create instance of DotEnv
    $dotenv = new \RaspAP\DotEnv\DotEnv;
    $dotenv->load();

    // set defaults
    $apiKey = $_ENV['RASPAP_API_KEY'];

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveAPIsettings'])) {
            if (isset($_POST['txtapikey'])) {
                $apiKey = trim($_POST['txtapikey']);
                if (strlen($apiKey) == 0) {
                    $status->addMessage('Please enter a valid API key', 'danger');
                } else {
                    $return = saveAPISettings($status, $apiKey, $dotenv);
                }
            }
        } elseif (isset($_POST['StartRestAPIservice'])) {
            $status->addMessage('Attempting to start restapi.service', 'info');
            exec('sudo /bin/systemctl start restapi.service', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopRestAPIservice'])) {
            $status->addMessage('Attempting to stop restapi.service', 'info');
            exec('sudo /bin/systemctl stop restapi.service', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }
    exec("ps aux | grep -v grep | grep uvicorn", $output, $return);
    $serviceStatus = !empty($output) ? "up" : "down";

    exec("sudo systemctl status restapi.service", $output, $return);
    $serviceLog = implode("\n", $output);

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
 * @param object dotenv
 * @param string $apiKey
 */
function saveAPISettings($status, $apiKey, $dotenv)
{
    $status->addMessage('Saving API key', 'info');
    $dotenv->set('RASPAP_API_KEY', $apiKey);

    return $status;
}

