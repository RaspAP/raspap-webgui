      <?php
        $extraFooterScripts = array();
        $page = $_SERVER['PATH_INFO'];
        // handle page actions
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
            DisplayNetworkingConfig();
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
        case "/about":
            DisplayAbout();
            break;
        default:
            DisplayDashboard($extraFooterScripts);
        }
      ?>

