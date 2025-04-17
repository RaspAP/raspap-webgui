<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

// fetch wg client.conf
exec('sudo cat '. RASPI_WIREGUARD_PATH.'client.conf', $return);
echo implode(PHP_EOL,$return);
