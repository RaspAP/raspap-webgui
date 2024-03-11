<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

exec("ls /sys/class/net | grep -v lo", $interfaces);
echo json_encode($interfaces);
