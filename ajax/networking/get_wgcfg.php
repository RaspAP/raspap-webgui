<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

// fetch wg client.conf
exec('sudo cat '. RASPI_WIREGUARD_PATH.'client.conf', $return);
echo implode(PHP_EOL,$return);
