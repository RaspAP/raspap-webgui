<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';

// fetch wg client.conf
exec('sudo cat '. RASPI_WIREGUARD_PATH.'client.conf', $return);
echo implode(PHP_EOL,$return);

