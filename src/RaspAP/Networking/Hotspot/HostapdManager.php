<?php

namespace RaspAP\Networking\Hotspot;

/**
 * Manages hostapd configurations and runtime settings
 */
class HostapdManager
{
    private const CONF_DEFAULT = RASPI_HOSTAPD_CONFIG;
    private const CONF_PATH_PREFIX = '/etc/hostapd/hostapd-';
    private const CONF_TMP = '/tmp/hostapddata';

    /**
     * Retrieves current hostapd config
     *
     * @return array
     */
    public function getConfig(): array
    {
        $configFile = SELF::CONF_DEFAULT;

        if (!file_exists($configFile)) {
            throw new \RuntimeException("hostapd config not found:  $configFile");
        }
        if (!is_readable($configFile)) {
            throw new \RuntimeException("Unable to read hostapd config: $configFile");
        }
        exec('cat ' . escapeshellarg($configFile), $hostapdconfig, $status);
        if ($status !== 0 || empty($hostapdconfig)) {
            throw new \RuntimeException("Failed to read hostapd config: $configFile");
        }
        //error_log("HostapdManager::getConfig() hostapdconfig =" . print_r($hostapdconfig, true));

        foreach ($hostapdconfig as $hostapdconfigline) {
            if (strlen($hostapdconfigline) === 0) {
                continue;
            }
            if ($hostapdconfigline[0] != "#") {
                $line = explode("=", $hostapdconfigline);
                $config[$line[0]]=$line[1];
            }
        };

        // assign beacon_int boolean if value is set
        if (isset($config['beacon_int'])) {
            $config['beacon_interval_bool'] = 1;
        }
        // assign disassoc_low_ack boolean if value is set
        if (isset($config['disassoc_low_ack'])) {
            $config['disassoc_low_ack_bool'] = 1;
        }
        // assign country_code from iw reg if not set in config
        if (empty($config['country_code']) && isset($country_code[0])) {
            $config['country_code'] = $country_code[0];
        }
        // map wpa_key_mgmt to security types
        if ($config['wpa_key_mgmt'] == 'WPA-PSK WPA-PSK-SHA256 SAE') {
            $config['wpa'] = 4;
        } elseif ($config['wpa_key_mgmt'] == 'SAE') {
            $config['wpa'] = 5;
        }
        $selectedHwMode = $config['hw_mode'];
        if (isset($config['ieee80211n'])) {
            if (strval($config['ieee80211n']) === '1') {
                $selectedHwMode = 'n';
            }
        }
        if (isset($config['ieee80211ac'])) {
            if (strval($config['ieee80211ac']) === '1') {
                $selectedHwMode = 'ac';
            }
        }
        if (isset($config['ieee80211w'])) {
            if (strval($config['ieee80211w']) === '2') {
                $selectedHwMode = 'w';
            }
        }
        $config['selected_hw_mode'] = $selectedHwMode;
        $config['ignore_broadcast_ssid'] ??= 0;
        $config['max_num_sta'] ??= 0;
        $config['wep_default_key'] ??= 0;

        return $config;

    }

    /**
     * Builds hostapd configuration text from array
     *
     * @param array $params
     * @return string
     */
    public function buildConfig(array $params): string
    {
        $config = [];
        $config[] = 'driver=nl80211';
        $config[] = 'ctrl_interface=' . RASPI_HOSTAPD_CTRL_INTERFACE;
        $config[] = 'ctrl_interface_group=0';
        $config[] = 'auth_algs=1';

        $wpa = $params['wpa'];
        $wpa_key_mgmt = 'WPA-PSK';

        if ($wpa == 4) {
            $config[] = 'ieee80211w=1';
            $wpa_key_mgmt = 'WPA-PSK WPA-PSK-SHA256 SAE';
            $wpa = 2;
        } elseif ($wpa == 5) {
            $config[] = 'ieee80211w=2';
            $wpa_key_mgmt = 'SAE';
            $wpa = 2;
        }

        if ($params['80211w'] == 1) {
            $config[] = 'ieee80211w=1';
            $wpa_key_mgmt = 'WPA-PSK';
        } elseif ($params['80211w'] == 2) {
            $config[] = 'ieee80211w=2';
            $wpa_key_mgmt = 'WPA-PSK-SHA256';
        }

        $config[] = 'wpa_key_mgmt=' . $wpa_key_mgmt;

        if (!empty($params['beacon_interval'])) {
            $config[] = 'beacon_int=' . $params['beacon_interval'];
        }

        if (!empty($params['disassoc_low_ack'])) {
            $config[] = 'disassoc_low_ack=0';
        }

        $config[] = 'ssid=' . $params['ssid'];
        $config[] = 'channel=' . $params['channel'];

        // Choose VHT segment index (fallback only if required)
        $vht_freq_idx = ($params['channel'] < RASPI_5GHZ_CHANNEL_MIN) ? 42 : 155;

        switch ($params['hw_mode']) {
            case 'n':
                $config[] = 'hw_mode=g';
                $config[] = 'ieee80211n=1';
                $config[] = 'wmm_enabled=1';
                break;
            case 'ac':
                $config[] = 'hw_mode=a';
                $config[] = '# N';
                $config[] = 'ieee80211n=1';
                $config[] = 'require_ht=1';
                $config[] = 'ht_capab=[MAX-AMSDU-3839][HT40+][SHORT-GI-20][SHORT-GI-40][DSSS_CCK-40]';
                $config[] = '# AC';
                $config[] = 'ieee80211ac=1';
                $config[] = 'require_vht=1';
                $config[] = 'ieee80211d=0';
                $config[] = 'ieee80211h=0';
                $config[] = 'vht_capab=[MAX-AMSDU-3839][SHORT-GI-80]';
                $config[] = 'vht_oper_chwidth=1';
                $config[] = 'vht_oper_centr_freq_seg0_idx=' . $vht_freq_idx;
                break;
            default:
                $config[] = 'hw_mode=' . $params['hw_mode'];
                $config[] = 'ieee80211n=0';
        }

        if ($params['wpa'] !== 'none') {
            $config[] = 'wpa_passphrase=' . $params['wpa_passphrase'];
        }

        if (!empty($params['bridge'])) {
            $config[] = 'interface=' . $params['interface'];
            $config[] = 'bridge=' . $params['bridge'];
        } else {
            $config[] = 'interface=' . $params['interface'];
        }

        $config[] = 'wpa=' . $wpa;
        $config[] = 'wpa_pairwise=' . $params['wpa_pairwise'];
        $config[] = 'country_code=' . $params['country_code'];
        $config[] = 'ignore_broadcast_ssid=' . $params['hiddenSSID'];
        if (!empty($params['max_num_sta'])) {
            $config[] = 'max_num_sta=' . (int)$params['max_num_sta'];
        }
        
        // Optional additional user config
        $config[] = parseUserHostapdCfg();

        return implode(PHP_EOL, $config) . PHP_EOL;
    }

    /**
     * Saves a hostapd configuration
     *
     * @param string $config, rendered hostapd.conf
     * @param string $interface, named interface
     * @param bool $dualMode, dual-band AP mode enabled
     * @param bool   $restart, option to restart hostapd@<iface> after save
     * @return bool
     * @throws \RuntimeException
     */
    public function saveConfig(string $config, bool $dualMode, string $iface, bool $restart = false): bool
    {
        $configFile = $this->resolveConfigPath($iface, $dualMode); 
        $tempFile   = SELF::CONF_TMP;


        if (file_put_contents($tempFile, $config) === false) {
            throw new \RuntimeException("Failed to write temp hostapd config");
        }

        exec(sprintf('sudo cp %s %s', escapeshellarg($tempFile), escapeshellarg($configFile)), $o, $status);
        if ($status !== 0) {
            throw new \RuntimeException("Failed to apply new hostapd config");
        }

        if ($restart) {
            $this->restartService($iface);
        }

        return true; 
    }

    /**
     * Sets transmit power for an interface
     *
     * @param string $iface
     * @param int|string $dbm
     * @return bool
     */
    public function setTxPower(string $iface, $dbm): bool
    {
        return false;
    }

    /**
     * Sets regulatory domain
     *
     * @param string $countryCode
     * @return bool
     */
    public function setRegDomain(string $countryCode): bool
    {
        return false;
    }

    /**
     * Parses optional /etc/hostapd/hostapd.conf.users file
     *
     * @return string $tmp
     */
    function parseUserHostapdCfg()
    {
        if (file_exists(CONF_DEFAULT . '.users')) {
            exec('cat '. CONF_DEFAULT . '.users', $hostapdconfigusers);
            foreach ($hostapdconfigusers as $hostapdconfigusersline) {
                if (strlen($hostapdconfigusersline) === 0) {
                    continue;
                }
                if ($hostapdconfigusersline[0] != "#") {
                    $arrLine = explode("=", $hostapdconfigusersline);
                    $tmp.= $arrLine[0]."=".$arrLine[1].PHP_EOL;;
                }
            }
            return $tmp;
        }
    }

    /**
     * Determines the hostapd config file for a given interface
     *
     * @param string $iface
     * @param bool $dualMode
     * @return string
     */
    private function resolveConfigPath(string $iface, bool $dualMode): string
    {
        if ($dualMode) {
            return SELF::CONF_PATH_PREFIX . $iface . '.conf';
        }
        // primary interface uses the canonical config path
        return self::CONF_DEFAULT;
    }

    /**
     * Restarts hostapd systemd instance
     *
     * @param string $iface
     * @throws \RuntimeException
     */
    private function restartService(string $iface): void
    {
        // sanitize 
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $iface)) {
            throw new \RuntimeException("Invalid interface name: $iface");
        }

        // use instance unit (preferred) if available
        $cmds = [
            sprintf('sudo systemctl restart hostapd@%s', $iface),
            // fallback to singleton service
            'sudo systemctl restart hostapd.service'
        ];

        foreach ($cmds as $cmd) {
            exec($cmd, $out, $rc);
            if ($rc === 0) {
                return;
            }
        }

        throw new \RuntimeException("Failed to restart hostapd (tried instance + fallback).");
    }

    /**
     * Persists options to /etc/raspap/
     *
     * @param string $apIface
     * @param bool   $logEnable
     * @param bool   $bridgedEnable
     * @param bool   $cfgWifiAPEnable
     * @param bool   $wifiAPEnable
     * @param bool   $repeaterEnable
     * @param string $cliIface
     * @return bool
     */
    public function persistHostapdIni($apIface, $logEnable, $bridgedEnable, $cfgWifiAPEnable, $wifiAPEnable, $repeaterEnable, $cliIface): bool
    {
        $cfg = [];
        $cfg['WifiInterface'] = $apIface;
        $cfg['LogEnable'] = $logEnable;
        $cfg['WifiAPEnable'] = ($bridgedEnable == 1 ? $cfgWifiAPEnable : $wifiAPEnable);
        $cfg['BridgedEnable'] = $bridgedEnable;
        $cfg['RepeaterEnable'] = $repeaterEnable;
        $cfg['WifiManaged'] = $cliIface;
        $success = write_php_ini($cfg, RASPI_CONFIG.'/hostapd.ini');
        if (!$success) {
            throw new \RuntimeException("Unable to write to hostapd.ini");
        }
        return true; 
    }

}


