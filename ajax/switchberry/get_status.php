<?php

require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

header('Content-Type: application/json; charset=utf-8');

$service = new \RaspAP\Switchberry\SwitchberryService();
echo json_encode($service->status(), JSON_UNESCAPED_SLASHES);
