<?php

/**
 * Raspbian WiFi Configuration Portal (RaspAP)
 *
 * Simple AP setup & WiFi management for Debian-based devices.
 * Enables use of simple web interface rather than SSH to control WiFi and related services  on the Raspberry Pi.
 * Recommended distribution is Raspberry Pi OS (32-bit) Lite. Specific instructions to install the supported software are
 * in the README and original post by @SirLagz. For a quick run through, the packages required for the WebGUI are:
 * lighttpd (version 1.4.53 installed via apt)
 * php-cgi (version 7.3.19-1 installed via apt)
 * along with their supporting packages, php7.3 will also need to be enabled.
 *
 * @author  Lawrence Yau <sirlagz@gmail.com>
 * @author  Bill Zimmerman <billzimmerman@gmail.com>
 * @license GNU General Public License, version 3 (GPL-3.0)
 * @version 2.6-beta
 * @link    https://github.com/billz/raspap-webgui/
 * @link    https://raspap.com/
 * @see     http://sirlagz.net/2013/02/08/raspap-webgui/
 *
 * You are not obligated to bundle the LICENSE file with your RaspAP projects as long
 * as you leave these references intact in the header comments of your source files.
 */

require 'includes/csrf.php';
ensureCSRFSessionToken();

require_once 'includes/config.php';
require_once 'includes/defaults.php';
require_once RASPI_CONFIG.'/raspap.php';
require_once 'includes/locale.php';
require_once 'includes/functions.php';
require_once 'includes/dashboard.php';
require_once 'includes/authenticate.php';
require_once 'includes/admin.php';
require_once 'includes/dhcp.php';
require_once 'includes/hostapd.php';
require_once 'includes/adblock.php';
require_once 'includes/system.php';
//require_once 'includes/sysstats.php';
require_once 'includes/configure_client.php';
require_once 'includes/networking.php';
require_once 'includes/themes.php';
require_once 'includes/data_usage.php';
require_once 'includes/about.php';
require_once 'includes/openvpn.php';
require_once 'includes/torproxy.php';
require_once 'vendor/BladeOne/lib/BladeOne.php';

$config = getConfig();

$output = $return = 0;
$page = $_SERVER['REQUEST_URI'];

$theme_url = getThemeOpt();
$toggleState = getSidebarState();
$bridgedEnabled = getBridgedState();

        $extraFooterScripts = array();
        // handle page actions
        switch ($page) {
        case "/wlan0_info":
            DisplayDashboard();
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
        case "/torproxy_conf":
            DisplayTorProxyConfig();
            break;
        case "/auth_conf":
            DisplayAuthConfig($config['admin_user'], $config['admin_pass']);
            break;
        case "/save_hostapd_conf":
            SaveTORAndVPNConfig();
            break;
        case "/theme_conf":
            DisplayThemeConfig();
            break;
        case "/data_use":
            DisplayDataUsage();
            break;
        case "/system_info":
            DisplaySystem();
            break;
        case "/about":
            DisplayAbout();
            break;
        default:
            DisplayDashboard();
        }
