<?php

/**
 * Sidebar UI class
 *
 * @description A sidebar class for the RaspAP UI, extendable by user plugins
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\UI;

class Sidebar {
    private $items = [];

    public function __construct() {
        // Load default sidebar items
        $this->addItem(_('Dashboard'), 'fa-solid fa-gauge-high', 'wlan0_info', 10);
        $this->addItem(_('Hotspot'), 'far fa-dot-circle', 'hostapd_conf', 20,
            fn() => defined('RASPI_HOTSPOT_ENABLED') && RASPI_HOTSPOT_ENABLED 
        );
        $this->addItem(_('DHCP Server'), 'fas fa-exchange-alt', 'dhcpd_conf', 30,
            fn() => defined('RASPI_DHCP_ENABLED') && RASPI_DHCP_ENABLED && !$_SESSION["bridgedEnabled"] 
        );
        $this->addItem(_('Ad Blocking'), 'far fa-hand-paper', 'adblock_conf', 40,
            fn() => defined('RASPI_ADBLOCK_ENABLED') && RASPI_HOTSPOT_ENABLED && !$_SESSION["bridgedEnabled"] 
        );
        $this->addItem(_('Networking'), 'fas fa-network-wired', 'network_conf', 50,
            fn() => defined('RASPI_NETWORK_ENABLED') && RASPI_NETWORK_ENABLED 
        );
        $this->addItem(_('WiFi client'), 'fas fa-wifi', 'wpa_conf', 60,
            fn() => defined('RASPI_WIFICLIENT_ENABLED') && RASPI_WIFICLIENT_ENABLED && !$_SESSION["bridgedEnabled"]
        );
        $this->addItem(_('OpenVPN'), 'fas fa-key', 'openvpn_conf', 70,
            fn() => defined('RASPI_OPENVPN_ENABLED') && RASPI_OPENVPN_ENABLED
        );
        $this->addItem(_('WireGuard'), 'ra-wireguard', 'wg_conf', 80,
            fn() => defined('RASPI_WIREGUARD_ENABLED') && RASPI_WIREGUARD_ENABLED
        );
        $this->addItem(_(getProviderValue($_SESSION["providerID"], "name")), 'fas fa-shield-alt', 'provider_conf', 90,
            fn() => defined('RASPI_VPN_PROVIDER_ENABLED') && RASPI_VPN_PROVIDER_ENABLED
        );
         $this->addItem(_('Authentication'), 'fas fa-user-lock', 'auth_conf', 100,
            fn() => defined('RASPI_CONFAUTH_ENABLED') && RASPI_CONFAUTH_ENABLED 
        );
        $this->addItem(_('Data usage'), 'fas fa-chart-area', 'data_use', 110,
            fn() => defined('RASPI_VNSTAT_ENABLED') && RASPI_VNSTAT_ENABLED
        );
        $this->addItem(_('RestAPI'), 'fas fa-puzzle-piece', 'restapi_conf', 120,
            fn() => defined('RASPI_VNSTAT_ENABLED') && RASPI_VNSTAT_ENABLED
        );
        $this->addItem(_('System'), 'fas fa-cube', 'data_use', 130,
            fn() => defined('RASPI_SYSTEM_ENABLED') && RASPI_SYSTEM_ENABLED
        );
        $this->addItem(_('About RaspAP'), 'fas fa-info-circle', 'about', 140);
    }

    /**
     * Adds an item to the sidebar
     * @param string $label
     * @param string $iconClass
     * @param string $link
     * @param int $priority
     * @param callable $condition
     */
    public function addItem(string $label, string $iconClass, string $link, int $priority, callable $condition = null) {
        $this->items[] = [
            'label' => $label,
            'icon' => $iconClass,
            'link' => $link,
            'priority' => $priority,
            'condition' => $condition
        ];
    }

    public function getItems(): array {
        // Sort items by priority and filter by enabled condition
        $filteredItems = array_filter($this->items, function ($item) {
            return $item['condition'] === null || call_user_func($item['condition']);
        });
        usort($filteredItems, function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        return $filteredItems;
    }

    public function render() {
        foreach ($this->getItems() as $item) {
            echo "<div class=\"sb-nav-link-icon px-2\">
                  <a class=\"nav-link\" href=\"{$item['link']}\">
                    <i class=\"sb-nav-link-icon {$item['icon']} fa-fw mr-2\"></i>
                    <span class=\"nav-label\">{$item['label']}</span>
                  </a>
                  </div>";
        }
    }
}
