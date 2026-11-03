<?php

require_once 'includes/config.php';

use RaspAP\Networking\Hotspot\WiFiManager;
use RaspAP\Networking\VpnConflict;
use RaspAP\Networking\VpnFile;
$wifi = new WiFiManager();
$wifi->getWifiInterface();

/**
 * Manage OpenVPN configuration
 */
function DisplayOpenVPNConfig()
{
    $status = new \RaspAP\Messages\StatusMessage;
    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveOpenVPNSettings'])) {
            if (isset($_POST['authUser'])) {
                $authUser = strip_tags(trim($_POST['authUser']));
            }
            if (isset($_POST['authPassword'])) {
                $authPassword = strip_tags(trim($_POST['authPassword']));
            }
            $keyPassword = '';
            if (isset($_POST['keyPassword'])) {
                $keyPassword = trim($_POST['keyPassword']);
            }
            if (is_uploaded_file( $_FILES["customFile"]["tmp_name"])) {
                $return = SaveOpenVPNConfig($status, $_FILES['customFile'], $authUser, $authPassword, $keyPassword);
            }
        } elseif (isset($_POST['StartOpenVPN'])) {
            $status->addMessage('Attempting to start OpenVPN', 'info');
            exec('sudo /bin/systemctl start openvpn-client@client', $return);
            exec('sudo /bin/systemctl enable openvpn-client@client', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        } elseif (isset($_POST['StopOpenVPN'])) {
            $status->addMessage('Attempting to stop OpenVPN', 'info');
            exec('sudo /bin/systemctl stop openvpn-client@client', $return);
            exec('sudo /bin/systemctl disable openvpn-client@client', $return);
            foreach ($return as $line) {
                $status->addMessage($line, 'info');
            }
        }
    }

    exec('pidof openvpn', $openvpnstatus, $ovpn_return);
    $serviceStatus = ($ovpn_return === 0) ? "up" : "down";
    $auth = VpnFile::readLines('openvpn', 'login');
    $authUser = '';
    $authPassword = '';
    // parse client auth credentials
    if (!empty($auth)) {
        $auth = array_filter($auth, 'filter_comments');
        $authUser = current($auth);
        $authPassword = next($auth);
    }
    $clients = preg_grep('/_client.(conf)$/', scandir(pathinfo(RASPI_OPENVPN_CLIENT_CONFIG, PATHINFO_DIRNAME)));
    // a config name can contain spaces, so no "xargs basename"
    $active = basename((string)@readlink(RASPI_OPENVPN_CLIENT_CONFIG));
    $conf_default = $active === '' ? "none" : $active;

    $logEnable = 0;
    $logOutput = '';
    if (!empty($_POST) && !isset($_POST['log-openvpn'])) {
        $logOutput = "";
        $f = @fopen("/tmp/openvpn.log", "r+");
        if ($f !== false) {
            ftruncate($f, 0);
            fclose($f);
        }
    } elseif (isset($_POST['log-openvpn']) || (file_exists('/tmp/openvpn.log') && filesize('/tmp/openvpn.log') > 0)) {
        $logEnable = 1;
        exec("sudo /etc/raspap/openvpn/openvpnlog.sh", $logOutput);
        $logOutput = file_get_contents('/tmp/openvpn.log');
    }

    $vpnWarning = (new VpnConflict())->getWarning();
    if ($vpnWarning !== null) {
        $status->addMessage($vpnWarning, 'warning');
    }

    echo renderTemplate(
        "openvpn", compact(
            "status",
            "serviceStatus",
            "openvpnstatus",
            "logEnable",
            "logOutput",
            "authUser",
            "authPassword",
            "clients",
            "conf_default"
        )
    );
}

/**
 * Splits a line of an OpenVPN configuration into its tokens the way
 * OpenVPN does: whitespace separates tokens, a token in double quotes may
 * contain backslash escapes, one in single quotes is literal, and "#" or
 * ";" at the start of a token begins a comment
 *
 * @param  string $line
 * @return array
 */
function tokenizeOpenVPNLine($line)
{
    $tokens = [];
    $token = null;
    $quote = null;
    $len = strlen($line);
    for ($i = 0; $i < $len; $i++) {
        $c = $line[$i];
        if ($quote === null && $c === '\\' && $i + 1 < $len) {
            // between tokens, an escaped space is still whitespace
            if ($token !== null || strpos(" \t\r\n\v\f", $line[$i + 1]) === false) {
                $token .= $line[$i + 1];
            }
            $i++;
        } elseif ($quote === '"' && $c === '\\' && $i + 1 < $len) {
            $token .= $line[++$i];
        } elseif ($quote !== null) {
            if ($c === $quote) {
                $quote = null;
            } else {
                $token .= $c;
            }
        } elseif ($c === '"' || $c === "'") {
            $quote = $c;
            $token .= '';
        } elseif (strpos(" \t\r\n\v\f", $c) !== false) {
            if ($token !== null) {
                $tokens[] = $token;
                $token = null;
            }
        } elseif ($token === null && ($c === '#' || $c === ';')) {
            break;
        } else {
            $token .= $c;
        }
    }
    if ($token !== null) {
        $tokens[] = $token;
    }
    return $tokens;
}

/**
 * Removes the options of an OpenVPN configuration that run programs, load
 * code, write files as root or send local files to a server. OpenVPN of the
 * client runs as root, so an uploaded configuration must not bring them.
 *
 * @param  string $contents
 * @param  array  $removed receives the names of the removed options
 * @return string
 */
function sanitizeOpenVPNConfig($contents, &$removed = [])
{
    $denied = [
        // programs
        'up', 'down', 'route-up', 'route-pre-down', 'ipchange', 'tls-verify',
        'auth-user-pass-verify', 'client-connect', 'client-disconnect',
        'client-crresponse', 'learn-address', 'iproute', 'script-security',
        // code
        'plugin', 'engine', 'providers', 'pkcs11-providers',
        // files written as root, or another configuration file
        'config', 'cd', 'chroot', 'tmp-dir', 'log', 'log-append', 'status',
        'writepid', 'replay-persist', 'tls-export-cert', 'ifconfig-pool-persist',
        'management', 'management-client-user', 'management-client-group'
    ];
    $removed = [];
    $lines = explode("\n", $contents);
    foreach ($lines as $i => $line) {
        // compare without case, the leading "--" and whitespace: a backslash
        // before a space does not start a token for OpenVPN
        $tokens = array_map(function ($token) {
            $ws = " \t\r\n\v\f";
            return strtolower(trim(preg_replace('/^--/', '', trim($token, $ws)), $ws));
        }, tokenizeOpenVPNLine($line));
        // "setenv opt" makes OpenVPN parse the rest as an option
        while (count($tokens) > 2 && $tokens[0] === 'setenv' && $tokens[1] === 'opt') {
            $tokens = array_slice($tokens, 2);
        }
        if (count($tokens) === 0) {
            continue;
        }
        $name = $tokens[0];
        $args = count($tokens) - 1;
        $drop = in_array($name, $denied, true)
            || strpos($name, 'management-') === 0
            // these send the file they name to the server or proxy
            || ($name === 'auth-user-pass' && $args > 0)
            || ($name === 'http-proxy' && $args > 2 && !in_array($tokens[3], ['auto', 'auto-nct'], true))
            || ($name === 'socks-proxy' && $args > 2);
        if ($drop) {
            $removed[] = preg_replace('/[^a-z0-9-]/', '', substr($name, 0, 32));
            unset($lines[$i]);
        }
    }
    return implode("\n", $lines);
}

/**
 * Sets an option that names a file in an OpenVPN configuration: removes the
 * lines of the option and adds "<option> <file>" at the end
 *
 * @param  string $contents
 * @param  string $option auth-user-pass or askpass
 * @param  string $file
 * @return string
 */
function setOpenVPNFileOption($contents, $option, $file)
{
    $contents = preg_replace('/^[ \t]*'.preg_quote($option, '/').'([ \t].*)?\r?$\n?/m', '', $contents);
    return rtrim($contents, "\r\n")."\n".$option.' '.$file."\n";
}

/**
 * Validates uploaded .ovpn file, adds auth-user-pass and
 * stores auth credentials in login.conf. Optionally adds askpass
 * and stores the private key password in keypass.conf.
 * Installs the files with the root helper raspap-vpn-file.sh
 *
 * @param  object $status
 * @param  object $file
 * @param  string $authUser
 * @param  string $authPassword
 * @param  string $keyPassword
 * @return object $status
 */
function SaveOpenVPNConfig($status, $file, $authUser, $authPassword, $keyPassword = '')
{
    define('KB', 1024);

    try {
        // If undefined or multiple files, treat as invalid
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters');
        }

        // OpenVPN reads only the first line of the askpass file
        if (preg_match('/[\r\n]/', $keyPassword)) {
            throw new RuntimeException('Private key password must not contain line breaks');
        }

        // the upload stays in the private directory of the web server user
        $tmp_destdir = get_private_cache_dir();
        if ($tmp_destdir === null) {
            throw new RuntimeException('Unable to save OpenVPN client config');
        }
        $upload = \RaspAP\Uploader\FileUpload::factory('ovpn',$tmp_destdir.'/');
        $upload->set_max_file_size(64*KB);
        $upload->set_allowed_mime_types(array('ovpn' => 'text/plain'));
        $upload->file($file);

        $validation = new validation;
        $upload->callbacks($validation, array('check_name_length'));
        $results = $upload->upload();

        if (!empty($results['errors'])) {
            throw new RuntimeException($results['errors'][0]);
        }

        $tmp_ovpn = $results['full_path'];
        $tmp_contents = file_get_contents($tmp_ovpn);
        unlink($tmp_ovpn);
        if ($tmp_contents === false || strpos($tmp_contents, "\0") !== false) {
            throw new RuntimeException('Invalid OpenVPN configuration');
        }
        $tmp_contents = sanitizeOpenVPNConfig($tmp_contents, $removed);
        if (count($removed) > 0) {
            $status->addMessage('Removed from OpenVPN configuration: '.implode(', ', array_unique($removed)), 'warning');
        }
        // the name of the files in /etc/openvpn/client, as raspap-vpn-file.sh allows it
        $name = preg_replace('/[^A-Za-z0-9 ._()-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $name = substr(ltrim($name, ' .-'), 0, 100);
        if ($name === '') {
            throw new RuntimeException('Invalid OpenVPN configuration name');
        }

        // Good file upload, update auth credentials if present
        if (!empty($authUser) && !empty($authPassword)) {
            $tmp_contents = setOpenVPNFileOption($tmp_contents, 'auth-user-pass', 'login.conf');
            $auth = $authUser .PHP_EOL . $authPassword .PHP_EOL;
            if (VpnFile::run(['openvpn', 'install', $name, 'login'], $auth) === null) {
                $status->addMessage('Unable to save client auth credentials', 'danger');
            }
        }

        // Store private key password if present
        if ($keyPassword !== '') {
            $tmp_contents = setOpenVPNFileOption($tmp_contents, 'askpass', 'keypass.conf');
            if (VpnFile::run(['openvpn', 'install', $name, 'keypass'], $keyPassword .PHP_EOL) === null) {
                $status->addMessage('Unable to save private key password', 'danger');
            }
        }

        // Set iptables rules
        $cmd = sprintf(
            'sudo /etc/raspap/openvpn/configauth.sh %s',
            escapeshellarg($_SESSION['ap_interface'] ?? '')
        );
        exec($cmd, $return);
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }

        // Install the ovpn config and link client.conf to it. The helper
        // checks the options again, because it runs as root.
        if (VpnFile::run(['openvpn', 'install', $name, 'client'], $tmp_contents) !== null) {
            $status->addMessage('OpenVPN client.conf uploaded successfully', 'info');
        } else {
            $status->addMessage('Unable to save OpenVPN client config: '.VpnFile::$lastError, 'danger');
        }

        return $status;
    } catch (RuntimeException $e) {
        $status->addMessage($e->getMessage(), 'danger');
        return $status;
    }
}

