<?php

require_once 'includes/functions.php';
require_once 'config.php';

/**
 *
 */
function DisplaySystem(&$extraFooterScripts)
{
    $status = new \RaspAP\Messages\StatusMessage;

    if (isset($_POST['SaveLanguage'])) {
        if (isset($_POST['locale'])) {
            $_SESSION['locale'] = $_POST['locale'];
            $status->addMessage('Language setting saved', 'success');
        }
    }

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveServerSettings'])) {
            $good_input = true;
            // Validate server port
            if (isset($_POST['serverPort'])) {
                if (strlen($_POST['serverPort']) > 4 || !is_numeric($_POST['serverPort'])) {
                    $status->addMessage('Invalid value for port number', 'danger');
                    $good_input = false;
                } else {
                    $serverPort = escapeshellarg($_POST['serverPort']);
               }
            }
            // Validate server bind address
            $serverBind = escapeshellarg('');
            if ($_POST['serverBind'] && $_POST['serverBind'] !== null ) {
                if (!filter_var($_POST['serverBind'], FILTER_VALIDATE_IP)) {
                    $status->addMessage('Invalid value for bind address', 'danger');
                    $good_input = false;
                } else {
                    $serverBind = escapeshellarg($_POST['serverBind']);
                }
            }
            // Validate log limit
            if (isset($_POST['logLimit'])) {
                if ( strlen($_POST['logLimit']) > 4 || !is_numeric($_POST['logLimit']) ) {
                    $status->addMessage('Invalid value for log size limit', 'danger');
                    $good_input = false;
                } else {
                    $_SESSION['log_limit'] = intval($_POST['logLimit']);
                    $status->addMessage(sprintf(_('Changing log limit size to %s KB'), $_SESSION['log_limit']), 'info');
                }
            }
            // Save settings
            if ($good_input) {
                exec("sudo /etc/raspap/lighttpd/configport.sh $serverPort $serverBind " .RASPI_LIGHTTPD_CONFIG. " ".$_SERVER['SERVER_NAME'], $return);
                foreach ($return as $line) {
                    $status->addMessage($line, 'info');
                }
            }
        }
    }

    if (isset($_POST['RestartLighttpd'])) {
        $status->addMessage('Restarting lighttpd in 3 seconds...', 'info');
        exec('sudo /etc/raspap/lighttpd/configport.sh --restart');
    }
    exec('cat '. RASPI_LIGHTTPD_CONFIG, $return);
    $conf = ParseConfig($return);
    $serverPort = $conf['server.port'];
    if (isset($conf['server.bind'])) {
        $serverBind = str_replace('"', '',$conf['server.bind']);
    } else {
        $serverBind = '';
    }

    // define locales
    $arrLocales = getLocales();

    // fetch system status variables
    $system = new \RaspAP\System\Sysinfo;

    $hostname = $system->hostname();
    $uptime   = $system->uptime();
    $cores    = $system->processorCount();
    $os       = $system->operatingSystem();
    $kernel   = $system->kernelVersion();
    $systime  = $system->systime();
    $revision = $system->rpiRevision();
    
    // mem used
    $memused  = $system->usedMemory();
    $memused_status = "primary";
    if ($memused > 90) {
        $memused_status = "danger";
        $memused_led = "service-status-down";
    } elseif ($memused > 75) {
        $memused_status = "warning";
        $memused_led = "service-status-warn";
    } elseif ($memused >  0) {
        $memused_status = "success";
        $memused_led = "service-status-up";
    }

    // cpu load
    $cpuload = $system->systemLoadPercentage();
    if ($cpuload > 90) {
        $cpuload_status = "danger";
    } elseif ($cpuload > 75) {
        $cpuload_status = "warning";
    } elseif ($cpuload >=  0) {
        $cpuload_status = "success";
    }

    // cpu temp
    $cputemp = $system->systemTemperature();
    if ($cputemp > 70) {
        $cputemp_status = "danger";
        $cputemp_led = "service-status-down";
    } elseif ($cputemp > 50) {
        $cputemp_status = "warning";
        $cputemp_led = "service-status-warn";
    } else {
        $cputemp_status = "success";
        $cputemp_led = "service-status-up";
    }

    // hostapd status
    $hostapd = $system->hostapdStatus();
    if ($hostapd[0] == 1) {
        $hostapd_status = "active";
        $hostapd_led = "service-status-up";
    } else {
        $hostapd_status = "inactive";
        $hostapd_led = "service-status-down";
    }

    // theme options
    $themes = [
        "default"    => "RaspAP (default)",
        "hackernews" => "HackerNews",
        "material-light" => "Material"
    ];
    $themeFiles = [
        "default"    => "custom.php",
        "hackernews" => "hackernews.css",
        "material-light" => "material-light.php"
    ];
    $selectedTheme = array_search($_COOKIE['theme'], $themeFiles);
    if (strpos($_COOKIE['theme'],'material') !== false) {
        $selectedTheme = 'material-light';
    }

    $extraFooterScripts[] = array('src'=>'dist/huebee/huebee.pkgd.min.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'app/js/huebee.js', 'defer'=>false);
    $logLimit = isset($_SESSION['log_limit']) ? $_SESSION['log_limit'] : RASPI_LOG_SIZE_LIMIT;

    echo renderTemplate("system", compact(
        "arrLocales",
        "status",
        "serverPort",
        "serverBind",
        "hostname",
        "uptime",
        "systime",
        "revision",
        "cores",
        "os",
        "kernel",
        "memused",
        "memused_status",
        "memused_led",
        "cpuload",
        "cpuload_status",
        "cputemp",
        "cputemp_status",
        "cputemp_led",
        "hostapd",
        "hostapd_status",
        "hostapd_led",
        "themes",
        "selectedTheme",
        "logLimit"
    ));
}
