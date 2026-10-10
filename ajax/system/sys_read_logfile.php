<?php

require_once '../../includes/autoload.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

// release the session lock so other requests are not blocked
session_write_close();

$logFile = '/tmp/raspap_install.log';
$since = (int)($_GET['since'] ?? 0);

// check the installer before reading the log, so a run that finishes
// in between is not reported as stopped
$running = !empty(shell_exec("pgrep -f '/etc/raspap/system/[r]aspbian\.sh'"));
$steps = getUpdateLogStatus($logFile, $since);

foreach ($steps as $step) {
    echo $step .PHP_EOL;
}
if (!$running && !in_array(6, $steps) && !in_array(7, $steps)) {
    echo "stopped" .PHP_EOL;
}
