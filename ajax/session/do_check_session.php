<?php

require '../../includes/csrf.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

$lastActivity = $_SESSION['lastActivity'] ?? time();
$sessionLifetime = time() - $lastActivity;
$status = $sessionLifetime >= RASPI_SESSION_TIMEOUT ? 'session_expired' : 'active';

// send response
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Pragma: no-cache');

$response = [
    'status' => $status,
    'last_activity' => $lastActivity,
    'session_lifetime' => $sessionLifetime
];

echo json_encode($response);
exit();

