<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

// fetch wg client.conf
$return = \RaspAP\Networking\VpnFile::readLines('wireguard', 'client.conf');
echo implode(PHP_EOL,$return);
