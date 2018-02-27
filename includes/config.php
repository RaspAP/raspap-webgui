<?php

define('RASPI_CONFIG', '/etc/raspap');
define('RASPI_CONFIG_NETWORKING',RASPI_CONFIG.'/networking');
define('RASPI_ADMIN_DETAILS', RASPI_CONFIG.'/raspap.auth');
define('RASPI_WIFI_CLIENT_INTERFACE', 'wlan0');

// Constants for configuration file paths.
// These are typical for default RPi installs. Modify if needed.
define('RASPI_DNSMASQ_CONFIG', '/etc/dnsmasq.conf');
define('RASPI_DNSMASQ_LEASES', '/var/lib/misc/dnsmasq.leases');
define('RASPI_HOSTAPD_CONFIG', '/etc/hostapd/hostapd.conf');
define('RASPI_WPA_SUPPLICANT_CONFIG', '/etc/wpa_supplicant/wpa_supplicant.conf');
define('RASPI_HOSTAPD_CTRL_INTERFACE', '/var/run/hostapd');
define('RASPI_WPA_CTRL_INTERFACE', '/var/run/wpa_supplicant');
define('RASPI_OPENVPN_CLIENT_CONFIG', '/etc/openvpn/client.conf');
define('RASPI_OPENVPN_SERVER_CONFIG', '/etc/openvpn/server.conf');
define('RASPI_TORPROXY_CONFIG', '/etc/tor/torrc');

//Page title text
define('RASPI_PAGETITLE_NAME', 'Raspbian WiFi Configuration Portal');

//Menu navbar text
define('RASPI_NAVBAR_NAME', 'RaspAP Wifi Portal v1.3.0');

// Optional services, set to true to enable.
define('RASPI_DASHBOARD_ENABLED', true );
define('RASPI_CUSTOMPAGE1_ENABLED', false );
define('RASPI_CLIENT_ENABLED', true );
define('RASPI_HOTSPOT_ENABLED', true );
define('RASPI_NETWORK_ENABLED', true );
define('RASPI_DHCP_ENABLED', true );
define('RASPI_CUSTOMPAGE2_ENABLED', false );
define('RASPI_OPENVPN_ENABLED', false );
define('RASPI_TORPROXY_ENABLED', false );
define('RASPI_CONFAUTH_ENABLED', true );
define('RASPI_CHANGETHEME_ENABLED', true );
define('RASPI_SYSTEM_ENABLED', true );

// Image and text to display in the custom page header
define('RASPI_CUSTOMHEADER_ENABLED', false );
define('RASPI_CUSTOMHEADERIMAGE_NAME', 'custom_logo.png');
define('RASPI_CUSTOMHEADERTEXT_NAME', 'Custom config');

// Custom configuration page names and icons
// Display names for custom pages
define('RASPI_CUSTOMPAGE1_NAME', 'Settings');
define('RASPI_CUSTOMPAGE2_NAME', 'Advanced');
// Icon names for custom page menu items.  These names represent characters from the font "fontawesome-webfont"
// The full list of available icon names is in bower_components/font-awesome/css/font-awesome.css
// The font is stored in the directory bower_components/font-awesome/fonts
define('RASPI_CUSTOMPAGE1_ICON', 'fa-gear');
define('RASPI_CUSTOMPAGE2_ICON', 'fa-star');

?>
