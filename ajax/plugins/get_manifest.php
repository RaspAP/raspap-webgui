<?php

require '../../includes/csrf.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';
require_once '../../src/RaspAP/Plugins/PluginInstaller.php';

$pluginInstaller = \RaspAP\Plugins\PluginInstaller::getInstance();
$plugin_uri = $_POST['plugin_uri'] ?? null;

if (isset($plugin_uri)) {
    $manifestUrl = rtrim($plugin_uri, '/') .'/blob/master/manifest.json?raw=true';

    try {
        $manifest = $pluginInstaller->getPluginManifest($manifestUrl);
        if ($manifest) {
            echo json_encode($manifest);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Plugin manifest not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'An unexpected error occurred']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Plugin URI is required']);
    exit;
}

