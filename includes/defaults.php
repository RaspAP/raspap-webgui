<?php

if (!defined('RASPI_CONFIG')) {
    define('RASPI_CONFIG', '/etc/raspap');
}

$defaults = [
  'RASPI_BRAND_TEXT' => 'RaspAP',
  'RASPI_VERSION' => '2.6',
  'RASPI_CONFIG_NETWORK' => RASPI_CONFIG.'/networking/defaults.json',
  'RASPI_ADMIN_DETAILS' => RASPI_CONFIG.'/raspap.auth',
  'RASPI_WIFI_AP_INTERFACE' => 'wlan0',
  'RASPI_CACHE_PATH' => sys_get_temp_dir() . '/raspap',

  // Constants for configuration file paths.
  // These are typical for default RPi installs. Modify if needed.
  'RASPI_DNSMASQ_LEASES' => '/var/lib/misc/dnsmasq.leases',
  'RASPI_DNSMASQ_PREFIX' => '/etc/dnsmasq.d/090_',
  'RASPI_ADBLOCK_LISTPATH' => '/etc/raspap/adblock/',
  'RASPI_ADBLOCK_CONFIG' =>  RASPI_DNSMASQ_PREFIX.'adblock.conf',
  'RASPI_HOSTAPD_CONFIG' => '/etc/hostapd/hostapd.conf',
  'RASPI_DHCPCD_CONFIG' => '/etc/dhcpcd.conf',
  'RASPI_WPA_SUPPLICANT_CONFIG' => '/etc/wpa_supplicant/wpa_supplicant.conf',
  'RASPI_HOSTAPD_CTRL_INTERFACE' => '/var/run/hostapd',
  'RASPI_WPA_CTRL_INTERFACE' => '/var/run/wpa_supplicant',
  'RASPI_OPENVPN_CLIENT_CONFIG' => '/etc/openvpn/client/client.conf',
  'RASPI_OPENVPN_CLIENT_LOGIN' => '/etc/openvpn/client/login.conf',
  'RASPI_OPENVPN_SERVER_CONFIG' => '/etc/openvpn/server/server.conf',
  'RASPI_TORPROXY_CONFIG' => '/etc/tor/torrc',
  'RASPI_LIGHTTPD_CONFIG' => '/etc/lighttpd/lighttpd.conf',
  'RASPI_ACCESS_CHECK_IP' => '1.1.1.1',
  'RASPI_ACCESS_CHECK_DNS' => 'one.one.one.one',

  // Constants for the 5GHz wireless regulatory domain
  'RASPI_5GHZ_ISO_ALPHA2' => array('NL','US'),
  'RASPI_5GHZ_MAX_CHANNEL' => 165,

  // Optional services, set to true to enable.
  'RASPI_WIFICLIENT_ENABLED' => true,
  'RASPI_HOTSPOT_ENABLED' => true,
  'RASPI_NETWORK_ENABLED' => true,
  'RASPI_DHCP_ENABLED' => true,
  'RASPI_ADBLOCK_ENABLED' => false,
  'RASPI_OPENVPN_ENABLED' => false,
  'RASPI_TORPROXY_ENABLED' => false,
  'RASPI_CONFAUTH_ENABLED' => true,
  'RASPI_CHANGETHEME_ENABLED' => true,
  'RASPI_VNSTAT_ENABLED' => true,
  'RASPI_SYSTEM_ENABLED' => true,
  'RASPI_MONITOR_ENABLED' => false,

  // Locale settings
  'LOCALE_ROOT' => 'locale',
  'LOCALE_DOMAIN' => 'messages'
];

foreach ($defaults as $setting => $value) {
    if (!defined($setting)) {
        define($setting, $value);
    }
}

unset($defaults);
