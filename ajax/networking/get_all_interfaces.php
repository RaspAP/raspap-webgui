<?php

require '../../includes/csrf.php';

exec("ls /sys/class/net | grep -v lo", $interfaces);
echo json_encode($interfaces);
