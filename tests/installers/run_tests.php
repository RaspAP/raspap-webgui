<?php
/**
 * Tests for the installer functions that set the hostapd country_code
 * from the WiFi country configured for the system
 *
 * Usage: php tests/installers/run_tests.php
 */

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

$tmp = sys_get_temp_dir() . '/raspap_installer_test_' . getmypid();
mkdir("$tmp/bin", 0755, true);

// stub iw, printing $IW_REG_GET for 'iw reg get' or failing when it is unset
file_put_contents("$tmp/bin/iw", "#!/bin/bash\n[ -n \"\$IW_REG_GET\" ] || exit 1\nprintf '%b' \"\$IW_REG_GET\"\n");
chmod("$tmp/bin/iw", 0755);

/**
 * Runs a snippet of bash with installers/common.sh sourced, the way the
 * installer runs it (errexit and pipefail), and returns its output and status
 */
function runInstaller($tmp, $script, $iwRegGet = '')
{
    $common = realpath(__DIR__ . '/../../installers/common.sh');
    $bash = "set -eo pipefail\n"
        . "source " . escapeshellarg($common) . "\n"
        . "function _install_status() { echo \"status \$1 \$2\"; }\n"
        . "function sudo() { \"\$@\"; }\n"
        . $script . "\n";
    $env = 'PATH=' . escapeshellarg("$tmp/bin:" . getenv('PATH'))
        . ' IW_REG_GET=' . escapeshellarg($iwRegGet);
    exec("$env bash -c " . escapeshellarg($bash) . ' 2>&1', $output, $status);
    return [implode("\n", $output), $status];
}

function cmdline($tmp, $contents)
{
    file_put_contents("$tmp/cmdline", $contents . "\n");
    return escapeshellarg("$tmp/cmdline");
}

$iwGlobal = function ($country) {
    return "global\ncountry $country: DFS-ETSI\n\t(2400 - 2483 @ 40), (N/A, 20), (N/A)\n\n"
        . "phy#0\ncountry 99: DFS-UNSET\n\t(2402 - 2482 @ 40), (6, 20), (N/A)\n";
};

echo "_get_wifi_country()\n";

$args = cmdline($tmp, 'coherent_pool=1M 8250.nr_uarts=0 cfg80211.ieee80211_regdom=US console=tty1 rootwait');
[$out, $status] = runInstaller($tmp, "_get_wifi_country $args", $iwGlobal('DE'));
check('reads the regulatory domain from the kernel command line',
    $status === 0 && $out === 'US', "status $status, output '$out'");

$args = cmdline($tmp, 'console=tty1 cfg80211.ieee80211_regdom=nl');
[$out, $status] = runInstaller($tmp, "_get_wifi_country $args");
check('lowercase country is uppercased', $status === 0 && $out === 'NL', "output '$out'");

$args = cmdline($tmp, 'console=tty1 rootwait');
[$out, $status] = runInstaller($tmp, "_get_wifi_country $args", $iwGlobal('DE'));
check('falls back to the global regulatory domain from iw',
    $status === 0 && $out === 'DE', "status $status, output '$out'");

[$out, $status] = runInstaller($tmp, "_get_wifi_country $args", $iwGlobal('00'));
check('world regulatory domain (00) is not a country', $status === 0 && $out === '', "output '$out'");

[$out, $status] = runInstaller($tmp, "_get_wifi_country $args");
check('nothing is output when iw fails', $status === 0 && $out === '', "status $status, output '$out'");

[$out, $status] = runInstaller($tmp, "_get_wifi_country " . escapeshellarg("$tmp/missing"));
check('nothing is output when the command line is unreadable', $status === 0 && $out === '', "output '$out'");

$args = cmdline($tmp, 'console=tty1 cfg80211.ieee80211_regdom=USA');
[$out, $status] = runInstaller($tmp, "_get_wifi_country $args");
check('malformed country code is ignored', $status === 0 && $out === '', "output '$out'");

echo "\n_set_hostapd_country()\n";

$default = file_get_contents(__DIR__ . '/../../config/hostapd.conf');
$conf = "$tmp/hostapd.conf";

file_put_contents($conf, $default);
[$out, $status] = runInstaller($tmp,
    "function _get_wifi_country() { echo FR; }\n_set_hostapd_country " . escapeshellarg($conf));
check('country_code is set to the system WiFi country',
    $status === 0 && file_get_contents($conf) === str_replace('country_code=GB', 'country_code=FR', $default),
    "status $status, output '$out'");
check('only the country_code line is changed',
    substr_count(file_get_contents($conf), 'country_code=') === 1);

file_put_contents($conf, $default);
[$out, $status] = runInstaller($tmp,
    "function _get_wifi_country() { :; }\n_set_hostapd_country " . escapeshellarg($conf));
check('default country_code is kept when no WiFi country is set',
    $status === 0 && file_get_contents($conf) === $default, "status $status, output '$out'");

exec('rm -rf ' . escapeshellarg($tmp));

echo $failures === 0 ? "\nAll tests passed\n" : "\n$failures test(s) failed\n";
exit($failures === 0 ? 0 : 1);
