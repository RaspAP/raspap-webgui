<?php

/**
 * A dnsmasq configuration manager for RaspAP
 *
 * @description Class methods to get, build and save dnsmasq configs 
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking\Hotspot;

use RaspAP\Messages\StatusMessage;

class DnsmasqManager
{
    private const CONF_DEFAULT = '/etc/dnsmasq.d/';
    private const CONF_SUFFIX = '.conf';
    private const CONF_TMP = '/tmp/dnsmasqdata';
    private const CONF_RASPAP = '090_raspap';

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
    public function buildConfig(?array $syscfg, string $iface, bool $wifiAPEnable, bool $bridgedEnable): array
    {
        // fallback: if no syscfg for interface seed with defaults
        if ($syscfg === null) {
            $syscfg = [];
            $dhcp_range = getDefaultNetValue('dnsmasq', $iface, 'dhcp-range');
            if ($dhcp_range !== false) {
                $syscfg['dhcp-range'] = $dhcp_range;
            }
        }

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
            $this->scanConfigDir(SELF::CONF_DEFAULT,'uap0',$status);
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
                $dhcpOptions = (array) $syscfg['dhcp-option'];
                $grouped = [];

                foreach ($dhcpOptions as $opt) {
                    $parts = explode(',', $opt, 2);
                    if (count($parts) < 2) {
                        continue; // skip malformed option
                    }
                    list($code, $value) = $parts;
                    $grouped[$code][] = $value;
                }
                foreach ($grouped as $code => $values) {
                    $config[] = 'dhcp-option=' . $code . ',' . implode(',', $values);
                }
            }
            $config[] = PHP_EOL;
        }
        return $config;
    }

    /**
     * Builds an extended dnsmasq configuration
     *
     * @param string $iface
     * @param array $post_data
     * @return array $config
     */
    public function buildConfigEx(string $iface, array $post_data): array
    {
        $config[] = '# RaspAP '. $iface .' configuration';
        $config[] = 'interface='. $iface;
        $leaseTime = ($post_data['RangeLeaseTimeUnits'] !== 'i')
            ? $post_data['RangeLeaseTime'] . $post_data['RangeLeaseTimeUnits']
            : 'infinite';
        $config[] = 'dhcp-range=' . $post_data['RangeStart'] . ',' .
            $post_data['RangeEnd'] . ',' .
            $post_data['SubnetMask'] . ',' .
            $leaseTime;
        //  Static leases
        $staticLeases = array();
        if (isset($post_data["static_leases"]["mac"])) {
            for ($i=0; $i < count($post_data["static_leases"]["mac"]); $i++) {
                $mac = trim($post_data["static_leases"]["mac"][$i]);
                $ip  = trim($post_data["static_leases"]["ip"][$i]);
                $comment  = trim($post_data["static_leases"]["comment"][$i]);
                if ($mac != "" && $ip != "") {
                    $staticLeases[] = array('mac' => $mac, 'ip' => $ip, 'comment' => $comment);
                }
            }
        }
        //  Sort ascending by IPs
        usort($staticLeases, [$this, 'compareIPs']);
        //  Update config
        for ($i = 0; $i < count($staticLeases); $i++) {
            $mac = $staticLeases[$i]['mac'];
            $ip = $staticLeases[$i]['ip'];
            $comment = $staticLeases[$i]['comment'];
            $config[] = "dhcp-host=$mac,$ip # $comment";
        }
        if ($post_data['no-resolv'] == "1") {
            $config[] = "no-resolv";
        }
        foreach (($post_data['server'] ?? []) as $server) {
            $config[] = "server=$server";
        }
        if (!empty($post_data['DNS1'])) {
            $dnsOption = "dhcp-option=6," . $post_data['DNS1'];
            if (!empty($post_data['DNS2'])) {
                $dnsOption .= ',' . $post_data['DNS2'];
            }
            $config[] = $dnsOption;
        }
        if ($post_data['dhcp-ignore'] == "1") {
            $config[] = 'dhcp-ignore=tag:!known';
        }
        $config[]= PHP_EOL;
        return $config;
    }

    /**
     * Builds a RaspAP default dnsmasq config
     * Written to 090_raspap.conf
     *
     * @param array $post_data
     * @return array $config
     */
    public function buildDefault(array $post_data): array
    {
        // preamble
        $config[] = '# RaspAP default config';
        $config[] = 'log-facility='. RASPI_DHCPCD_LOG;
        $config[] = 'conf-dir=/etc/dnsmasq.d';

        // handle log option
        if (($post_data['log-dhcp'] ?? '') == "1") {
            $config[] = "log-dhcp";
        }
        if (($post_data['log-queries'] ?? '') == "1") {
          $config[] = "log-queries";
        }
        $config[] = PHP_EOL;

        return $config; 
    }

    /**
     * Saves dnsmasq configuration for an interface
     *
     * @param array $config
     * @param string $iface
     * @return bool
     */
    public function saveConfig(array $config, string $iface): bool
    {
        $configFile = RASPI_DNSMASQ_PREFIX . $iface . SELF::CONF_SUFFIX;
        $tempFile = SELF::CONF_TMP; 

        $config = join(PHP_EOL, $config);
        file_put_contents($tempFile, $config);
        $cmd = sprintf('sudo cp %s %s', escapeshellarg($tempFile), escapeshellarg($configFile));
        exec($cmd, $output, $status);
        if ($status !== 0) {
            throw new \RuntimeException("Failed to copy temp config to $configFile");
            return false;
        }

        // reload dnsmasq to apply changes
        exec('sudo systemctl reload dnsmasq.service', $output, $status);
        if ($status !== 0) {
            throw new \RuntimeException("Failed to reload dnsmasq service");
        }

        return true;
    }

    /**
     * Saves dnsmasq default configuration
     *
     * @param array $config
     * @return bool
     */
    public function saveConfigDefault(array $config): bool
    {
        $configFile = SELF::CONF_DEFAULT . SELF::CONF_RASPAP . SELF::CONF_SUFFIX;
        $tempFile = SELF::CONF_TMP; 

        $config = join(PHP_EOL, $config);
        file_put_contents($tempFile, $config);
        $cmd = sprintf('sudo cp %s %s', escapeshellarg($tempFile), escapeshellarg($configFile));
        exec($cmd, $output, $status);
        if ($status !== 0) {
            throw new \RuntimeException("Failed to copy temp config to $configFile");
            return false;
        }

        // reload dnsmasq to apply changes
        exec('sudo systemctl reload dnsmasq.service', $output, $status);
        if ($status !== 0) {
            throw new \RuntimeException("Failed to reload dnsmasq service");
        }

        return true;
    }

    /**
     * Validates dnsmasq user input from $_POST object
     *
     * @param array $post_data
     * @return array $errors
     */
    public function validate(array $post_data): array
    {
        $errors = [];
        $encounteredIPs = [];

        if (isset($post_data["static_leases"]["mac"])) {
            for ($i=0; $i < count($post_data["static_leases"]["mac"]); $i++) {
                $mac = trim($post_data["static_leases"]["mac"][$i]);
                $ip  = trim($post_data["static_leases"]["ip"][$i]);
                if (!validateMac($mac)) {
                    $errors[] = _('Invalid MAC address: '.$mac);
                }
                if (in_array($ip, $encounteredIPs)) {
                    $errors[] = _('Duplicate IP address entered: ' . $ip);
                } else {
                    $encounteredIPs[] = $ip;
                }
            }
        }
        return $errors;
    }

    /**
     * Removes a configuration block for the specified interface
     *
     * @param string $iface
     * @param StatusMessage $status
     * @return bool $result
     */
    public function remove(string $iface, StatusMessage $status): bool
    {
        system('sudo rm '.RASPI_DNSMASQ_PREFIX.$iface.'.conf', $result);
        if ($result == 0) {
            $status->addMessage('Dnsmasq configuration for '.$iface.' removed.', 'success');
            return true;
        } else {
            $status->addMessage('Failed to remove dnsmasq configuration for '.$iface.'.', 'danger');
            return false;
        }
    }

    /**
     * Scans configuration dir for the specified interface
     * Non-matching configs are removed, optional adblock.conf is protected
     *
     * @param string $dir_conf
     * @param string $interface
     * @return bool
     */
    public function scanConfigDir(string $dir_conf, string $interface): bool
    {
        $syscnf = preg_grep('~\.(conf)$~', scandir($dir_conf));
        foreach ($syscnf as $cnf) {
            if ($cnf !== '090_adblock.conf' && !preg_match('/.*_'.$interface.'.conf/', $cnf)) {
                system('sudo rm /etc/dnsmasq.d/'.$cnf, $result);
                return true;
            }
        }
    }

    /**
     * Compares two IPs
     *
     * @param array $ip1
     * @param array $ip2
     * @return int
     */
    private function compareIPs(array $ip1, array $ip2): int
    {
        $ipu1 = sprintf('%u', ip2long($ip1["ip"])) + 0;
        $ipu2 = sprintf('%u', ip2long($ip2["ip"])) + 0;
        return $ipu1 <=> $ipu2;
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
        return false;
    }
}

