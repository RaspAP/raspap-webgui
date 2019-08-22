<?php

define('RASPI_VERSION', '1.6');
define('RASPI_CONFIG', '/etc/raspap');
define('RASPI_CONFIG_NETWORKING', RASPI_CONFIG.'/networking');
define('RASPI_ADMIN_DETAILS', RASPI_CONFIG.'/raspap.auth');
define('RASPI_WIFI_CLIENT_INTERFACE', 'wlan0');
define('RASPI_CACHE_PATH', sys_get_temp_dir() . '/raspap');

// Constants for configuration file paths.
// These are typical for default RPi installs. Modify if needed.
define('RASPI_DNSMASQ_CONFIG', '/etc/dnsmasq.conf');
define('RASPI_DNSMASQ_LEASES', '/var/lib/misc/dnsmasq.leases');
define('RASPI_HOSTAPD_CONFIG', '/etc/hostapd/hostapd.conf');
define('RASPI_DHCPCD_CONFIG', '/etc/dhcpcd.conf');
define('RASPI_WPA_SUPPLICANT_CONFIG', '/etc/wpa_supplicant/wpa_supplicant.conf');
define('RASPI_HOSTAPD_CTRL_INTERFACE', '/var/run/hostapd');
define('RASPI_WPA_CTRL_INTERFACE', '/var/run/wpa_supplicant');
define('RASPI_OPENVPN_CLIENT_CONFIG', '/etc/openvpn/client.conf');
define('RASPI_OPENVPN_SERVER_CONFIG', '/etc/openvpn/server.conf');
define('RASPI_TORPROXY_CONFIG', '/etc/tor/torrc');
define('RASPI_CUSTOM_CONFIG', '/etc/raspap/custom.ini');

// Optional services, set to true to enable.
define('RASPI_WIFICLIENT_ENABLED', true);
define('RASPI_HOTSPOT_ENABLED', true);
define('RASPI_NETWORK_ENABLED', true);
define('RASPI_DHCP_ENABLED', true);
define('RASPI_OPENVPN_ENABLED', false);
define('RASPI_TORPROXY_ENABLED', false);
define('RASPI_CONFAUTH_ENABLED', true);
define('RASPI_CHANGETHEME_ENABLED', true);
define('RASPI_VNSTAT_ENABLED', true);
define('RASPI_CUSTOM_ENABLED', true);

// Locale settings
define('LOCALE_ROOT', 'locale');
define('LOCALE_DOMAIN', 'messages');


// Custom Configuration Parameters
define('RASPI_CUSTOM_FIELDS', array(
    'Network Proxy Configuration' => array(
        'host' => 'Network Proxy Host',
        'port' => 'Network Proxy Port', 
        'user' => 'Network Proxy User', 
        'pass' => 'Network Proxy Pass'),
));
