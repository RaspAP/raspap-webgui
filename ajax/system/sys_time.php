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
header('Content-Type: application/json');

/**
 * Returns the ID of the current boot. It changes at each boot.
 *
 * @return string
 */
function getBootId(): string
{
    return trim((string)@file_get_contents('/proc/sys/kernel/random/boot_id'));
}

/**
 * Returns the time of the device in milliseconds, and whether NTP
 * synchronized the clock
 *
 * @param bool $knownSynced NTP synchronized the clock earlier in this boot
 * @return array
 */
function getDeviceTime(bool $knownSynced = false): array
{
    $synced = $knownSynced
        || trim((string)shell_exec('timedatectl show -p NTPSynchronized --value 2>/dev/null')) === 'yes';
    return [
        'time' => (int)round(microtime(true) * 1000),
        'synced' => $synced,
    ];
}

// After NTP synchronized the clock, the clock stays correct for a long time.
// Thus a synchronized result is kept in the session for one hour, and
// timedatectl does not run on every page. A reboot (new boot ID) or the end
// of the hour starts a new check.
const CLOCK_SYNCED_TTL = 3600;
$bootId = getBootId();
$cached = $_SESSION['clock_synced'] ?? null;
$age = time() - (int)($cached['time'] ?? 0);
$knownSynced = $bootId !== ''
    && ($cached['boot_id'] ?? '') === $bootId
    && $age >= 0 && $age < CLOCK_SYNCED_TTL;
$device = getDeviceTime($knownSynced);
if ($device['synced'] && !$knownSynced && $bootId !== '') {
    $_SESSION['clock_synced'] = ['boot_id' => $bootId, 'time' => time()];
}
session_write_close();

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
    if ($device['synced']) {
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
    // the time of the device after the change
    $device = getDeviceTime();
}
echo json_encode($device);
