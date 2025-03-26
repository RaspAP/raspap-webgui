<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/defaults.php';
require_once '../../includes/functions.php';

$uri = RASPI_API_ENDPOINT;
preg_match('/(\d+(\.\d+)+)/', RASPI_VERSION, $matches);
$thisRelease = $matches[0];

$json = shell_exec("wget --timeout=5 --tries=1 $uri -qO -");
$data = json_decode($json, true);
$tagName = $data['tag_name'];
$updateAvailable = checkReleaseVersion($thisRelease, $tagName);

$response['tag'] = $tagName;
$response['update'] = $updateAvailable;
echo json_encode($response);

