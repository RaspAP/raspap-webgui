<?php

require_once 'includes/status_messages.php';
require_once 'config.php';

/**
 * Find the version of the Raspberry Pi
 * Currently only used for the system information page but may useful elsewhere
 */

function RPiVersion()
{
    // Lookup table from http://www.raspberrypi-spy.co.uk/2012/09/checking-your-raspberry-pi-board-version/
    $revisions = array(
    '0002' => 'Model B Revision 1.0',
    '0003' => 'Model B Revision 1.0 + ECN0001',
    '0004' => 'Model B Revision 2.0 (256 MB)',
    '0005' => 'Model B Revision 2.0 (256 MB)',
    '0006' => 'Model B Revision 2.0 (256 MB)',
    '0007' => 'Model A',
    '0008' => 'Model A',
    '0009' => 'Model A',
    '000d' => 'Model B Revision 2.0 (512 MB)',
    '000e' => 'Model B Revision 2.0 (512 MB)',
    '000f' => 'Model B Revision 2.0 (512 MB)',
    '0010' => 'Model B+',
    '0013' => 'Model B+',
    '0011' => 'Compute Module',
    '0012' => 'Model A+',
    'a01041' => 'a01041',
    'a21041' => 'a21041',
    '900092' => 'PiZero 1.2',
    '900093' => 'PiZero 1.3',
    '9000c1' => 'PiZero W',
    'a02082' => 'Pi 3 Model B',
    'a22082' => 'Pi 3 Model B',
    'a32082' => 'Pi 3 Model B',
    'a52082' => 'Pi 3 Model B',
    'a020d3' => 'Pi 3 Model B+',
    'a220a0' => 'Compute Module 3',
    'a020a0' => 'Compute Module 3',
    'a02100' => 'Compute Module 3+',
    'a03111' => 'Model 4B Revision 1.1 (1 GB)',
    'b03111' => 'Model 4B Revision 1.1 (2 GB)',
    'c03111' => 'Model 4B Revision 1.1 (4 GB)'
    );

    $cpuinfo_array = '';
    exec('cat /proc/cpuinfo', $cpuinfo_array);
    $rev = trim(array_pop(explode(':', array_pop(preg_grep("/^Revision/", $cpuinfo_array)))));
    if (array_key_exists($rev, $revisions)) {
        return $revisions[$rev];
    } else {
        exec('cat /proc/device-tree/model', $model);
        if (isset($model[0])) {
            return $model[0];
        } else {
            return 'Unknown Device';
        }
    }
}

/**
 *
 *
 */
function DisplaySystem()
{

    $status = new StatusMessages();

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
            // Save settings
            if ($good_input) {
                exec("sudo /etc/raspap/lighttpd/configport.sh $serverPort $serverBind " .RASPI_LIGHTTPD_CONFIG. " ".$_SERVER['SERVER_NAME'], $return);
                foreach ($return as $line) {
                    $status->addMessage($line, 'info');
                }
            }
        }

        if (isset($_POST['system_reboot'])) {
            $status->addMessage("System Rebooting Now!", "warning", false);
            $result = shell_exec("sudo /sbin/reboot");
        }
        if (isset($_POST['system_shutdown'])) {
            $status->addMessage("System Shutting Down Now!", "warning", false);
            $result = shell_exec("sudo /sbin/shutdown -h now");
        }
    }

    if (isset($_POST['RestartLighttpd'])) {
        $status->addMessage('Restarting lighttpd in 3 seconds...', 'info');
        exec('sudo /etc/raspap/lighttpd/configport.sh --restart');
    }
    exec('cat '. RASPI_LIGHTTPD_CONFIG, $return);
    $conf = ParseConfig($return);
    $serverPort = $conf['server.port'];
    $serverBind = str_replace('"', '',$conf['server.bind']);

    // define locales
    $arrLocales = array(
        'en_GB.UTF-8' => 'English',
        'cs_CZ.UTF-8' => 'Čeština',
        'zh_TW.UTF-8' => '正體中文 (Chinese traditional)',
        'zh_CN.UTF-8' => '简体中文 (Chinese simplified)',
        'da_DK.UTF-8' => 'Dansk',
        'de_DE.UTF-8' => 'Deutsch',
        'es_MX.UTF-8' => 'Español',
        'fi_FI.UTF-8' => 'Finnish',
        'fr_FR.UTF-8' => 'Français',
        'el_GR.UTF-8' => 'Ελληνικά',
        'id_ID.UTF-8' => 'Indonesian',
        'it_IT.UTF-8' => 'Italiano',
        'ja_JP.UTF-8' => '日本語 (Japanese)',
        'ko_KR.UTF-8' => '한국어 (Korean)',
        'nl_NL.UTF-8' => 'Nederlands',
        'pl_PL.UTF-8' => 'Polskie',
        'pt_BR.UTF-8' => 'Português',
        'ru_RU.UTF-8' => 'Русский',
        'sv_SE.UTF-8' => 'Svenska',
        'tr_TR.UTF-8' => 'Türkçe',
        'vi_VN.UTF-8' => 'Tiếng Việt (Vietnamese)'
    );

    echo renderTemplate("system", compact("arrLocales", "status", "serverPort", "serverBind"));
}
