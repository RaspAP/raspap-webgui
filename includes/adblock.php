<?php

require_once 'includes/status_messages.php';
require_once 'config.php';

/**
 * Manages ad blocking (dnsmasq) configuration
 *
 */
function DisplayAdBlockConfig()
{
    $status = new StatusMessages();
    $enabled = false;

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['saveadblocksettings'])) {
            if ($_POST['adblock-enable'] == "1") {
                $config = 'conf-file=' .RASPI_ADBLOCK_LISTPATH .'domains.txt'.PHP_EOL;
                $config.= 'addn-hosts=' .RASPI_ADBLOCK_LISTPATH .'hostnames.txt'.PHP_EOL;
            } elseif ($_POST['adblock-enable'] == "0") {
                $config = null;
            }
            file_put_contents("/tmp/dnsmasqdata", $config);
            system('sudo cp /tmp/dnsmasqdata '.RASPI_ADBLOCK_CONFIG, $return);

            if ($return == 0) {
                $status->addMessage('Adblock configuration updated successfully', 'success');
            } else {
                $status->addMessage('Adblock configuration failed to be updated.', 'danger');
            }
        } elseif (isset($_POST['restartadblock']) || isset($_POST['startadblock'])) {
            exec('sudo /bin/systemctl restart dnsmasq.service', $dnsmasq, $return);
            if ($return == 0) {
                $status->addMessage('Adblock restart successful', 'success');
            } else {
                $status->addMessage('Adblock failed to restart.', 'danger');
            }
        }
    }

    exec('cat '. RASPI_ADBLOCK_CONFIG, $return);
    $arrConf = ParseConfig($return);
    if (sizeof($arrConf) > 0) {
        $enabled = true;
    }

    exec('pidof dnsmasq | wc -l', $dnsmasq);
    $dnsmasq_state = ($dnsmasq[0] > 0);
    $serviceStatus = $dnsmasq_state && $enabled ? "up" : "down";

    echo renderTemplate(
        "adblock", compact(
        "status",
        "serviceStatus",
        "dnsmasq_state",
        "enabled"
        )
    );
}

