<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/defaults.php';
require_once '../../includes/functions.php';

session_write_close();

$uri = RASPI_API_ENDPOINT;
preg_match('/(\d+(\.\d+)+)/', RASPI_VERSION, $matches);
$thisRelease = $matches[0];

$json = shell_exec("wget --timeout=5 --tries=1 $uri -qO -");
$data = json_decode($json ?? '', true);
$tagName = $data['tag_name'] ?? null;

if (empty($tagName)) {
    $response['error'] = true;
} else {
    $response['tag'] = $tagName;
    $response['update'] = checkReleaseVersion($thisRelease, $tagName);
}
echo json_encode($response);

