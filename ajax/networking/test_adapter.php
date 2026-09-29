<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

header('Content-Type: application/json');

$interface = $_POST['interface'] ?? '';
$interface = preg_replace('/[^a-zA-Z0-9]/', '', $interface); // sanitize

if (empty($interface)) {
    echo json_encode(['error' => 'Missing or invalid interface name']);
    exit;
}

$result = [
    'interface' => $interface,
    'interface_up' => false,
    'driver_bound' => false,
    'firmware_loaded' => false,
    'can_scan' => false,
    'has_ip' => false,
    'can_ping' => false
];

// interface up?
exec("ip link show $interface 2>/dev/null", $ipLinkOutput, $linkStatus);
$result['interface_up'] = (bool)preg_grep('/state UP/', $ipLinkOutput);

// driver + firmware check
exec("ethtool -i $interface 2>/dev/null", $ethtoolOut, $ethtoolStatus);
foreach ($ethtoolOut as $line) {
    if (stripos($line, 'driver:') !== false && !str_contains($line, 'driver: (null)')) {
        $result['driver_bound'] = true;
    }
    if (stripos($line, 'firmware-version:') !== false && !str_contains($line, 'firmware-version: (null)')) {
        $result['firmware_loaded'] = true;
    }
}

// scan for networks
exec("sudo iw dev $interface scan 2>/dev/null | grep SSID", $scanOutput, $scanStatus);
$result['can_scan'] = count($scanOutput) > 0;

// ip assigned?
exec("ip -4 addr show $interface 2>/dev/null | grep inet", $ipOut);
$result['has_ip'] = count($ipOut) > 0;

// can ping public IP?
if ($result['has_ip']) {
    $pingTarget = defined('RASPI_ACCESS_CHECK_IP') ? RASPI_ACCESS_CHECK_IP : '1.1.1.1';
    exec("ping -I $interface -c 1 -W 1 $pingTarget 2>/dev/null", $pingOut, $pingStatus);
    $result['can_ping'] = ($pingStatus === 0);
}

$rfkill = getRfkillStatus();
if ($rfkill) {
    $result = array_merge($result, $rfkill);
}

echo json_encode($result);

function getRfkillStatus(): ?array {
    $rfkillCmd = "rfkill list wifi 2>/dev/null";
    exec($rfkillCmd, $output, $exitCode);

    if ($exitCode !== 0 || empty($output)) {
        return null; // rfkill not present or unsupported
    }

    $soft = null;
    $hard = null;

    foreach ($output as $line) {
        if (stripos($line, 'Soft blocked:') !== false) {
            $soft = stripos($line, 'yes') === false;
        }
        if (stripos($line, 'Hard blocked:') !== false) {
            $hard = stripos($line, 'yes') === false;
        }
    }
    return [
        'rfkill_soft_blocked' => $soft,
        'rfkill_hard_blocked' => $hard
    ];
}

