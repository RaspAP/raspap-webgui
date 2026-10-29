<?php

require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

initAsyncJsonEndpoint();

// The helper accepts only these actions from the web interface (see raspap.sudoers)
$action = $_POST['action'] ?? '';
if (!in_array($action, ['open', 'close'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$helper = '/usr/local/sbin/raspap-captive-portal.sh';
if (!is_executable($helper)) {
    http_response_code(404);
    echo json_encode(['error' => 'The captive portal helper is not installed']);
    exit;
}

exec('sudo ' . $helper . ' ' . $action . ' 2>&1', $out, $rc);
if ($rc !== 0) {
    http_response_code(500);
    echo json_encode(['error' => trim(implode(' ', $out)) ?: 'Unable to change the captive portal login']);
    exit;
}
echo json_encode(['state' => getCaptivePortalState()]);
