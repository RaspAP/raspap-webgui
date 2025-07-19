<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

use RaspAP\System\Sysinfo;
use RaspAP\UI\Dashboard;
use RaspAP\Messages\StatusMessage;
use RaspAP\Plugins\PluginManager;
use RaspAP\Networking\Hotspot\WiFiManager;

/**
 * Displays the dashboard
 */
function DisplayDashboard(&$extraFooterScripts): void
{
    // instantiate RaspAP objects
    $system = new Sysinfo();
    $dashboard = new Dashboard();
    $status = new StatusMessage();
    $pluginManager = PluginManager::getInstance();
    $wifi = new WiFiManager();

    // set AP and client interface session vars
    $wifi->getWifiInterface();

    $interface = $_SESSION['ap_interface'] ?? 'wlan0';
    $clientInterface = $_SESSION['wifi_client_interface'];
    $hostname = $system->hostname();
    $revision = $system->rpiRevision();
    $deviceImage = $dashboard->getDeviceImage($revision);
    $hostapd = $system->hostapdStatus();
    $adblock = $system->adBlockStatus();
    $vpn = $system->getActiveVpnInterface();
    $frequency = $dashboard->getFrequencyBand($interface);
    $details = $dashboard->getInterfaceDetails($interface);
    $wireless = $dashboard->getWirelessDetails($interface);
    $connectionType = $dashboard->getConnectionType();
    $connectionIcon = $dashboard->getConnectionIcon($connectionType);
    $state = strtolower($details['state']);
    $wirelessClients = $dashboard->getWirelessClients($interface);
    $ethernetClients = $dashboard->getEthernetClients();
    $totalClients = $wirelessClients + $ethernetClients;
    $plugins = $pluginManager->getInstalledPlugins();
    $bridgedEnable = getBridgedState();

    // handle page actions
    if (!empty($_POST)) {
        $status = $dashboard->handlePageAction($state, $_POST, $status, $interface);
        // refresh interface details + state
        $details = $dashboard->getInterfaceDetails($interface);
        $state = strtolower($details['state']);
    }

    $ipv4Address = $details['ipv4'];
    $ipv4Netmask = $details['ipv4_netmask'];
    $macAddress = $details['mac'];
    $ssid = $wireless['ssid'];
    $ethernetActive = ($connectionType === 'ethernet') ? "active" : "inactive";
    $wirelessActive = ($connectionType === 'wireless') ? "active" : "inactive";
    $tetheringActive = ($connectionType === 'tethering') ? "active" : "inactive";
    $cellularActive = ($connectionType === 'cellular') ? "active" : "inactive";
    $bridgedStatus = ($bridgedEnable == 1) ? "active" : "";
    $hostapdStatus = ($hostapd[0] == 1) ?  "active" : "";
    $adblockStatus = ($adblock == true) ?  "active" : "";
    $wirelessClientActive = ($wirelessClients > 0) ? "active" : "inactive";
    $wirelessClientLabel = sprintf(
        _('%d WLAN %s'),
        $wirelessClients,
        $dashboard->formatClientLabel($wirelessClients)
    );
    $ethernetClientActive = ($ethernetClients > 0) ? "active" : "inactive";
    $ethernetClientLabel = sprintf(
        _('%d LAN %s'),
        $ethernetClients,
        $dashboard->formatClientLabel($ethernetClients)
    );
    $totalClientsActive = ($totalClients > 0) ? "active": "inactive";
    $freq5active = $freq24active = "";
    $varName = "freq" . str_replace('.', '', $frequency) . "active";
    $$varName = "active";
    $vpnStatus = $vpn ? "active" : "inactive";
    $vpnManaged = $vpn ? $dashboard->getVpnManaged($vpn) : null;
    $firewallManaged = $firewallStatus = "";
    $firewallInstalled = array_filter($plugins, fn($p) => str_ends_with($p, 'Firewall')) ? true : false;
    if (!$firewallInstalled) {
        $firewallUnavailable = '<i class="fas fa-slash fa-stack-1x"></i>';
    } else {
        $firewallManaged = '<a href="/plugin__Firewall">';
        $firewallStatus = ($dashboard->firewallEnabled() == true) ? "active" : "";
    }

    echo renderTemplate(
        "dashboard", compact(
            "revision",
            "deviceImage",
            "interface",
            "clientInterface",
            "state",
            "bridgedStatus",
            "hostapdStatus",
            "adblockStatus",
            "vpnStatus",
            "vpnManaged",
            "firewallUnavailable",
            "firewallStatus",
            "firewallManaged",
            "ipv4Address",
            "ipv4Netmask",
            "macAddress",
            "ssid",
            "frequency",
            "freq5active",
            "freq24active",
            "wirelessClients",
            "wirelessClientLabel",
            "wirelessClientActive",
            "ethernetClients",
            "ethernetClientLabel",
            "ethernetClientActive",
            "totalClients",
            "totalClientsActive",
            "connectionType",
            "connectionIcon",
            "ethernetActive",
            "wirelessActive",
            "tetheringActive",
            "cellularActive",
            "status"
        )
    );
    $extraFooterScripts[] = array('src'=>'app/js/vendor/dashboardchart.js', 'defer'=>false);
}

/**
 * Renders a URL for an svg solid line representing the associated
 * connection type
 *
 * @param string $connectionType
 * @return string
 */
function renderConnection(string $connectionType): string
{
    $deviceMap = [
        'ethernet'  => 'device-1',
        'wireless'  => 'device-2',
        'tethering' => 'device-3',
        'cellular'  => 'device-4'
    ];
    $device = $deviceMap[$connectionType] ?? 'device-unknown';

    // return generated URL for solid.php
    return sprintf('app/img/solid.php?joint&%s&out', $device);
}

/**
 * Renders a URL for an svg solid line representing associated
 * client connection(s)
 *
 * @param int $wirelessClients
 * @param int $ethernetClients
 * @return string
 */
function renderClientConnections(int $wirelessClients, int $ethernetClients): string
{
    $devices = [];

    if ($wirelessClients > 0) {
        $devices[] = 'device-1&out';
    }
    if ($ethernetClients > 0) {
        $devices[] = 'device-2&out';
    }
    return empty($devices) ? '' : sprintf(
        '<img src="app/img/right-solid.php?%s" class="solid-lines solid-lines-right" alt="Client connections">',
        implode('&', $devices)
    );
}


