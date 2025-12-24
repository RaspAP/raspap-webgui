<?php

/**
 * A class to manage hotspot service configurations
 *
 * Consolidates:
 * hostapd, dnsmasq and dhcpcd config updates
 * dhcpcd interface adjustments
 * service control (start/stop/restart)
 *
 * @description Manages wireless configurations and services
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking\Hotspot;

use RaspAP\Networking\Hotspot\Validators\HostapdValidator;
use RaspAP\Messages\StatusMessage;

class HotspotService
{
    protected HostapdManager $hostapd;
    protected DnsmasqManager $dnsmasq;
    protected DhcpcdManager $dhcpcd;

    // IEEE 802.11 standards
    private const IEEE_80211_STANDARD = [
        'a'  => '802.11a - 5 GHz',
        'b'  => '802.11b - 2.4 GHz',
        'g'  => '802.11g - 2.4 GHz',
        'n'  => '802.11n - 2.4/5 GHz',
        'ac' => '802.11ac - 5 GHz',
        'ax' => '802.11ax - 2.4/5/6 GHz',
        'be' => '802.11be - 2.4/5/6 GHz'
    ];

    // encryption types
    private const ENC_TYPES = [
        'TKIP'       => 'TKIP',
        'CCMP'       => 'CCMP',
        'TKIP CCMP'  => 'TKIP+CCMP'
    ];

    // 802.11ax (Wi-Fi 6) channel widths
    private const HE_CHANNEL_WIDTHS = [
        0 => '20/40 MHz',
        1 => '80 MHz',
        2 => '160 MHz'
    ];

    // 802.11be (Wi-Fi 7) channel widths
    private const EHT_CHANNEL_WIDTHS = [
        0 => '20 MHz',
        1 => '40 MHz',
        2 => '80 MHz',
        3 => '160 MHz',
        4 => '320 MHz (6 GHz only)'
    ];

    public function __construct()
    {
        $this->hostapd = new HostapdManager();
        $this->dnsmasq = new DnsmasqManager();
        $this->dhcpcd  = new DhcpcdManager();
    }

    /**
     * Returns IEEE 802.11 standards
     */
    public static function get80211Standards(): array
    {
        return self::IEEE_80211_STANDARD;
    }

    /**
     * Returns encryption types
     */
    public static function getEncTypes(): array
    {
        return self::ENC_TYPES;
    }

    /**
     * Returns 802.11ax (Wi-Fi 6) channel widths
     */
    public static function getHeChannelWidths(): array
    {
        return self::HE_CHANNEL_WIDTHS;
    }

    /**
     * Returns 802.11be (Wi-Fi 7) channel widths
     */
    public static function getEhtChannelWidths(): array
    {
        return self::EHT_CHANNEL_WIDTHS;
    }

    /**
     * Returns translated security modes
     */
    public static function getSecurityModes(): array
    {
        // Build each call to ensure translation occurs under current locale.
        return [
            1      => 'WPA',
            2      => 'WPA2',
            3      => _('WPA and WPA2'),
            4      => _('WPA2 and WPA3-Personal (transitional mode)'),
            5      => 'WPA3-Personal (required)',
            'none' => _('None'),
        ];
    }

    /**
     * Returns translated 802.11w options
     */
    public static function get80211wOptions(): array
    {
        return [
            3 => _('Disabled'),
            1 => _('Enabled (for supported clients)'),
            2 => _('Required (for supported clients)'),
        ];
    }

    /**
     * Validates user input + saves configs for hostapd, dnsmasq & dhcp
     *
     * @param array         $wpa_array
     * @param array         $enc_types
     * @param array         $modes
     * @param array         $interfaces
     * @param string        $reg_domain
     * @param StatusMessage $status
     * @return bool
     */
    public function saveSettings(
        array $post_data,
        array $wpa_array,
        array $enc_types,
        array $modes,
        array $interfaces,
        string $reg_domain,
        StatusMessage $status): bool
    {
        $arrHostapdConf = $this->getHostapdIni();
        $dualAPEnable = false;

        // derive mode states
        $states = $this->hostapd->deriveModeStates($post_data, $arrHostapdConf);

        // determine base interface (validated or fallback)
        $baseIface = validateInterface($post_data['interface']) ? $post_data['interface'] : RASPI_WIFI_AP_INTERFACE;

        // derive interface roles
        [$apIface, $cliIface, $sessionIface] = $this->hostapd->deriveInterfaces($baseIface, $states);

        // persist hostapd.ini
        $this->hostapd->persistHostapdIni($states, $apIface, $cliIface, $arrHostapdConf);

        // store session (compatibility)
        $_SESSION['ap_interface'] = $sessionIface;

        // validate config from post data
        $validated = $this->hostapd->validate($post_data, $wpa_array, $enc_types, $modes, $interfaces, $reg_domain, $status);

        if ($validated === false) {
            $status->addMessage('Unable to save WiFi hotspot settings due to validation errors', 'danger');
            return false;
        }

        try {
            // normalize state flags
            $validated['interface'] = $apIface;
            $validated["bridgeName"] = !empty($states["BridgedEnable"]) ? "br0" : null;
            $validated['bridge']    = !empty($states['BridgedEnable']);
            $validated['apsta']     = !empty($states['WifiAPEnable']);
            $validated['repeater']  = !empty($states['RepeaterEnable']);
            $validated['dualmode']  = !empty($states['DualAPEnable']);
            $validated['txpower']   = $post_data['txpower'];

            // add 802.11ax/be specific parameters if present
            if (in_array($validated['hw_mode'], ['ax', 'be'])) {
                if ($validated['wpa'] < 4 && $validated['hw_mode'] === 'be') {
                    $status->addMessage('Note: WiFi 7 works best with WPA3 security', 'info');
                }
            }

            // hostapd
            $config = $this->hostapd->buildConfig($validated, $status);
            $this->hostapd->saveConfig($config, $dualAPEnable, $validated['interface']);
            $this->maybeSetRegDomain($post_data['country_code'], $status);

            $status->addMessage('WiFi hotspot settings saved.', 'success');

            // dnsmasq
            try {
                $syscfg = $this->dnsmasq->getConfig($validated['interface'] ?? RASPI_WIFI_AP_INTERFACE);
            } catch (\RuntimeException $e) {
                error_log('Error: ' . $e->getMessage());
            }

            try {
                $dnsmasqConfig = $this->dnsmasq->buildConfig(
                    $syscfg,
                    $validated['interface'],
                    $validated['apsta'],
                    $validated['bridge']
                );
                $this->dnsmasq->saveConfig($dnsmasqConfig, $validated['interface'], $status);
            } catch (\RuntimeException $e) {
                error_log('Error: ' . $e->getMessage());
            }

            // dhcpcd
            // pass bridge configuration if available
            try {
                $bridgeConfig = null;
                if ($validated['bridge'] && !empty($validated['bridgeStaticIp'])) {
                    $bridgeConfig = [
                        'staticIp'  => $validated['bridgeStaticIp'],
                        'netmask'   => $validated['bridgeNetmask'],
                        'gateway'   => $validated['bridgeGateway'],
                        'dns'       => $validated['bridgeDNS']
                    ];
                }

                $return = $this->dhcpcd->buildConfig(
                    $validated['interface'],
                    $validated['bridge'],
                    $validated['repeater'],
                    $validated['apsta'],
                    $validated['dualmode'],
                    $bridgeConfig,
                    $status,
                );
            } catch (\RuntimeException $e) {
                error_log('Error: ' . $e->getMessage());
                $status->addMessage('Error configuring DHCP: ' . $e->getMessage(), 'danger');
                return false;
            }

        } catch (\Throwable $e) {
            error_log(sprintf(
                "Error: %s in %s on line %d\nStack trace:\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            ));
            $status->addMessage('Unable to save WiFi hotspot settings', 'danger');
        }

        return true;
    }

    /**
     * Gets system hostapd.ini
     *
     * @return array $config
     */
    public function getHostapdIni(): array
    {
        $hostapdIni = RASPI_CONFIG . '/hostapd.ini';
        if (file_exists($hostapdIni)) {
            return parse_ini_file($hostapdIni) ?: [];
        }
        return [];
    }

    /**
     * Sets transmit power for an interface
     *
     * @param string $iface
     * @param int|string $dbm
     * @param StatusMessage $status
     * @return bool
     */
    public function maybeSetTxPower(string $iface, $dbm, StatusMessage $status): bool
    {
        $currentTxPower = $this->getTxPower($iface);

        if ($currentTxPower === $dbm) {
            return true;
        }

        if ($dbm === 'auto') {
            exec('sudo /sbin/iw dev ' . escapeshellarg($iface) . ' set txpower auto', $return);
            $status->addMessage('Setting transmit power to auto.', 'success');
        } else {
            $sdBm = (int)$dbm * 100;
            exec('sudo /sbin/iw dev ' . escapeshellarg($iface) . ' set txpower fixed ' . $sdBm, $return);
            $status->addMessage('Setting transmit power to ' . $dbm . ' dBm.', 'success');
        }
        return true;
    }

    /**
     * Gets transmit power for an interface
     *
     * @param string $iface
     * @return int
     */
    public function getTxPower(string $iface): int
    {
        $cmd = "iw dev ".escapeshellarg($iface)." info | awk '$1==\"txpower\" {print $2}'";
        exec($cmd, $txpower);
        return intval($txpower[0]);
    }

    /**
     * Sets a new regulatory domain if value has changed
     *
     * @param string $countryCode
     * @return bool
     */
    public function maybeSetRegDomain($countryCode, StatusMessage $status): bool
    {
        $currentDomain = $this->getRegDomain();
        if (trim($countryCode) !== trim($currentDomain)) {
            $result = $this->setRegDomain($countryCode, $status);
            if ($result !== true) {
                return false;
            }
        }
        return true;
    }

    /**
     * Gets the current regulatory domain
     *
     * @return string
     * @throws RuntimeException if unable to determine regulatory domain
     */
    public function getRegDomain(): string
    {
        $domain = shell_exec("iw reg get | grep -o 'country [A-Z]\{2\}' | awk 'NR==1{print $2}'");

        if ($domain === null) {
            throw new \RuntimeException('Failed to execute regulatory domain command');
        }

        $domain = trim($domain);

        if (empty($domain)) {
            throw new \RuntimeException('Unable to determine regulatory domain');
        }

        return $domain;
    }

    /**
     * Sets the specified wireless regulatory domain
     *
     * @param string $country_code ISO 2-letter country code
     * @param object $status       StatusMessage object
     * @return boolean $result
     */
    public function setRegDomain(string $country_code, StatusMessage $status): bool
    {
        $country_code = escapeshellarg($country_code);
        exec("sudo iw reg set $country_code", $output, $result);
        if ($result !== 0) {
            return false;
        } else {
            return true; 
        }
    }

    /**
     * Enumerates available network interfaces
     *
     * @return array $interfaces
     */
    public function getInterfaces(): array
    {
        exec("ip -o link show | awk -F': ' '{print $2}'", $interfaces);

        // filter out loopback, docker, bridges + other virtual interfaces 
        // that are incapable of hosting an AP
        $interfaces = array_filter($interfaces, function ($iface) {
            return !preg_match('/^(lo|docker|br-|veth|tun|tap|tailscale)/', $iface);
        });
        sort($interfaces);

        return array_values($interfaces);
    }

    /**
     * Retrieves hostapd service logs from systemd journal
     *
     * @param int $lines number of log lines to retrieve (default: 100, max: 1000)
     * @param bool $follow return command for real-time following (tbd)
     * @return array ['success' => bool, 'logs' => array, 'command' => string]
     */
    public function getHostapdLogs(int $lines = 100, bool $follow = false): array
    {
        // sanitize and limit line count
        $lines = max(1, min(1000, $lines));

        if ($follow) {
            return [
                'success' => true,
                'logs' => [],
                'command' => 'journalctl -u hostapd.service -f --no-pager'
            ];
        }

        $cmd = sprintf('sudo journalctl -u hostapd.service -n %d --no-pager 2>&1', $lines);
        exec($cmd, $output, $status);

        return [
            'success' => $status === 0,
            'logs' => $output,
            'line_count' => count($output)
        ];
    }

    /**
     * Starts services for given interface
     *
     * @param string $iface
     * @return bool
     */
    public function start(string $iface): bool
    {
        return false;
    }

    /**
     * Stops hotspot services
     *
     * @return bool
     */
    public function stop(): bool
    {
        return false;
    }

    /**
     * Restart hotspot services for given interface
     *
     * @param string $iface
     * @return bool
     */
    public function restart(string $iface): bool
    {
        return false;
    }

    /**
     * Get current hotspot status
     *
     * @return array
     */
    public function getStatus(): array
    {
        return [];
    }
}

