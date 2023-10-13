<?php

require_once 'includes/config.php';
require_once 'includes/wifi_functions.php';

getWifiInterface();

/**
 * Manage VPN provider configuration
 */
function DisplayProviderConfig()
{
    $status = new \RaspAP\Messages\StatusMessage;
    $providerName = getProviderValue($_SESSION["providerID"], "name");
    $binPath = getProviderValue($_SESSION["providerID"], "bin_path");

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveProviderSettings'])) {
            if (isset($_POST['someVar'])) {
                $someVar = strip_tags(trim($_POST['someVar']));
            }
            $return = SaveProviderConfig($status, $someVar);
        } elseif (isset($_POST['StartProviderVPN'])) {
            $status->addMessage('Attempting to connect provider VPN', 'info');
            exec('sudo '.$binPath.' connect', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopProviderVPN'])) {
            $status->addMessage('Attempting to disconnect provider VPN', 'info');
            exec('sudo '.$binPath.' disconnect', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }

    exec("sudo $binPath status", $result);
    $serviceStatus = strtolower($output[1]) == 0 ? "disconnected" : "connected";
    $public_ip = get_public_ip();

    exec("sudo $binPath status > /tmp/provider.log");
    $providerLog = file_get_contents('/tmp/provider.log');

    echo renderTemplate(
        "provider", compact(
            "status",
            "serviceStatus",
            "providerName",
            "providerLog",
            "public_ip"
        )
    );
}

/**
 * Validates VPN provider settings 
 *
 * @param  object $status
 * @return string $someVar
 */
function SaveProviderConfig($status, $someVar)
{

}
