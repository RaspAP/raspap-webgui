<?php

require_once 'includes/functions.php';
require_once 'config.php';

/**
 *
 */
function DisplaySystem(&$extraFooterScripts)
{
    $status = new \RaspAP\Messages\StatusMessage;
    $dashboard = new \RaspAP\UI\Dashboard;
    $pluginInstaller = \RaspAP\Plugins\PluginInstaller::getInstance();

    // set defaults
    $optAutoclose = true;
    $alertTimeout = 5000;
    $good_input = true;
    $config_port = false;

    // set alert_timeout from cookie if valid
    if (isset($_COOKIE['alert_timeout']) && is_numeric($_COOKIE['alert_timeout'])) {
        $cookieTimeout = (int) $_COOKIE['alert_timeout'];

        if ($cookieTimeout > 0) {
            $alertTimeout = $cookieTimeout;
        } else {
            // A value of 0 means auto-close is disabled
            $optAutoclose = false;
        }
    }
    if (isset($_POST['SaveLanguage'])) {
        if (isset($_POST['locale'])) {
            $_SESSION['locale'] = $_POST['locale'];
            $status->addMessage('Language setting saved', 'success');
        }
    }

    if (!RASPI_MONITOR_ENABLED) {
        if (isset($_POST['SaveServerSettings'])) {
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
            if (isset($_POST['serverBind']) && $_POST['serverBind'] !== '') {
                $inputBind = trim($_POST['serverBind']);
                if (!filter_var($inputBind, FILTER_VALIDATE_IP)) {
                    $status->addMessage('Invalid value for bind address', 'danger');
                    $good_input = false;
                } else {
                    $serverBind = escapeshellarg($inputBind);
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
        } elseif (isset($_POST['savethemeSettings'])) {
            // Validate alert timout
            if (isset($_POST['autoClose'])) {
                $alertTimeout = trim($_POST['alertTimeout'] ?? '');
                if (strlen($alertTimeout) > 7 || !is_numeric($alertTimeout)) {
                    $status->addMessage('Invalid value for alert close timeout', 'danger');
                    $good_input = false;
                } else {
                    setcookie('alert_timeout', (int) $alertTimeout);
                    $status->addMessage(sprintf(_('Changing alert close timeout to %s ms'), $alertTimeout), 'info');
                }
            } else {
                setcookie('alert_timeout', '', time() - 3600, '/');
                $optAutoclose = false;
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
    $deviceImage = $dashboard->getDeviceImage($revision);

    // memory use
    $memused  = $system->usedMemory();
    $memStatus = getResourceStatus($memused);
    $memused_status = $memStatus['status'];
    $memused_led = $memStatus['led'];

    // disk storage use
    $diskused  = $system->usedDisk();
    $diskStatus = getResourceStatus($diskused);
    $diskused_status = $diskStatus['status'];
    $diskused_led = $diskStatus['led'];

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
    $extraFooterScripts[] = array('src'=>'app/js/vendor/huebee.js', 'defer'=>false);
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
        "deviceImage",
        "cores",
        "os",
        "kernel",
        "memused",
        "memused_status",
        "memused_led",
        "diskused",
        "diskused_status",
        "diskused_led",
        "cpuload",
        "cpuload_status",
        "cputemp",
        "cputemp_status",
        "cputemp_led",
        "themes",
        "selectedTheme",
        "logLimit",
        "pluginsTable",
        "optAutoclose",
        "alertTimeout"
    ));
}

function getResourceStatus($used): array
{
    $used_status = "primary";
    $used_led = "";

    if ($used > 90) {
        $used_status = "danger";
        $used_led = "service-status-down";
    } elseif ($used > 75) {
        $used_status = "warning";
        $used_led = "service-status-warn";
    } elseif ($used > 0) {
        $used_status = "success";
        $used_led = "service-status-up";
    }

    return [
        'status' => $used_status,
        'led' => $used_led
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

