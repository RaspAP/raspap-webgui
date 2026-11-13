<?php

require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';

\RaspAP\Tokens\CSRF::requirePost();

if (RASPI_MONITOR_ENABLED) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Plugins cannot be uninstalled in monitor mode']);
    exit;
}

$pluginInstaller = \RaspAP\Plugins\PluginInstaller::getInstance();
$plugin_namespace = $_POST['plugin_namespace'] ?? null;

if (is_string($plugin_namespace) && $plugin_namespace !== '') {
    try {
        $return = $pluginInstaller->uninstallPlugin($plugin_namespace);
        echo json_encode($return);
    } catch (Exception $e) {
        http_response_code(422); // unprocessable content
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Plugin namespace is required']);
    exit;
}
