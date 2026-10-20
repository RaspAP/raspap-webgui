<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

// accept POST only, so the CSRF token is always verified
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
session_write_close();
header('Content-Type: application/json');

/**
 * Returns the time of the device in milliseconds, and whether NTP
 * synchronized the clock
 *
 * @return array
 */
function getDeviceTime(): array
{
    $synced = trim((string)shell_exec('timedatectl show -p NTPSynchronized --value 2>/dev/null'));
    return [
        'time' => (int)round(microtime(true) * 1000),
        'synced' => $synced === 'yes',
    ];
}

$action = $_POST['a'] ?? 'get';
if ($action === 'set') {
    // time of the browser, in milliseconds
    $time = $_POST['time'] ?? '';
    if (!is_string($time) || !ctype_digit($time)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid time']);
        exit;
    }
    // NTP keeps a synchronized clock correct; do not replace it with the
    // browser time, which can be wrong
    if (getDeviceTime()['synced']) {
        http_response_code(409);
        echo json_encode(['error' => 'The clock is synchronized with NTP']);
        exit;
    }
    $seconds = intdiv((int)$time, 1000);
    exec('sudo /usr/local/sbin/raspap-settime.sh ' . escapeshellarg((string)$seconds), $output, $return);
    if ($return !== 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Unable to set the time']);
        exit;
    }
}
echo json_encode(getDeviceTime());
