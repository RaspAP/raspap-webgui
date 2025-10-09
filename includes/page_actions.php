<?php

$pluginManager = \RaspAP\Plugins\PluginManager::getInstance();

// Get the requested page
$extraFooterScripts = array();
$page = $_SERVER['PATH_INFO'] ?? '';

// Check if any plugin wants to handle the request
if (!$pluginManager->handlePageAction($page)) {
    // If no plugin is available fall back to core page action handlers
    handleCorePageAction($page, $extraFooterScripts);
}

/**
 * Core application page handling
 *
 * @param string $page
 * @param array $extraFooterScripts
 * @return void
 */
function handleCorePageAction(string $page, array &$extraFooterScripts): void
{
    switch ($page) {
        case "/wlan0_info":
            DisplayDashboard($extraFooterScripts);
            break;
        case "/dhcpd_conf":
            DisplayDHCPConfig();
            break;
        case "/wpa_conf":
            DisplayWPAConfig();
            break;
        case "/network_conf":
            DisplayNetworkingConfig($extraFooterScripts);
            break;
        case "/hostapd_conf":
            DisplayHostAPDConfig();
            break;
        case "/adblock_conf":
            DisplayAdBlockConfig();
            break;
        case "/openvpn_conf":
            DisplayOpenVPNConfig();
            break;
        case "/wg_conf":
            DisplayWireGuardConfig();
            break;
        case "/provider_conf":
            DisplayProviderConfig();
            break;
        case "/torproxy_conf":
            DisplayTorProxyConfig();
            break;
        case "/auth_conf":
            DisplayAuthConfig($_SESSION['user_id']);
            break;
        case "/save_hostapd_conf":
            SaveTORAndVPNConfig();
            break;
        case "/data_use":
            DisplayDataUsage($extraFooterScripts);
            break;
        case "/system_info":
            DisplaySystem($extraFooterScripts);
            break;
        case "/restapi_conf":
            DisplayRestAPI();
            break;
        case "/about":
            DisplayAbout();
            break;
        case "/login":
            DisplayLogin();
            break;
        default:
            DisplayDashboard($extraFooterScripts);
    }
}

