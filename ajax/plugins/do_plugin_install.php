<?php
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

$pluginInstaller = \RaspAP\Plugins\PluginInstaller::getInstance();
$plugin_uri = $_POST['plugin_uri'] ?? null;
$plugin_version = $_POST['plugin_version'] ?? null;
$install_path = $_POST['install_path'] ?? null;

if (isset($plugin_uri, $plugin_version, $install_path)) {
    try {
        $return = $pluginInstaller->installPlugin($plugin_uri, $plugin_version, $install_path);
        echo json_encode($return);
    } catch (Exception $e) {
        http_response_code(422); // unprocessable content
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Plugin URI, version, and install path are required']);
    exit;
}

