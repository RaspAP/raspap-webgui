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
        $providerLog = getProviderLog($id, $binPath, $country);

        // fetch provider version
        $providerVersion = shell_exec("sudo $binPath -v");

        // fetch account info
        $cmd = getCliOverride($id, 'cmd_overrides', 'account');
        exec("sudo $binPath $cmd", $acct);
        $accountInfo = stripArtifacts($acct);

        // fetch available countries
        $countries = getCountries($id, $binPath);
    }

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveProviderSettings'])) {
            if (isset($_POST['country'])) {
                $country = trim($_POST['country']);
                if (strlen($country) == 0) {
                    $status->addMessage('Select a country from the server location list', 'danger');
                }
                $return = saveProviderConfig($status, $binPath, $country);
            }
        } elseif (isset($_POST['StartProviderVPN'])) {
            $status->addMessage('Attempting to connect VPN provider', 'info');
            $cmd = getCliOverride($id, 'cmd_overrides', 'connect');
            exec("sudo $binPath $cmd", $return);
            sleep(3); // required for connect delay
            $return = stripArtifacts($return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopProviderVPN'])) {
            $status->addMessage('Attempting to disconnect VPN provider', 'info');
            $cmd = getCliOverride($id, 'cmd_overrides', 'disconnect');
            exec("sudo $binPath $cmd", $return);
            $return = stripArtifacts($return);
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
            "country",
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
function saveProviderConfig($status, $binPath, $country)
{
    $status->addMessage('Attempting to connect to '.$country, 'info');
    $cmd = getCliOverride($id, 'cmd_overrides', 'connect');
    exec("sudo $binPath $cmd $country", $return);
    sleep(3); // required for connect delay
    $return = stripArtifacts($return);
    foreach ($return as $line) {
        $status->addMessage($line, 'info');
    }
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
    $cmd = getCliOverride($id, 'cmd_overrides', 'status');
    exec("sudo $binPath $cmd", $cmd_raw);
    $cmd_raw = stripArtifacts($cmd_raw[0]);
    if (preg_match('/Status: (\w+)/', $cmd_raw, $match)) {
        $cli = getCliOverride($id, 'status', 'connected');
        $status = strtolower($match[1]) == $cli ? "up" : "down";
    }
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
    $select = array(' ' => 'Select a country...');
    $countries = $select + $countries;
    foreach ($countries as $key => $value) {
        $countries[$key] = str_replace("_", " ", $value);
    }
    return $countries;
}

/**
 * Retrieves provider log
 *
 * @param integer $id
 * @param string $binPath
 * @param string $country
 * @return string $log
 */
function getProviderLog($id, $binPath, &$country)
{
    $cmd = getCliOverride($id, 'cmd_overrides', 'status');
    exec("sudo $binPath $cmd", $cmd_raw);
    $output = stripArtifacts($cmd_raw);
    foreach ($output as $item) {
        if (preg_match('/Country: (\w+)/', $item, $match)) {
            $country = $match[1];
        }
        $providerLog.= ltrim($item) .PHP_EOL;
    }
    return $providerLog;
}

