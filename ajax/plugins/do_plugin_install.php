<?php

require '../../includes/csrf.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';
require_once '../../src/RaspAP/Plugins/PluginInstaller.php';

$pluginInstaller = \RaspAP\Plugins\PluginInstaller::getInstance();
$plugin_uri = $_POST['plugin_uri'] ?? null;
$plugin_version = $_POST['plugin_version'] ?? null;

if (isset($plugin_uri) && isset($plugin_version)) {
    $archiveUrl = rtrim($plugin_uri, '/') . '/archive/refs/tags/' . $plugin_version .'.zip';

    try {
        $return = $pluginInstaller->installPlugin($archiveUrl);
        echo json_encode($return);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Plugin URI and version are required']);
    exit;
}

