<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

if (isset($_POST['interface'])) {

    $iface = escapeshellcmd($_POST['interface']);
    $parser = new \RaspAP\Parsers\IwParser($iface);
    $supportedFrequencies = $parser->parseIwInfo($iface);

    echo json_encode($supportedFrequencies);
}
