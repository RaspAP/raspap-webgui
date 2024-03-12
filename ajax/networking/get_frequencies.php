<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../src/RaspAP/Parsers/IwParser.php';
require_once '../../includes/authenticate.php';

if (isset($_POST['interface'])) {

    $iface = escapeshellcmd($_POST['interface']);
    $parser = new \RaspAP\Parsers\IwParser($iface);
    $supportedFrequencies = $parser->parseIwInfo($iface);

    echo json_encode($supportedFrequencies);
}
