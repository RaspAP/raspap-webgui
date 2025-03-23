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
        $this->addItem(_('Hotspot'), 'fas fa-bullseye', 'hostapd_conf', 20,
            fn() => RASPI_HOTSPOT_ENABLED
        );
        $this->addItem(_('DHCP Server'), 'fas fa-exchange-alt', 'dhcpd_conf', 30,
            fn() => RASPI_DHCP_ENABLED && !$_SESSION["bridgedEnabled"]
        );
        $this->addItem(_('Ad Blocking'), 'far fa-hand-paper', 'adblock_conf', 40,
            fn() => RASPI_ADBLOCK_ENABLED && RASPI_HOTSPOT_ENABLED && !$_SESSION["bridgedEnabled"]
        );
        $this->addItem(_('Networking'), 'fas fa-network-wired', 'network_conf', 50,
            fn() => RASPI_NETWORK_ENABLED
        );
        $this->addItem(_('WiFi client'), 'fas fa-wifi', 'wpa_conf', 60,
            fn() => RASPI_WIFICLIENT_ENABLED && !$_SESSION["bridgedEnabled"]
        );
        $this->addItem(_('OpenVPN'), 'fas fa-key', 'openvpn_conf', 70,
            fn() => RASPI_OPENVPN_ENABLED
        );
        $this->addItem(_('WireGuard'), 'ra-wireguard', 'wg_conf', 80,
            fn() => RASPI_WIREGUARD_ENABLED
        );
        $this->addItem(_(getProviderValue($_SESSION["providerID"], "name")), 'fas fa-shield-alt', 'provider_conf', 90,
            fn() => RASPI_VPN_PROVIDER_ENABLED
        );
        $this->addItem(_('Data usage'), 'fas fa-chart-area', 'data_use', 110,
            fn() => RASPI_VNSTAT_ENABLED
        );
        $this->addItem(_('RestAPI'), 'fas fa-puzzle-piece', 'restapi_conf', 120,
            fn() => RASPI_RESTAPI_ENABLED
        );
        $this->addItem(_('System'), 'fas fa-cube', 'system_info', 130,
            fn() => RASPI_SYSTEM_ENABLED
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

