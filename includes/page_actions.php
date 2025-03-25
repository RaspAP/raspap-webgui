<?php

$pluginManager = \RaspAP\Plugins\PluginManager::getInstance();

// Get the requested page
$extraFooterScripts = array();
$page = $_SERVER['PATH_INFO'];

// Check if any plugin wants to handle the request
if (!$pluginManager->handlePageAction($page)) {
    // If no plugin is available fall back to core page action handlers
    handleCorePageAction($page, $extraFooterScripts, $token);
}

/**
 * Core application page handling
 *
 * @param string $page
 * @param array $extraFooterScripts
 * @param object $token
 * @return void
 */
function handleCorePageAction(string $page, array &$extraFooterScripts, object $token): void
{
    switch ($page) {
        case "/wlan0_info":
            DisplayDashboard($extraFooterScripts, $token);
            break;
        case "/dhcpd_conf":
            DisplayDHCPConfig($token);
            break;
        case "/wpa_conf":
            DisplayWPAConfig($token);
            break;
        case "/network_conf":
            DisplayNetworkingConfig($token);
            break;
        case "/hostapd_conf":
            DisplayHostAPDConfig($token);
            break;
        case "/adblock_conf":
            DisplayAdBlockConfig($token);
            break;
        case "/openvpn_conf":
            DisplayOpenVPNConfig($token);
            break;
        case "/wg_conf":
            DisplayWireGuardConfig($token);
            break;
        case "/provider_conf":
            DisplayProviderConfig($token);
            break;
        case "/torproxy_conf":
            DisplayTorProxyConfig($token);
            break;
        case "/auth_conf":
            DisplayAuthConfig($_SESSION['user_id'], $token);
            break;
        case "/save_hostapd_conf":
            SaveTORAndVPNConfig($token);
            break;
        case "/data_use":
            DisplayDataUsage($extraFooterScripts, $token);
            break;
        case "/system_info":
            DisplaySystem($extraFooterScripts, $token);
            break;
        case "/restapi_conf":
            DisplayRestAPI($token);
            break;
        case "/about":
            DisplayAbout($token);
            break;
        case "/login":
            DisplayLogin($token);
            break;
        default:
            DisplayDashboard($extraFooterScripts, $token);
    }
}

