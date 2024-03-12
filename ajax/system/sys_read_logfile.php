<?php

require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';

$logFile = '/tmp/raspap_install.log';
$searchStrings = [
    'Configure update' => 1,
    'Updating sources' => 2,
    'Installing required packages' => 3,
    'Cloning latest files' => 4,
    'Installing application' => 5,
    'Installation completed' => 6,
    'error' => 7
];
usleep(500);

if (file_exists($logFile)) {
    $handle = fopen($logFile, 'r');

    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            foreach ($searchStrings as $searchString => $value) {
                if (strpos($line, $searchString) !== false) {
                    echo $value .PHP_EOL;
                    flush();
                    ob_flush();
                    if ($value === 6) {
                        fclose($handle);
                        exit();
                    } elseif ($value === 7) {
                        echo $line .PHP_EOL;
                        fclose($handle);
                        exit();
                    }
                }
            }
        }
        fclose($handle);
    } else {
        echo json_encode("Unable to open file: $logFile");
    }
} else {
    echo json_encode("File does not exist: $logFile");
}
