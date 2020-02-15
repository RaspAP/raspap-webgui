<?php

require_once 'includes/status_messages.php';

/**
 * Manage Tor Proxy configuration
 */
function DisplayTorProxyConfig()
{

    exec('cat '. RASPI_TORPROXY_CONFIG, $return);
    exec('pidof tor | wc -l', $torproxystatus);

    $arrConfig = array();
    foreach ($return as $a) {
        if ($a[0] != "#") {
            $arrLine = explode(" ", $a);
            $arrConfig[$arrLine[0]]=$arrLine[1];
        }
    }

    echo renderTemplate(
        "torproxy", compact(
            "status",
            "torproxystatus"
        )
    );
}

/**
 *
 *
 */
function SaveTORAndVPNConfig()
{
    if (isset($_POST['SaveTORProxySettings'])) {
        // TODO
    } elseif (isset($_POST['StartTOR'])) {
        echo "Attempting to start TOR";
        exec('sudo systemctl start tor.service', $return);
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }
    } elseif (isset($_POST['StopTOR'])) {
        echo "Attempting to stop TOR";
        exec('sudo systemctl stop tor.service', $return);
        foreach ($return as $line) {
            $status->addMessage($line, 'info');
        }
    }
}
?>
