<?php

require_once 'config.php';

/**
 * Manages ad blocking (dnsmasq) configuration
 *
 */
function DisplayAdBlockConfig()
{
    $status = new \RaspAP\Messages\StatusMessage;
    $enabled = false;
    $custom_enabled = false;

    \RaspAP\UI\LiveForm::loadStatusMessages($status);

    $custom_list = RASPI_ADBLOCK_LISTPATH . 'custom.txt';
    $custom_enabled = false;

    if (file_exists($custom_list) && filesize($custom_list) > 0) {
        $custom_enabled = true;
    }

    exec('cat '. RASPI_ADBLOCK_CONFIG, $return);
    $arrConf = ParseConfig($return);
    if (sizeof($arrConf) > 0) {
        $enabled = true;
    }

    exec('pidof dnsmasq | wc -l', $dnsmasq);
    $dnsmasq_state = ($dnsmasq[0] > 0);
    $serviceStatus = $dnsmasq_state && $enabled ? "up" : "down";

    if (file_exists(RASPI_ADBLOCK_LISTPATH .'custom.txt')) {
        $adblock_custom_content = file_get_contents(RASPI_ADBLOCK_LISTPATH .'custom.txt');
    } else {
        $adblock_custom_content = '';
    }
    $adblock_log = '';
    exec('sudo chmod o+r '.RASPI_DHCPCD_LOG);
    $handle = fopen(RASPI_DHCPCD_LOG, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/(is 0.0.0.0)|(using only locally-known addresses)/', $line)) {
                $adblock_log .= $line;
            }
        }
        fclose($handle);
    } else {
        $adblock_log = "Unable to open log file";
    }
    $logdata = getLogLimited(RASPI_DHCPCD_LOG, $adblock_log);

    echo renderTemplate(
        "adblock", compact(
        "status",
        "serviceStatus",
        "dnsmasq_state",
        "enabled",
        "custom_enabled",
        "adblock_custom_content",
        "logdata"
        )
    );
}

