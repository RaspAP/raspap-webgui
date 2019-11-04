<?php

include_once('includes/status_messages.php');
include_once('app/lib/system.php');

/**
 *
 * Find the version of the Raspberry Pi
 * Currently only used for the system information page but may useful elsewhere
 *
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
    'c03111' => 'Model 4B v1.1'
    );

    $cpuinfo_array = '';
    exec('cat /proc/cpuinfo', $cpuinfo_array);
    $rev = trim(array_pop(explode(':', array_pop(preg_grep("/^Revision/", $cpuinfo_array)))));
    if (array_key_exists($rev, $revisions)) {
        return $revisions[$rev];
    } else {
        return 'Unknown Pi';
    }
}

/**
 *
 *
 */
function DisplaySystem()
{

    $status = new StatusMessages();
    $system = new System();


    if (isset($_POST['SaveLanguage'])) {
        if (isset($_POST['locale'])) {
            $_SESSION['locale'] = $_POST['locale'];
            $status->addMessage('Language setting saved', 'success');
        }
    }

    // define locales
    $arrLocales = array(
        'en_GB.UTF-8' => 'English',
        'de_DE.UTF-8' => 'Deutsch',
        'fr_FR.UTF-8' => 'Français',
        'it_IT.UTF-8' => 'Italiano',
        'pt_BR.UTF-8' => 'Português',
        'sv_SE.UTF-8' => 'Svenska',
        'nl_NL.UTF-8' => 'Nederlands',
        'zh_CN.UTF-8' => '简体中文 (Chinese simplified)',
        'id_ID.UTF-8' => 'Indonesian',
        'ko_KR.UTF-8' => '한국어 (Korean)',
        'ja_JP.UTF-8' => '日本語 (Japanese)',
        'vi_VN.UTF-8' => 'Tiếng Việt',
        'cs_CZ.UTF-8' => 'Čeština',
        'ru_RU.UTF-8' => 'Русский',
        'es_MX.UTF-8' => 'Español',
        'fi_FI.UTF-8' => 'Finnish',
        'si_LK.UTF-8' => 'Sinhala',
        'tr_TR.UTF-8' => 'Türkçe'
    );

    if (isset($_POST['system_reboot'])) {
        $status->addMessage("System Rebooting Now!", "warning", false);
        $result = shell_exec("sudo /sbin/reboot");
    }
    if (isset($_POST['system_shutdown'])) {
        $status->addMessage("System Shutting Down Now!", "warning", false);
        $result = shell_exec("sudo /sbin/shutdown -h now");
    }

    echo renderTemplate("system", compact("arrLocales", "status", "system"));
}
