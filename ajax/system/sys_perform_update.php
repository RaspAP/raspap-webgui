<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

// release the session lock so progress polling is not blocked
session_write_close();

// set installer path + options
$path = getenv("DOCUMENT_ROOT");
$opts = " --update --yes --check 0 --path ".escapeshellarg($path);
$installer = "sudo /etc/raspap/system/raspbian.sh";
$execUpdate = $installer.$opts;

// run the installer in the background; progress is read from its log file
$started = time();
shell_exec($execUpdate." > /dev/null 2>&1 &");
echo json_encode(['started' => $started]);
