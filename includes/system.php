<?php

require_once 'includes/functions.php';
require_once 'config.php';

/**
 *
 */
function DisplaySystem(&$extraFooterScripts)
{
    $status = new \RaspAP\Messages\StatusMessage;
    $pluginInstaller = \RaspAP\Plugins\PluginInstaller::getInstance();

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

    // memory use
    $memused  = $system->usedMemory();
    $memStatus = getMemStatus($memused);
    $memused_status = $memStatus['status'];
    $memused_led = $memStatus['led'];

    // cpu load
    $cpuload = $system->systemLoadPercentage();
    $cpuload_status = getCPULoadStatus($cpuload);

    // cpu temp
    $cputemp = $system->systemTemperature();
    $cpuStatus = getCPUTempStatus($cputemp);
    $cputemp_status = $cpuStatus['status'];
    $cputemp_led =  $cpuStatus['led'];

    // theme options
    $themes = [
        "default"    => "RaspAP (default)",
        "hackernews" => "HackerNews",
    ];
    $themeFiles = [
        "default"    => "custom.php",
        "hackernews" => "hackernews.css",
    ];
    $selectedTheme = array_search($_COOKIE['theme'], $themeFiles);
    $extraFooterScripts[] = array('src'=>'dist/huebee/huebee.pkgd.min.js', 'defer'=>false);
    $extraFooterScripts[] = array('src'=>'app/js/huebee.js', 'defer'=>false);
    $logLimit = isset($_SESSION['log_limit']) ? $_SESSION['log_limit'] : RASPI_LOG_SIZE_LIMIT;

    $plugins = $pluginInstaller->getUserPlugins();
    $pluginsTable = $pluginInstaller->getHTMLPluginsTable($plugins);

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
        "themes",
        "selectedTheme",
        "logLimit",
        "pluginsTable"
    ));
}

function getMemStatus($memused): array
{
    $memused_status = "primary";
    $memused_led = "";

    if ($memused > 90) {
        $memused_status = "danger";
        $memused_led = "service-status-down";
    } elseif ($memused > 75) {
        $memused_status = "warning";
        $memused_led = "service-status-warn";
    } elseif ($memused > 0) {
        $memused_status = "success";
        $memused_led = "service-status-up";
    }

    return [
        'status' => $memused_status,
        'led' => $memused_led
    ];
}

function getCPULoadStatus($cpuload): string
{
    if ($cpuload > 90) {
        $status = "danger";
    } elseif ($cpuload > 75) {
        $status = "warning";
    } elseif ($cpuload >=  0) {
        $status = "success";
    }
    return $status;
}

function getCPUTempStatus($cputemp): array
{
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
    return [
        'status' => $cputemp_status,
        'led' => $cputemp_led
    ];
}

