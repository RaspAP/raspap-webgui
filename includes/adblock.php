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
    exec('cat '. RASPI_DNSMASQ_CONFIG, $return);
    $arrConf = ParseConfig($return);

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['saveadblocksettings'])) {
            if ($_POST['adblock-enable'] == "1") {
                $arrConf['conf-file'] = '/etc/dnsmasq.d/domains.txt';
                $arrConf['addn-hosts'] = '/etc/dnsmasq.d/hostnames.txt';
            } else {
                unset($arrConf['conf-file']);
                unset($arrConf['addn-hosts']);
            }
            $config = array_map(function($value, $key) {
                return $key.'='.$value;
            }, array_values($arrConf), array_keys($arrConf));
            $config = implode(PHP_EOL, $config);
            $config = $config . PHP_EOL;

            file_put_contents("/tmp/dnsmasqdata", $config);
            system('sudo cp /tmp/dnsmasqdata '.RASPI_DNSMASQ_CONFIG, $return);

            if ($return == 0) {
                $status->addMessage('Adblock configuration updated successfully', 'success');
            } else {
                $status->addMessage('Adblock configuration failed to be updated.', 'danger');
            }
        }
    }

    exec('pidof dnsmasq | wc -l', $dnsmasq);
    $dnsmasq_state = ($dnsmasq[0] > 0);
    $serviceStatus = $dnsmasq_state ? "up" : "down";

    echo renderTemplate(
        "adblock", compact(
            "status",
            "serviceStatus",
            "arrConf"
        )
    );
}



