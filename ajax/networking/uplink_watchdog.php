<?php

require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

initAsyncJsonEndpoint();

// The helper accepts only these actions (see raspap.sudoers)
$action = $_POST['action'] ?? 'status';
if (!in_array($action, ['status', 'enable', 'disable'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$helper = '/usr/local/sbin/raspap-uplink-watchdog.sh';
if (!is_executable($helper)) {
    echo json_encode(['available' => false]);
    exit;
}

if ($action !== 'status') {
    exec('sudo ' . $helper . ' ' . $action, $out, $rc);
    if ($rc !== 0) {
        http_response_code(500);
        echo json_encode(['error' => 'Unable to change the uplink watchdog']);
        exit;
    }
}

$out = [];
exec('sudo ' . $helper . ' status', $out, $rc);
$state = [];
foreach ($out as $line) {
    if (preg_match('/^(enabled|failures|last_action|last_result|uplink|gateway)=(.*)$/', $line, $m)) {
        $state[$m[1]] = $m[2];
    }
}

echo json_encode([
    'available' => $rc === 0,
    'enabled' => ($state['enabled'] ?? '0') === '1',
    'failures' => (int)($state['failures'] ?? 0),
    'lastAction' => (int)($state['last_action'] ?? 0),
    'lastResult' => $state['last_result'] ?? '',
    'uplink' => $state['uplink'] ?? '',
    'gateway' => $state['gateway'] ?? '',
]);
