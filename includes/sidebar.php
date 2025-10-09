<?php

use RaspAP\Plugins\PluginManager;

$pluginManager = PluginManager::getInstance();

// Display logo and status LEDs
renderStatus($hostapd_led, $hostapd_status,
    $memused_led, $memused,
    $cputemp_led, $cputemp);

// Render sidebar via the PluginManager
$sidebar = $pluginManager->getSidebar();
$sidebar->render();

