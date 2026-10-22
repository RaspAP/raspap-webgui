<?php

require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';
require_once '../../includes/internetRoute.php';

initAsyncJsonEndpoint();

$iface = $_POST['iface'] ?? '';
if (!is_string($iface) || !validateInterface($iface)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid interface']);
    exit;
}

if (isAccessPoint($iface)) {
    echo json_encode(['isAP' => true]);
    exit;
}

echo json_encode(checkInternetAccess($iface));
