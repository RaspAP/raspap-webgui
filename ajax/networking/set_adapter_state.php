<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

header('Content-Type: application/json');

$interface = $_POST['interface'] ?? '';
$interface = preg_replace('/[^a-zA-Z0-9]/', '', $interface); // sanitize
$udev_path = "/sys/class/net/{$interface}";

if (empty($interface) || !file_exists($udev_path)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing interface']);
    exit;
}

$_POST['state'] = $_POST['state'] ?? '';
$state = strtolower($_POST['state']);
if (!in_array($state, ['up', 'down', 'cycle'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid state value']);
    exit;
}

if ($state === 'cycle') {
    // bring down first
    exec("sudo ip link set $interface down 2>/dev/null", $downOutput, $downStatus);
    if ($downStatus !== 0) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to bring adapter down']);
        exit;
    }
    // then up
    $state = 'up';
}

exec("sudo ip link set $interface $state 2>/dev/null", $output, $status);
if ($status !== 0) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to change adapter state']);
    exit;
}

sleep(2); // wait for state change to take effect

$data = [
    'interface' => $interface,
    'interface_up' => null,
];

// check if interface is up for buttons
exec("ip link show $interface 2>/dev/null", $ipLinkOutput, $linkStatus);
$data['interface_up'] = (bool)preg_grep('/state UP/', $ipLinkOutput);

echo json_encode($data, JSON_PRETTY_PRINT);
