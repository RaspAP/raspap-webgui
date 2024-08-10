<?php

require_once 'includes/config.php';

/*
 * Display VPN provider configuration
 */
function DisplayProviderConfig()
{
    // initialize status object
    $status = new \RaspAP\Messages\StatusMessage;

    // set defaults
    $id = $_SESSION["providerID"];
    $binPath = getProviderValue($id, "bin_path");
    $providerName = getProviderValue($id, "name");
    $providerVersion = getProviderVersion($id, $binPath);
    $installPage = getProviderValue($id, "install_page");
    $publicIP = get_public_ip();
    $serviceStatus = 'down';
    $statusDisplay = 'down';
    $ctlState = '';

    if (!file_exists($binPath)) {
        $status->addMessage(sprintf(_('Expected %s binary not found at: %s'), $providerName, $binPath), 'warning');
        $status->addMessage(sprintf(_('Visit the <a href="%s" target="_blank">installation instructions</a> for %s\'s Linux CLI.'), $installPage, $providerName), 'warning');
        $ctlState = 'disabled';
        $providerVersion = 'not found';
    } elseif (empty($providerVersion)) {
        $status->addMessage(sprintf(_('Unable to execute %s binary found at: %s'), $providerName, $binPath), 'warning');
        $status->addMessage(_('Check that binary is executable and permissions exist in raspap.sudoers'), 'warning');
        $ctlState = 'disabled';
        $providerVersion = 'not found';
    } else {
        // fetch provider status
        $serviceStatus = getProviderStatus($id, $binPath);
        $statusDisplay = $serviceStatus == "down" ? "inactive" : "active";

        // fetch provider log
        $providerLog = getProviderLog($id, $binPath, $country);

        // fetch account info
        $accountInfo = getAccountInfo($id, $binPath, $providerName);
        $accountLink = getProviderValue($id, "account_page");

        // fetch available countries
        $countries = getCountries($id, $binPath);
    }

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveProviderSettings'])) {
            if (isset($_POST['country'])) {
                $country = escapeshellarg(trim($_POST['country']));
                if (strlen($country) == 0) {
                    $status->addMessage('Select a country from the server location list', 'danger');
                } else {
                    $return = saveProviderConfig($status, $binPath, $country, $id);
                }
            }
        } elseif (isset($_POST['StartProviderVPN'])) {
            $status->addMessage('Attempting to connect VPN provider', 'info');
            $cmd = getCliOverride($id, 'cmd_overrides', 'connect');
            exec("sudo $binPath $cmd", $return);
            $return = stripArtifacts($return);
            foreach ($return as $line) {
                if (strlen(trim($line)) > 0) {
                    $line = preg_replace('/\e\[\?[0-9]*l\s(.*)\e.*$/', '$1', $line);
                    $line = preg_replace('/\e\[0m\e\[[0-9;]*m(.*)/', '$1', $line);
                    $status->addMessage($line, 'info');
                }
            }
        } elseif (isset($_POST['StopProviderVPN'])) {
            $status->addMessage('Attempting to disconnect VPN provider', 'info');
            $cmd = getCliOverride($id, 'cmd_overrides', 'disconnect');
            exec("sudo $binPath $cmd", $return);
            $return = stripArtifacts($return);
            foreach ($return as $line) {
                if (strlen(trim($line)) > 0) {
                    $line = preg_replace('/\[1;33;49m(.*)\[0m/', '$1', $line);
                    $status->addMessage($line, 'info');
                }
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
            "accountLink",
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
 * @param object $status
 * @param string $binPath
 * @param string $country
 * @param integer $id (optional)
 */
function saveProviderConfig($status, $binPath, $country, $id = null)
{
    $status->addMessage(sprintf(_('Attempting to connect to %s'),$country), 'info');
    $cmd = getCliOverride($id, 'cmd_overrides', 'connect');
    // mullvad requires relay set location before connect
    if ($id == 2) {
        exec("sudo $binPath relay set location $country", $return);
        exec("sudo $binPath $cmd", $return);
    } else {
        exec("sudo $binPath $cmd $country", $return);
    }
    $return = stripArtifacts($return);
    foreach ($return as $line) {
        if ( strlen(trim($line)) >0 ) {
            $status->addMessage($line, 'info');
        }
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
        if (empty($obj['providers'][$id][$group][$item])) {
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
    $pattern = getCliOverride($id, 'regex', 'status');
    exec("sudo $binPath $cmd", $cmd_raw);
    $cmd_raw = strtolower(stripArtifacts($cmd_raw[0]));

    if (!empty($cmd_raw[0])) {
        if (preg_match($pattern, $cmd_raw, $match)) {
            $status =  "down";
        } else {
            $status = "up";
        }
    } else {
        $status = "down";
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
    $countries = [];
    $cmd = getCliOverride($id, 'cmd_overrides', 'countries');
    $pattern = getCliOverride($id, 'regex', 'pattern');
    $replace = getCliOverride($id, 'regex', 'replace');
    exec("sudo $binPath $cmd", $output);

    // CLI country output differs considerably between different providers.
    // Ideally, custom parsing would be avoided in favor of a pure regex solution
    switch ($id) {
    case 1: // expressvpn
        $slice = getCliOverride($id, 'regex', 'slice');
        $output = array_slice($output, $slice);
        foreach ($output as $item) {
            $item = preg_replace($pattern, $replace, $item);
            $parts = explode(',', $item);
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $countries[$key] = $value;
        }
        break;
    case 2: // mullvad
        foreach ($output as $item) {
            $item = preg_replace($pattern, $replace, $item);
            if (strlen(trim($item) >0)) {
                preg_match('/\s+([a-z0-9-]+)\s.*$/', $item, $match);
                if (count($match) > 1) {
                    $key = $match[1];
                    $item = str_pad($item, strlen($item)+16,' ', STR_PAD_LEFT);
                    $countries[$key] = $item;
                } else {
                    preg_match('/\(([a-z]+)\)/', $item, $match);
                    $key = $match[1];
                    if (strlen($match[1]) == 3) {
                        $item = str_pad($item, strlen($item)+8,' ', STR_PAD_LEFT);
                    }
                    $countries[$key] = $item;
                }
            }
        }
        break;
    case 3: // nordvpn
        $arrTmp = explode(",", $output[0]);
        foreach ($output as $key => $value) {
            $countries[$value] = str_replace("_", " ", $value);
        }
        break;
    default:
        break;
    }
    $select = array(' ' => _("Select a country..."));
    $countries = $select + $countries;
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
    $providerLog = '';
    $cmd = getCliOverride($id, 'cmd_overrides', 'log');
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

/**
 * Retrieves provider version information
 *
 * @param integer $id
 * @param string $binPath
 * @return string $version
 */
function getProviderVersion($id, $binPath)
{
    $cmd = getCliOverride($id, 'cmd_overrides', 'version');
    $version = shell_exec("sudo $binPath $cmd");
    $version = preg_replace('/^[^\w]+\s*/', '', $version);
    return $version;
}

/**
 * Retrieves provider account info
 *
 * @param integer $id
 * @param string $binPath
 * @param string $providerName
 * @return array
 */
function getAccountInfo($id, $binPath, $providerName)
{
    $cmd = getCliOverride($id, 'cmd_overrides', 'account');
    exec("sudo $binPath $cmd", $acct);

    foreach ($acct as &$item) {
        $item = preg_replace('/^[^\w]+\s*/', '', $item);
    }
    if (empty($acct)) {
        $msg = sprintf(_("Account information not available from %s's Linux CLI."), $providerName);
        $acct[] = $msg;
    }
    return $acct;
}

