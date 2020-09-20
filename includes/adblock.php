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
    $custom_enabled = false;

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['saveadblocksettings'])) {
            if ($_POST['adblock-enable'] == "1") {
                $config = 'conf-file=' .RASPI_ADBLOCK_LISTPATH .'domains.txt'.PHP_EOL;
                $config.= 'addn-hosts=' .RASPI_ADBLOCK_LISTPATH .'hostnames.txt'.PHP_EOL;
            } elseif ($_POST['adblock-enable'] == "0") {
                $config = null;
            }
            if ($_POST['adblock-custom-enable'] == "1") {
                // validate custom hosts input
                $lines = preg_split('/\r\n|\n|\r/', trim($_POST['adblock-custom-hosts']));
                if (!in_array("", $lines, true)) {
                    foreach ($lines as $line) {
                        $ip_host = preg_split('/\s+/', $line);
                        $index++;
                        if (!filter_var($ip_host[0], FILTER_VALIDATE_IP)) {
                            $errors .= _('Invalid custom IP address found on line '.$index);
                            break;
                        }
                        if (!validate_host($ip_host[1])) {
                            $errors .= _('Invalid custom host found on line '.$index);
                            break;
                        }
                    }
                }
                file_put_contents("/tmp/dnsmasq_custom", $_POST['adblock-custom-hosts'].PHP_EOL);
                system("sudo cp /tmp/dnsmasq_custom " .RASPI_ADBLOCK_LISTPATH .'custom.txt', $return);
                $config.= 'addn-hosts=' .RASPI_ADBLOCK_LISTPATH .'custom.txt'.PHP_EOL;
                $custom_enabled = true;
            }

            if (empty($errors)) {
                file_put_contents("/tmp/dnsmasqdata", $config);
                system('sudo cp /tmp/dnsmasqdata '.RASPI_ADBLOCK_CONFIG, $return);
                if ($return == 0) {
                    $status->addMessage('Adblock configuration updated successfully', 'success');
                } else {
                    $status->addMessage('Adblock configuration failed to be updated.', 'danger');
                }
            } else {
                $status->addMessage($errors, 'danger');
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
        "enabled",
        "custom_enabled"
        )
    );
}

