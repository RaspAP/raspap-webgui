<?php

define('RASPI_BRAND_TEXT', 'RaspAP');
define('RASPI_CONFIG', '/etc/raspap');
define('RASPI_CONFIG_NETWORK', RASPI_CONFIG.'/networking/defaults.json');
define('RASPI_ADMIN_DETAILS', RASPI_CONFIG.'/raspap.auth');
define('RASPI_WIFI_AP_INTERFACE', 'wlan0');
define('RASPI_CACHE_PATH', sys_get_temp_dir() . '/raspap');

// Constants for configuration file paths.
// These are typical for default RPi installs. Modify if needed.
define('RASPI_DNSMASQ_LEASES', '/var/lib/misc/dnsmasq.leases');
define('RASPI_DNSMASQ_PREFIX', '/etc/dnsmasq.d/090_');
define('RASPI_ADBLOCK_LISTPATH', '/etc/raspap/adblock/');
define('RASPI_ADBLOCK_CONFIG', RASPI_DNSMASQ_PREFIX.'adblock.conf');
define('RASPI_HOSTAPD_CONFIG', '/etc/hostapd/hostapd.conf');
define('RASPI_DHCPCD_CONFIG', '/etc/dhcpcd.conf');
define('RASPI_WPA_SUPPLICANT_CONFIG', '/etc/wpa_supplicant/wpa_supplicant.conf');
define('RASPI_HOSTAPD_CTRL_INTERFACE', '/var/run/hostapd');
define('RASPI_WPA_CTRL_INTERFACE', '/var/run/wpa_supplicant');
define('RASPI_OPENVPN_CLIENT_CONFIG', '/etc/openvpn/client/client.conf');
define('RASPI_OPENVPN_CLIENT_LOGIN', '/etc/openvpn/client/login.conf');
define('RASPI_OPENVPN_SERVER_CONFIG', '/etc/openvpn/server/server.conf');
define('RASPI_TORPROXY_CONFIG', '/etc/tor/torrc');
define('RASPI_LIGHTTPD_CONFIG', '/etc/lighttpd/lighttpd.conf');
define('RASPI_ACCESS_CHECK_IP', '1.1.1.1');
define('RASPI_ACCESS_CHECK_DNS', 'one.one.one.one');

// Constant for the 5GHz wireless regulatory domain
define('RASPI_5GHZ_ISO_ALPHA2', array('NL','US'));
define('RASPI_5GHZ_MAX_CHANNEL', 165);

// Optional services, set to true to enable.
define('RASPI_WIFICLIENT_ENABLED', true);
define('RASPI_HOTSPOT_ENABLED', true);
define('RASPI_NETWORK_ENABLED', true);
define('RASPI_DHCP_ENABLED', true);
define('RASPI_ADBLOCK_ENABLED', false);
define('RASPI_OPENVPN_ENABLED', false);
define('RASPI_TORPROXY_ENABLED', false);
define('RASPI_CONFAUTH_ENABLED', true);
define('RASPI_CHANGETHEME_ENABLED', true);
define('RASPI_VNSTAT_ENABLED', true);
define('RASPI_SYSTEM_ENABLED', true);
define('RASPI_MONITOR_ENABLED', false);

// Locale settings
define('LOCALE_ROOT', 'locale');
define('LOCALE_DOMAIN', 'messages');
