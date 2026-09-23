<?php
/*
 Save settings of network devices (type, name, PW, APN ...)
 Called by js saveNetDeviceSettings (app/js/custom.js)
*/

use RaspAP\Utils\JsonResponse;
use RaspAP\Networking\NetDeviceHandler;
use RaspAP\Networking\MobileDataHandler;

require_once '../../includes/config.php';
require_once '../../includes/defaults.php';
require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../src/RaspAP/Auth/HTTPAuth.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

$form = $_POST['arrFormData'] ?? [];

try {
    // validate input
    if (empty($_POST['arrFormData']) || !is_array($_POST['arrFormData'])) {
        throw new \Exception('No form data received.');
    }
    $form = $_POST['arrFormData'];

    if ($form['interface'] == 'mobiledata') {
        $handler = new MobileDataHandler();
        $response = $handler->handle($form);
    } else {
        $deviceName = $form['opts'] ?? '';
        if (empty($deviceName)) {
            throw new \Exception('No device specified.');
        }
        $handler = new NetDeviceHandler();
        $response = $handler->handle($form);
    }
    echo $response->toJson();

} catch (\Exception $e) {
    echo JsonResponse::error($e->getMessage())->toJson();
}

