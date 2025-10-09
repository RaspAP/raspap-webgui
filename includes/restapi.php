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
                    $status->addMessage('Restarting restapi.service', 'info');
                    exec('sudo /bin/systemctl stop restapi.service', $return);
                    sleep(1);
                    exec('sudo /bin/systemctl start restapi.service', $return);
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
    array_shift($output);
    $serviceLog = implode("\n", $output);

    if ($serviceStatus == "up") {
        $docUrl = getDocUrl();
        $faicon = "<i class=\"text-gray-500 fas fa-external-link-alt ml-1\"></i>"; 
        $docMsg = sprintf(_("RestAPI docs are accessible <a href=\"%s\" target=\"_blank\">here %s</a>"),$docUrl, $faicon);
    }

    echo renderTemplate("restapi", compact(
        "status",
        "apiKey",
        "serviceStatus",
        "serviceLog",
        "docMsg"
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

// Returns a url for fastapi's automatic docs
function getDocUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $server_name = $_SERVER['SERVER_NAME'];
    $port = 8081;
    $url = $protocol . $server_name .':'. $port . '/docs';
    return $url;
}

