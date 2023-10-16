<?php

require_once 'includes/config.php';

/*
 * Manage VPN provider configuration
 */
function DisplayProviderConfig()
{
    $status = new \RaspAP\Messages\StatusMessage;

    $id = $_SESSION["providerID"];
    $providerName = getProviderValue($id, "name");
    $binPath = getProviderValue($id, "bin_path");
    $publicIP = get_public_ip();

    if (!file_exists($binPath)) {
        $installPage = getProviderValue($id, "install_page");
        $status->addMessage('Expected '.$providerName.' binary not found at: '.$binPath, 'warning');
        $status->addMessage('Visit the <a href="'.$installPage.'" target="_blank">installation instructions</a> for '.$providerName.'\'s Linux CLI.', 'warning');
        $ctlState = 'disabled';
        $providerVersion = 'not found';
    } else {
        // fetch provider status
        $serviceStatus = getProviderStatus($id, $binPath);
        $statusDisplay = $serviceStatus == "down" ? "inactive" : "active";

        // fetch provider log
        $cmd = getCliOverride($id, 'cmd_overrides', 'status');
        $output = shell_exec("sudo $binPath $cmd");
        $providerLog = stripArtifacts($output);

        // fetch provider version
        $providerVersion = shell_exec("sudo $binPath -v");

        // fetch account info
        $cmd = getCliOverride($id, 'cmd_overrides', 'account');
        exec("sudo $binPath $cmd", $output);
        $accountInfo = stripArtifacts($output);

        // fetch available countries
        $countries = getCountries($id, $binPath);
    }

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveProviderSettings'])) {
            if (isset($_POST['someVar'])) {
                $someVar = strip_tags(trim($_POST['someVar']));
            }
            $return = SaveProviderConfig($status, $someVar);
        } elseif (isset($_POST['StartProviderVPN'])) {
            $status->addMessage('Attempting to connect VPN provider', 'info');
            $cmd = getCliOverride($id, 'cmd_overrides', 'connect');
            exec("sudo $binPath $cmd", $return);
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
            "statusDisplay",
            "providerName",
            "providerVersion",
            "accountInfo",
            "countries",
            "providerLog",
            "publicIP",
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

/**
 * Retrieves an override for provider CLI
 *
 * @param integer $id
 * @param string $group
 * @param string $item
 * @return string $override
 */
function getCliOverride($id, $group, $item)
{
    $obj = json_decode(file_get_contents(RASPI_CONFIG_PROVIDERS), true);
    if ($obj === null) {
        return false;
    } else {
        $id--;
        if ($obj['providers'][$id][$group][$item] === null) {
            return $item;
        } else {
            return $obj['providers'][$id][$group][$item];
        }
    }
}

/**
 * Retreives VPN provider status
 *
 * @param integer $id
 * @param string $binPath
 * @return string $status
 */
function getProviderStatus($id, $binPath)
{
        $output = shell_exec("sudo $binPath status");
        $output = strtolower(($lastSpace = strrpos($output, ' ')) ? substr($output, $lastSpace + 1) : $output);
        $return = getCliOverride($id, 'status', 'connected');
        $status = strtolower($return) == 'connected' ? "up" : "down";
        return $status;
}

/**
 * Retrieves available countries
 *
 * @param integer $id
 * @param string $binPath
 * @return array $countries
 */
function getCountries($id, $binPath)
{
    $cmd = getCliOverride($id, 'cmd_overrides', 'countries');
    $output = shell_exec("sudo $binPath $cmd");
    $output = stripArtifacts($output, '\s');
    $arrTmp = explode(",", $output);
    $countries = array_combine($arrTmp, $arrTmp);
    foreach ($countries as $key => $value) {
        $countries[$key] = str_replace("_", " ", $value);
    }
    return $countries;
}

