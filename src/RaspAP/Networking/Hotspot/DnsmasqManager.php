<?php

namespace RaspAP\Networking\Hotspot;

/**
 * Manages dnsmasq configuration for DHCP/DNS services
 */
class DnsmasqManager
{
    private const CONF_SUFFIX = '.conf';
    private const CONF_TMP = '/tmp/dnsmasqdata';

    /**
     * Retrieves dnsmasq configuration for an interface
     *
     * @param string $iface
     * @return array
     * @throws \RuntimeException
     */
    public function getConfig(string $iface): array
    {
        $configFile = RASPI_DNSMASQ_PREFIX . "$iface.conf";
        $lines = [];

        if (!file_exists($configFile)) {
            throw new \RuntimeException("dnsmasq config not found:  $configFile");
        }
        if (!is_readable($configFile)) {
            throw new \RuntimeException("Unable to read dnsmasq config: $configFile");
        }
        if (!function_exists('ParseConfig')) {
            throw new \RuntimeException("Unable to execute ParseConfig()");
        }

        exec('cat ' . escapeshellarg($configFile), $lines, $status);
        if ($status !== 0 || empty($lines)) {
            throw new \RuntimeException("Failed to read dnsmasq config for $iface");
        }

        $config = ParseConfig($lines);
        return $config;
    }

    /**
     * Builds a dnsmasq configuration
     * @param array   $syscfg
     * @param string  $iface
     * @param bool    $wifiAPEnable
     * @param bool    $bridgedEnable
     * @return array  $config
     * @throws \RuntimeException
     */
    public function buildConfig(array $syscfg, string $iface, bool $wifiAPEnable, bool $bridgedEnable): array
    {
        if ($wifiAPEnable == 1) {
            // Enable uap0 configuration for ap-sta mode
            // Set dhcp-range from system config, fallback to default if undefined
            $dhcp_range = ($syscfg['dhcp-range'] == '') ? getDefaultNetValue('dnsmasq','uap0','dhcp-range') : $syscfg['dhcp-range'];
            $config = [ '# RaspAP uap0 configuration' ];
            $config[] = 'interface=lo,uap0               # Enable uap0 interface for wireless client AP mode';
            $config[] = 'bind-dynamic                    # Hybrid between --bind-interfaces and default';
            $config[] = 'server=8.8.8.8                  # Forward DNS requests to Google DNS';
            $config[] = 'domain-needed                   # Don\'t forward short names';
            $config[] = 'bogus-priv                      # Never forward addresses in the non-routed address spaces';
            $config[] = 'dhcp-range='.$dhcp_range;
            if (!empty($syscfg['dhcp-option'])) {
                $config[] = 'dhcp-option='.$syscfg['dhcp-option'];
            }
            $config[] = PHP_EOL;
            scanConfigDir('/etc/dnsmasq.d/','uap0',$status);
        } elseif ($bridgedEnable !==1) {
            $dhcp_range = ($syscfg['dhcp-range'] =='') ? getDefaultNetValue('dnsmasq',$iface,'dhcp-range') : $syscfg['dhcp-range'];
            $config = [ '# RaspAP '.$_POST['interface'].' configuration' ];
            $config[] = 'interface='.$_POST['interface'];
            $config[] = 'domain-needed';
            $config[] = 'dhcp-range='.$dhcp_range;
            // handle multiple dhcp-host + option entries
            if (!empty($syscfg['dhcp-host'])) {
                if (is_array($syscfg['dhcp-host'])) {
                    foreach ($syscfg['dhcp-host'] as $host) {
                        $config[] = 'dhcp-host=' . $host;
                    }
                } else {
                    $config[] = 'dhcp-host=' . $syscfg['dhcp-host'];
                }
            }
            if (!empty($syscfg['dhcp-option'])) {
                if (is_array($syscfg['dhcp-option'])) {
                    foreach ($syscfg['dhcp-option'] as $opt) {
                        $config[] = 'dhcp-option=' . $opt;
                    }
                } else {
                    $config[] = 'dhcp-option=' . $syscfg['dhcp-option'];
                }
            }
            $config[] = PHP_EOL;
        }
        return $config;
    }

    /**
     * Saves dnsmasq configuration for an interface
     *
     * @param array $config
     * @param string $iface
     * @return bool
     */
    public function saveConfig(array $config, string $iface = self::DEFAULT_IFACE): bool
    {
        $configFile = RASPI_DNSMASQ_PREFIX . $iface . self::CONF_SUFFIX;
        $tempFile = SELF::CONF_TMP; 
        $config = join(PHP_EOL, $config);
        error_log('[DnsmasqManager::saveConfig] $config = ' . var_export($config, true));

        file_put_contents($tempFile, $config);
        $cmd = sprintf('sudo cp %s %s', escapeshellarg($tempFile), escapeshellarg($configFile));
        exec($cmd, $output, $status);
        if ($status !== 0) {
            throw new \RuntimeException("Failed to copy temp config to $configFile");
        }

        // reload dnsmasq to apply changes
        exec('sudo systemctl reload dnsmasq.service', $output, $status);
        if ($status !== 0) {
            throw new \RuntimeException("Failed to reload dnsmasq service");
        }

        return true;
    }

    /**
     * Add static DHCP lease
     *
     * @param string $iface
     * @param string $mac
     * @param string $ip
     * @param string|null $comment
     * @return bool
     */
    public function addStaticLease(string $iface, string $mac, string $ip, ?string $comment = null): bool
    {
        // TODO: append to conf
        return false;
    }
}

