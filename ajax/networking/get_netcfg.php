<?php

use RaspAP\Networking\Hotspot\DhcpcdManager;

require_once '../../includes/autoload.php';
require_once '../../includes/CSRF.php';
require_once '../../includes/session.php';
require_once '../../includes/config.php';
require_once '../../includes/authenticate.php';
require_once '../../includes/functions.php';

$dhcpcdManager = new DhcpcdManager();

$interface = $_POST['iface'];

if (isset($interface)) {
    $dhcpdata = $dhcpcdManager->getInterfaceConfig($interface); 
    echo json_encode($dhcpdata);
}
