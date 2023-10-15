<?php

require_once 'includes/config.php';

/*
 * Manage VPN provider configuration
 */
function DisplayProviderConfig()
{
    $status = new \RaspAP\Messages\StatusMessage;
    $providerName = getProviderValue($_SESSION["providerID"], "name");
    $binPath = getProviderValue($_SESSION["providerID"], "bin_path");
    $public_ip = get_public_ip();

    if (!file_exists($binPath)) {
        $installPage = getProviderValue($_SESSION["providerID"], "install_page");
        $status->addMessage('Expected '.$providerName.' binary not found at: '.$binPath, 'warning');
        $status->addMessage('Visit the <a href="'.$installPage.'" target="_blank">installation instructions</a> for '.$providerName.'\'s Linux CLI.', 'warning');
        $ctlState = 'disabled';
        $providerVersion = 'not found';
    } else {
        // fetch provider status
        $output = shell_exec("sudo $binPath status");
        $serviceStatus = strtolower($output) == 0 ? "inactive" : "active";
        $result = strtolower(($lastSpacePos = strrpos($output, ' ')) ? substr($output, $lastSpacePos + 1) : $output);
        $providerLog = stripArtifacts($output);
        //echo '<br>status = '.$result;

        // fetch provider version
        $providerVersion = shell_exec("sudo $binPath -v");

        // fetch account info
        exec("sudo $binPath account", $output);
        $accountInfo = stripArtifacts($output);

        // fetch available countries
        $output = shell_exec("sudo $binPath countries");
        $output = stripArtifacts($output, '\s');
        $arrTmp = explode(",", $output);
        $countries = array_combine($arrTmp, $arrTmp);
        foreach ($countries as $key => $value) {
            $countries[$key] = str_replace("_", " ", $value);
        }
    }

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveProviderSettings'])) {
            if (isset($_POST['someVar'])) {
                $someVar = strip_tags(trim($_POST['someVar']));
            }
            $return = SaveProviderConfig($status, $someVar);
        } elseif (isset($_POST['StartProviderVPN'])) {
            $status->addMessage('Attempting to connect VPN provider', 'info');
            exec("sudo $binPath connect", $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopProviderVPN'])) {
            $status->addMessage('Attempting to disconnect VPN provider', 'info');
            exec("sudo $binPath disconnect", $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }

    echo renderTemplate(
        "provider", compact(
            "status",
            "serviceStatus",
            "providerName",
            "providerVersion",
            "accountInfo",
            "countries",
            "providerLog",
            "public_ip",
            "ctlState"
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

/**
 * Removes artifacts from shell_exec string values
 *
 * @param string $output
 * @param string $pattern
 * @return string $result
 */
function stripArtifacts($output, $pattern = null)
{
    $result = preg_replace('/[-\/\n\t\\\\'.$pattern.'|]/', '', $output);
    return $result;
}

