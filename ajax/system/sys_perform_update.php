<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

// set installer path + options
$path = getenv("DOCUMENT_ROOT");
$opts = " --update --yes --check 0 --path $path";
$installer = "sudo /etc/raspap/system/raspbian.sh";
$execUpdate = $installer.$opts;

$response = shell_exec($execUpdate);
echo json_encode($response);

