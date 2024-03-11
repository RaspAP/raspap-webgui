<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require '../../src/RaspAP/Parsers/IwParser.php';

if (isset($_POST['interface'])) {

    $iface = escapeshellcmd($_POST['interface']);
    $parser = new \RaspAP\Parsers\IwParser($iface);
    $supportedFrequencies = $parser->parseIwInfo($iface);

    echo json_encode($supportedFrequencies);
}
