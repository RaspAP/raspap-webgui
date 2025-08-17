<?php

/**
 * A hostapd manager class for RaspAP
 *
 * @description Manages hostapd configurations and runtime settings
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking\Hotspot;

use RaspAP\Networking\Hotspot\Validators\HostapdValidator;
use RaspAP\Messages\StatusMessage;

class HostapdManager
{
    private const CONF_DEFAULT = RASPI_HOSTAPD_CONFIG;
    private const CONF_PATH_PREFIX = '/etc/hostapd/hostapd-';
    private const CONF_TMP = '/tmp/hostapddata';

    /** @var HostapdValidator */
    private $validator;

    public function __construct(?HostapdValidator $validator = null)
    {
        $this->validator = $validator ?: new HostapdValidator();
    }

    /**
     * Retrieves current hostapd config
     *
     * @return array
     * @throws \RuntimeException
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
        $config['selected_hw_mode'] = $this->resolveHwMode($config);
        $config['ignore_broadcast_ssid'] ??= 0;
        $config['max_num_sta'] ??= 0;
        $config['wep_default_key'] ??= 0;

        return $config;
    }

    /**
     * Determines the selected hardware mode based on config
     *
     * @param array $config
     * @return string
     */
    private function resolveHwMode(array $config): string
    {
        $selected = $config['hw_mode'] ?? 'g'; // default fallback

        if (!empty($config['ieee80211n']) && strval($config['ieee80211n']) === '1') {
            $selected = 'n';
        }
        if (!empty($config['ieee80211ac']) && strval($config['ieee80211ac']) === '1') {
            $selected = 'ac';
        }
        if (!empty($config['ieee80211w']) && strval($config['ieee80211w']) === '2') {
            $selected = 'w';
        }

        return $selected;
    }

    /**
     * Validates a hostapd configuration
     *
     * @param array $post            raw $_POST object
     * @param array $wpaArray        allowed WPA values
     * @param array $encTypes        allowed encryption types
     * @param array $modes           allowed hardware modes
     * @param array $interfaces      valid interface list
     * @param string $regDomain      regulatory domain
     * @param StatusMessage $status  Status message collector
     * @return array|false           validated configuration array or false on failure
     */
    public function validate(
        array $post,
        array $wpaArray,
        array $encTypes,
        array $modes,
        array $interfaces,
        string $regDomain,
        StatusMessage $status
    ) {
        return $this->validator->validate($post, $wpaArray, $encTypes, $modes, $interfaces, $regDomain, $status);
    }

    /**
     * Builds hostapd configuration text from array
     *
     * @param array         $params
     * @param StatusMessage $status
     * @return string
     */
    public function buildConfig(array $params, StatusMessage $status): string
    {
        $config = [];

        // core static values
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
            $config[] = 'beacon_int=' . intval($params['beacon_interval']);
        }

        if (!empty($params['disassoc_low_ack'])) {
            $config[] = 'disassoc_low_ack=0';
        }

        // SSID and channel (required)
        $config[] = 'ssid=' . $params['ssid'];
        $config[] = 'channel=' . $params['channel'];

        // choose VHT segment index (fallback only if required)
        $vht_freq_idx = ($params['channel'] < RASPI_5GHZ_CHANNEL_MIN) ? 42 : 155;
        $hwMode = isset($params['hw_mode']) ? $params['hw_mode'] : '';

        // fetch settings for selected mode
        $modeSettings = getDefaultNetOpts('hostapd', 'modes', $hwMode);
        $settings = $modeSettings[$hwMode]['settings'] ?? [];

        if (!empty($settings)) {
            foreach ($settings as $line) {
                if (!is_string($line)) {
                    continue;
                }
                $replaced = str_replace('{VHT_FREQ_IDX}', (string) $vht_freq_idx ?? '',$line);
                $config[] = $replaced;
            }
        }

        // WPA passphrase
        if ($wpa_numeric !== 'none' && !empty($params['wpa_passphrase'])) {
            $config[] = 'wpa_passphrase=' . $params['wpa_passphrase'];
        }

        // bridge handling
        if (!empty($params['bridge'])) {
            $config[] = 'interface=' . $params['interface'];
            $config[] = 'bridge=' . $params['bridge'];
        } else {
            $config[] = 'interface=' . $params['interface'];
        }

        $config[] = 'wpa=' . $wpa;
        $config[] = 'wpa_pairwise=' . ($params['wpa_pairwise'] ?? '');
        $config[] = 'country_code=' . ($params['country_code'] ?? '');
        $config[] = 'ignore_broadcast_ssid=' . ($params['hiddenSSID'] ?? 0);

        if (!empty($params['max_num_sta'])) {
            $config[] = 'max_num_sta=' . (int)$params['max_num_sta'];
        }

        // optional additional user config
        $config[] = $this->parseUserHostapdCfg();

        return implode(PHP_EOL, array_filter($config, function ($v) { return $v !== null && $v !== ''; })) . PHP_EOL;
    }

    /**
     * Saves a hostapd configuration
     *
     * @param string $config, rendered hostapd.conf
     * @param string $interface, named interface
     * @param bool   $dualMode, dual-band AP mode enabled
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
     * Derives mode checkbox states from POST + existing ini
     *
     * @param array  $post raw $_POST
     * @param array  $currentIni parsed hostapd.ini
     * @return array normalized states
     */
    public function deriveModeStates(array $post, array $currentIni): array
    {
        $prevWifiAPEnable = (int)($currentIni['WifiAPEnable'] ?? 0);
        $bridgedEnable  = isset($post['bridgedEnable'])  ? 1 : 0;
        $repeaterEnable = 0;
        $wifiAPEnable   = 0;

        if ($bridgedEnable === 0) {
            // only meaningful when not bridged
            $repeaterEnable = isset($post['repeaterEnable']) ? 1 : 0;
            $wifiAPEnable   = isset($post['wifiAPEnable'])   ? 1 : 0;
        }

        $logEnable = isset($post['logEnable']) ? 1 : 0;
        $effectiveWifiAPEnable = $bridgedEnable === 1 ? $prevWifiAPEnable : $wifiAPEnable;

        return [
            'BridgedEnable'  => $bridgedEnable,
            'RepeaterEnable' => $repeaterEnable,
            'WifiAPEnable'   => $effectiveWifiAPEnable,
            'LogEnable'      => $logEnable
        ];
    }

    /**
     * Determine AP interface, client (managed) interface and session/monitor interface
     * Uses these semantics:
     *  - Base interface = user selection (validated) or RASPI_WIFI_AP_INTERFACE
     *  - AP-STA mode (WifiAPEnable=1): AP is 'uap0', client is base iface
     *  - Bridged mode: client/session use 'br0', AP remains base iface
     *
     * @param string $baseIface Selected interface from form
     * @param array $states Output from deriveModeStates()
     * @return array [ap_iface, cli_iface, session_iface]
     */
    public function deriveInterfaces(string $baseIface, array $states): array
    {
        $apIface      = $baseIface;
        $cliIface     = $baseIface;
        $sessionIface = $baseIface;

        if ($states['WifiAPEnable'] === 1 && $states['BridgedEnable'] === 0) {
            // client AP (AP-STA) – uap0 is AP, base iface remains client
            $apIface      = 'uap0';
            $sessionIface = 'uap0';
            $cliIface     = $baseIface;
        } elseif ($states['BridgedEnable'] === 1) {
            // bridged mode – monitor br0, AP stays as base wireless iface
            $cliIface     = 'br0';
            $sessionIface = 'br0';
        }

        return [$apIface, $cliIface, $sessionIface];
    }

    /**
     * Enables or disables hostapd logging
     *
     * @param int $logEnable
     */
    private function handleLogState(int $logEnable): void
    {
        $script = $logEnable === 1 ? 'enablelog.sh' : 'disablelog.sh';
        exec('sudo ' . RASPI_CONFIG . '/hostapd/' . $script);
    }

    /**
     * Parses optional /etc/hostapd/hostapd.conf.users file
     *
     * @return string $tmp
     */
    private function parseUserHostapdCfg()
    {
        if (file_exists(SELF::CONF_DEFAULT . '.users')) {
            exec('cat '. SELF::CONF_DEFAULT . '.users', $hostapdconfigusers);
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
     * Persist hostapd.ini with mode / interface user settings
     *
     * @param array $states states from deriveModeStates()
     * @param string $apIface the AP interface
     * @param string $cliIface the managed interface
     * @param array $previousIni existing ini
     * @return bool
     */
    public function persistHostapdIni(array $states, string $apIface, string $cliIface, array $previousIni = []): bool
    {
        $this->applyLogState($states['LogEnable']);

        // compose new ini payload
        $cfg = [
            'WifiInterface'  => $apIface,
            'LogEnable'      => $states['LogEnable'] ?? false,
            'WifiAPEnable'   => $states['WifiAPEnable'] ?? false,
            'BridgedEnable'  => $states['BridgedEnable'] ?? false,
            'RepeaterEnable' => $states['RepeaterEnable'] ?? false,
            'DualAPEnable'   => $states['DualAPEnable'] ?? false,
            'WifiManaged'    => $cliIface
        ];
        foreach ($previousIni as $k => $v) {
            if (!array_key_exists($k, $cfg)) {
                $cfg[$k] = $v;
            }
        }
        return write_php_ini($cfg, RASPI_CONFIG . '/hostapd.ini');
    }

    /**
     * Enables or disables hostapd logging
     *
     * @param int $logEnable 1 = enable, 0 = disable
     */
    private function applyLogState(int $logEnable): void
    {
        $script = $logEnable === 1 ? 'enablelog.sh' : 'disablelog.sh';
        exec('sudo ' . RASPI_CONFIG . '/hostapd/' . $script);
    }

    /**
     * Returns a count of hostapd-<interface>.conf files
     *
     * @return int 
     */
    private function countHostapdConfigs(): int
    {
        $configs = glob('/etc/hostapd/hostapd-*.conf');
        return is_array($configs) ? count($configs) : 0;
    }

}

