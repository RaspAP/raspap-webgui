<?php
/**
 * Tests for the "Check for update" / "Perform update" flow
 *
 * Usage: php tests/update/run_tests.php
 */

require_once __DIR__ . '/../../includes/functions.php';

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

function writeLog($dir, $name, array $lines)
{
    $file = "$dir/$name.log";
    file_put_contents($file, implode("\n", $lines) . "\n");
    return $file;
}

$tmp = sys_get_temp_dir() . '/raspap_update_test_' . getmypid();
mkdir($tmp);

// installer log lines, as written by _install_log and _install_status
$log = function ($msg) {
    return "\033[38;5;30mRaspAP Install: $msg\033[m";
};
$err = function ($msg) {
    return "[\033[0;31m \u{2718} error \033[m] \033[1;37;41m $msg \033[m";
};
$ok = "[\033[38;5;30m \u{2713} ok \033[m]";

echo "getUpdateLogStatus()\n";

check('missing log returns no steps',
    getUpdateLogStatus("$tmp/missing.log") === []);

$file = writeLog($tmp, 'progress', [
    $log('Configure update'), 'Detected OS: Debian 13', $ok,
    $log('Updating sources'), 'Hit:1 http://deb.debian.org/debian trixie InRelease',
    $log('Installing required packages'),
]);
check('in-progress log returns steps so far',
    getUpdateLogStatus($file) === [1, 2, 3], json_encode(getUpdateLogStatus($file)));

$file = writeLog($tmp, 'complete', [
    $log('Configure update'), $log('Updating sources'), $log('Installing required packages'),
    $log('Cloning latest files from GitHub'), $log('Installing application to /var/www/html'),
    $log('Installation completed'),
]);
check('completed log returns all steps',
    getUpdateLogStatus($file) === [1, 2, 3, 4, 5, 6], json_encode(getUpdateLogStatus($file)));

$file = writeLog($tmp, 'error', [
    $log('Configure update'), $log('Updating sources'),
    $err('Unable to update package list'),
    $log('Installation completed'),
]);
check('installer error is reported and stops parsing',
    getUpdateLogStatus($file) === [1, 2, 7], json_encode(getUpdateLogStatus($file)));

$file = writeLog($tmp, 'apt_noise', [
    $log('Configure update'), $log('Installing required packages'),
    'Setting up liberror-perl (0.17029-2) ...',
    'Unpacking libgpg-error0:arm64 (1.51-4) ...',
    'W: some warning mentioning an error',
]);
check('apt output containing "error" is not an installer error',
    getUpdateLogStatus($file) === [1, 3], json_encode(getUpdateLogStatus($file)));

$file = writeLog($tmp, 'error_digit', [
    $log('Configure update'),
    $err('Unable to install php8.4-fpm 6.x'),
]);
check('error message containing "6" is not reported as complete',
    getUpdateLogStatus($file) === [1, 7], json_encode(getUpdateLogStatus($file)));

$file = writeLog($tmp, 'stale', [$log('Configure update'), $log('Installation completed')]);
touch($file, time() - 600);
check('log from a previous update is ignored',
    getUpdateLogStatus($file, time()) === []);
check('log is read when no start time is given',
    getUpdateLogStatus($file) === [1, 6]);

echo "\nsession locking (sys_read_logfile.php)\n";

// Serve the web root with PHP's built-in server. A request with ?hold=1
// sleeps after the endpoint code has run (via auto_append_file), mimicking
// an endpoint that does long work after session_start(). A concurrent
// request with the same session must not have to wait for it.
$sessDir = "$tmp/sessions";
mkdir($sessDir);
$sid = 'raspaptest' . getmypid();
file_put_contents("$sessDir/sess_$sid", 'user_id|s:5:"admin";lastActivity|i:' . time() . ';');
file_put_contents("$tmp/hold.php", '<?php if (isset($_GET["hold"])) { sleep(3); }');

$port = 18080 + (getmypid() % 1000);
$docRoot = realpath(__DIR__ . '/../..');
$cmd = sprintf(
    'PHP_CLI_SERVER_WORKERS=2 exec php -d session.save_path=%s -d auto_append_file=%s -S 127.0.0.1:%d -t %s',
    escapeshellarg($sessDir), escapeshellarg("$tmp/hold.php"), $port, escapeshellarg($docRoot)
);
$server = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);

$base = "http://127.0.0.1:$port/ajax/system/sys_read_logfile.php";
$ctx = stream_context_create(['http' => [
    'header' => "Cookie: PHPSESSID=$sid\r\n", 'timeout' => 10, 'ignore_errors' => true
]]);

// wait for the server to accept connections
for ($i = 0; $i < 50 && @fsockopen('127.0.0.1', $port) === false; $i++) {
    usleep(100000);
}

$response = @file_get_contents($base, false, $ctx);
check('authenticated request succeeds',
    $response !== false && isset($http_response_header[0]) && strpos($http_response_header[0], '200') !== false,
    $http_response_header[0] ?? 'no response');

// a start time in the future excludes any real /tmp/raspap_install.log
$response = @file_get_contents($base . '?since=' . (time() + 60), false, $ctx);
check('reports "stopped" when the installer is not running',
    $response === "stopped\n", json_encode($response));

$hold = proc_open(['curl', '-s', '-o', '/dev/null', '-b', "PHPSESSID=$sid", "$base?hold=1"], [], $pipes);
usleep(500000);
$start = microtime(true);
@file_get_contents($base, false, $ctx);
$elapsed = microtime(true) - $start;
check('poll is not blocked by another request holding the session',
    $elapsed < 1.5, sprintf('waited %.2fs', $elapsed));

proc_close($hold);
proc_terminate($server);
proc_close($server);
exec('rm -rf ' . escapeshellarg($tmp));

echo $failures === 0 ? "\nAll tests passed\n" : "\n$failures test(s) failed\n";
exit($failures === 0 ? 0 : 1);
