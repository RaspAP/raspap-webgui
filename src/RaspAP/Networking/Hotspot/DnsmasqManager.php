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

