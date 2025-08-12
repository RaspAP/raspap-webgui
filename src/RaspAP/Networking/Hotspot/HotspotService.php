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
        'ac' => '802.11ac - 5 GHz'
    ];

    // encryption types
    private const ENC_TYPES = [
        'TKIP'       => 'TKIP',
        'CCMP'       => 'CCMP',
        'TKIP CCMP'  => 'TKIP+CCMP'
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
     * Returns translated security modes.
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

        if ($validated !== false) {
            try {
                // normalize state flags
                $validated['interface'] = $apIface;
                $validated['bridge']    = !empty($states['BridgedEnable']);
                $validated['apsta']     = !empty($states['WifiAPEnable']);
                $validated['repeater']  = !empty($states['RepeaterEnable']);
                $validated['dualmode']  = !empty($states['DualAPEnable']);
                $validated['txpower']   = $post_data['txpower'];

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
                try {
                    $return = $this->dhcpcd->buildConfig(
                        $validated['interface'],
                        $validated['bridge'],
                        $validated['repeater'],
                        $validated['apsta'],
                        $validated['dualmode'],
                        $status,
                    );
                } catch (\RuntimeException $e) {
                    error_log('Error: ' . $e->getMessage());
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
     */
    public function getRegDomain(): string
    {
        $domain = shell_exec("iw reg get | grep -o 'country [A-Z]\{2\}' | awk 'NR==1{print $2}'");
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

