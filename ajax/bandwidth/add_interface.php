<?php
/*
 Adds an interface to the vnstat database, for the button of the data usage
 page. Called by app/js/vendor/bandwidthcharts.js
*/
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json');

$interface = $_POST['inet'] ?? '';
if (!is_string($interface) || !preg_match('/^[a-zA-Z0-9]{1,15}$/', $interface)) {
    http_response_code(400);
    exit(json_encode(['error' => 'invalid']));
}
exec('sudo /usr/local/sbin/raspap-vnstat.sh add ' . escapeshellarg($interface), $output, $return);
echo json_encode(['added' => $return === 0]);
