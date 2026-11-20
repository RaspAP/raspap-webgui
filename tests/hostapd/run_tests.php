<?php
/**
 * Tests for the 802.11ax/be settings written to hostapd.conf
 *
 * Usage: php tests/hostapd/run_tests.php
 */

define('RASPI_HOSTAPD_CONFIG', '/nonexistent/hostapd.conf');
define('RASPI_HOSTAPD_CTRL_INTERFACE', '/var/run/hostapd');
define('RASPI_CONFIG_NETWORK', __DIR__ . '/../../config/defaults.json');

if (!function_exists('_')) {
    function _($text)
    {
        return $text;
    }
}

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../src/RaspAP/Messages/StatusMessage.php';
require_once __DIR__ . '/../../src/RaspAP/Networking/Hotspot/Validators/HostapdValidator.php';
require_once __DIR__ . '/../../src/RaspAP/Networking/Hotspot/HostapdManager.php';

use RaspAP\Messages\StatusMessage;
use RaspAP\Networking\Hotspot\HostapdManager;

$failures = 0;

function check($name, $condition, $detail = '')
{
    global $failures;
    if ($condition) {
        echo "  ok    $name\n";
    } else {
        echo "  FAIL  $name" . ($detail !== '' ? " ($detail)" : '') . "\n";
        $failures++;
    }
}

/**
 * Builds a config for the given mode and channel and returns its lines
 */
function build($hwMode, $channel)
{
    $hostapd = new HostapdManager();
    $config = $hostapd->buildConfig([
        'ssid' => 'raspap-test',
        'channel' => $channel,
        'hw_mode' => $hwMode,
        'interface' => 'wlan0',
        'wpa' => '2',
        'wpa_pairwise' => 'CCMP',
        'wpa_passphrase' => 'ChangeMe',
        'country_code' => 'US',
        '80211w' => 0,
    ], new StatusMessage());
    return explode(PHP_EOL, trim($config));
}

/**
 * Returns the lines that set the given hostapd key
 */
function lines($config, $key)
{
    return array_values(array_filter($config, function ($line) use ($key) {
        return strpos($line, $key . '=') === 0;
    }));
}

foreach (['ax', 'be'] as $mode) {
    $ieee = $mode === 'ax' ? 'ieee80211ax' : 'ieee80211be';

    foreach ([1, 6, 11] as $channel) {
        echo "buildConfig() 802.11$mode, 2.4 GHz channel $channel\n";
        $config = build($mode, $channel);

        check('uses hw_mode=g', lines($config, 'hw_mode') === ['hw_mode=g'],
            implode(', ', lines($config, 'hw_mode')));
        check("keeps $ieee=1", in_array("$ieee=1", $config));
        check('keeps ieee80211n=1', in_array('ieee80211n=1', $config));
        check('drops 802.11ac and VHT settings',
            !lines($config, 'ieee80211ac') && !lines($config, 'require_vht')
            && !lines($config, 'vht_capab') && !lines($config, 'vht_oper_chwidth'));
        check('drops center frequency indices',
            !lines($config, 'vht_oper_centr_freq_seg0_idx')
            && !lines($config, 'he_oper_centr_freq_seg0_idx')
            && !lines($config, 'eht_oper_centr_freq_seg0_idx'));
        check('drops 6 GHz settings', !lines($config, 'he_6ghz_max_mpdu')
            && !lines($config, 'he_6ghz_max_ampdu_len_exp') && !lines($config, 'he_6ghz_reg_pwr_type'));
        check('runs HE at 20 MHz', !array_diff(lines($config, 'he_oper_chwidth'), ['he_oper_chwidth=0']));
        if ($mode === 'be') {
            check('runs EHT at 20 MHz', lines($config, 'eht_oper_chwidth') === ['eht_oper_chwidth=0']);
        }
        $htCapab = implode('', lines($config, 'ht_capab'));
        check('has no HT40 or empty ht_capab flags',
            strpos($htCapab, 'HT40') === false && strpos($htCapab, '[]') === false
            && strpos($htCapab, '{') === false, $htCapab);
        check('has no unfilled placeholders', strpos(implode("\n", $config), '{') === false);
    }

    echo "buildConfig() 802.11$mode, 5 GHz channel 36\n";
    $config = build($mode, 36);
    check('uses hw_mode=a', lines($config, 'hw_mode') === ['hw_mode=a'],
        implode(', ', lines($config, 'hw_mode')));
    check('keeps ieee80211ac=1', in_array('ieee80211ac=1', $config));
    check('keeps the 80 MHz center frequency index',
        lines($config, 'vht_oper_centr_freq_seg0_idx') === ['vht_oper_centr_freq_seg0_idx=42'],
        implode(', ', lines($config, 'vht_oper_centr_freq_seg0_idx')));
}

echo "buildConfig() 802.11n\n";
check('2.4 GHz channel uses hw_mode=g', lines(build('n', 6), 'hw_mode') === ['hw_mode=g']);
check('5 GHz channel uses hw_mode=a', lines(build('n', 36), 'hw_mode') === ['hw_mode=a']);

echo "\n";
if ($failures > 0) {
    echo "$failures check(s) failed\n";
    exit(1);
}
echo "All checks passed\n";
