<?php

require '../../includes/csrf.php';
require '../../src/RaspAP/Parsers/IwParser.php';

if (isset($_POST['interface'])) {

    $iface = escapeshellcmd($_POST['interface']);
    $parser = new \RaspAP\Parsers\IwParser($iface);

    $supportedFrequencies = $parser->parseIwList($iface);

    # debug
    #foreach ($supportedFrequencies as $frequency) {
    #    echo "<br>Frequency: {$frequency['MHz']} MHz, Channel: {$frequency['Channel']}, dBm: {$frequency['dBm']}\n";
    #}

    echo json_encode($supportedFrequencies);
}

