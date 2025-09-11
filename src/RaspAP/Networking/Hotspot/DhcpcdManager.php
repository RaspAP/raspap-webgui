<?php

/**
 * A dhcpcd configuration class for RaspAP
 *
 * @description Handles building, saving and safe updating of dhcpcd configs
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking\Hotspot;

use RaspAP\Messages\StatusMessage;

class DhcpcdManager
{
    private const CONF_DEFAULT = RASPI_DHCPCD_CONFIG;
    private const CONF_TMP     = '/tmp/dhcpddata';

    /**
     * Builds a dhcpcd config for an interface
     *
     * @param string $ap_iface
     * @param bool $bridgedEnable
     * @param bool $repeaterEnable
     * @param bool $wifiAPEnable
     * @param bool $dualAPEnable
     * @param StatusMessage $status
     * @return string 
     */
    public function buildConfig(
        string $ap_iface,
        bool $bridgedEnable,
        bool $repeaterEnable,
        bool $wifiAPEnable,
        bool $dualAPEnable,
        StatusMessage $status
        ): bool
    {
        // determine static IP, routers, DNS
        $jsonData = $this->getInterfaceConfig($ap_iface);

        $ip_address = empty($jsonData['StaticIP'])
            ? getDefaultNetValue('dhcp', $ap_iface, 'static ip_address')
            : $jsonData['StaticIP'];
        $domain_name_server = empty($jsonData['StaticDNS'])
            ? getDefaultNetValue('dhcp', $ap_iface, 'static domain_name_server')
            : $jsonData['StaticDNS'];
        $routers = empty($jsonData['StaticRouters'])
            ? getDefaultNetValue('dhcp', $ap_iface, 'static routers')
            : $jsonData['StaticRouters'];
        $netmask = (empty($jsonData['SubnetMask']) || $jsonData['SubnetMask'] === '0.0.0.0')
            ? getDefaultNetValue('dhcp', $ap_iface, 'subnetmask')
            : $jsonData['SubnetMask'];
        if (!preg_match('/.*\/\d+/', $ip_address)) {
            $ip_address .= '/' . mask2cidr($netmask);
        }
        $config = [];

        if ($bridgedEnable) {
            $config = array_keys(getDefaultNetOpts('dhcp', 'options'));
            $config[] = '# RaspAP br0 configuration';
            $config[] = 'denyinterfaces eth0 wlan0';
            $config[] = 'interface br0';
        } elseif ($repeaterEnable) {
            $config = [
                '# RaspAP ' . $ap_iface . ' configuration',
                'interface ' . $ap_iface,
                'static ip_address=' . $ip_address,
                'static routers=' . $routers,
                'static domain_name_server=' . $domain_name_server
            ];
            $client_metric = getIfaceMetric($_SESSION['wifi_client_interface']);
            if (is_int($client_metric)) {
                $config[] = 'metric ' . ((int)$client_metric + 1);
            } else {
                $status->addMessage(
                    'Unable to obtain metric value for client interface. Repeater mode inactive.',
                    'warning'
                );
            }
        } elseif ($wifiAPEnable) {
            $config = array_keys(getDefaultNetOpts('dhcp', 'options'));
            $config[] = '# RaspAP uap0 configuration';
            $config[] = 'interface uap0';
            $config[] = 'static ip_address=' . $ip_address;
            $config[] = 'nohook wpa_supplicant';
        } elseif ($dualAPEnable) {
            $config = [
                '# RaspAP ' . $ap_iface . ' configuration',
                'interface ' . $ap_iface,
                'static ip_address=' . $ip_address,
                'static routers=' . $routers,
                'static domain_name_server=' . $domain_name_server,
                'nogateway'
            ];
        } else {
            $config = $this->updateDhcpcdConfig(
                $ap_iface,
                $jsonData,
                $ip_address,
                $routers,
                $domain_name_server
            );
        }
        $dhcp_cfg = file_get_contents(SELF::CONF_DEFAULT);
        $skip_dhcp = false;

        if (preg_match('/wlan[3-9]\d*|wlan[1-9]\d+/', $ap_iface)) {
            $skip_dhcp = true;
        } elseif ($bridgedEnable == 1 || $wifiAPEnable == 1) {
            $dhcp_cfg = join(PHP_EOL, $config);
            $status->addMessage(sprintf(_('DHCP configuration for %s enabled.'), $ap_iface), 'success');
        } elseif (!preg_match('/^interface\s'.$ap_iface.'$/m', $dhcp_cfg)) {
            $config[] = PHP_EOL;
            $config= join(PHP_EOL, $config);
            $dhcp_cfg = $this->removeIface($dhcp_cfg,'br0');
            $dhcp_cfg = $this->removeIface($dhcp_cfg,'uap0');
            $dhcp_cfg .= $config;
        } else {
            $config = join(PHP_EOL, $config);
            $dhcp_cfg = $this->removeIface($dhcp_cfg,'br0');
            $dhcp_cfg = $this->removeIface($dhcp_cfg,'uap0');
            if (!strpos($dhcp_cfg, 'metric')) {
                $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$ap_iface.'\s.*?(?=(?:\s*^\s*$|\s*nogateway))/ms', $config, $dhcp_cfg, 1);
            } else {
                $metrics = true;
            }
        }
        if ($repeaterEnable && $metrics) {
            $status->addMessage(_('WiFi repeater mode: A metric value is already defined for DHCP.'), 'warning');
        } else if ($repeaterEnable && !$metrics) {
            $status->addMessage(sprintf(_('Metric value configured for the %s interface.'), $ap_iface), 'success');
            $status->addMessage('Restart hotspot to enable WiFi repeater mode.', 'success');
            $this->saveConfig($dhcp_cfg, $ap_iface, $status);
        } elseif (!$skip_dhcp) {
            $this->saveConfig($dhcp_cfg, $ap_iface, $status);
        } else {
            $status->addMessage('WiFi hotspot settings saved.', 'success');
        }
        return true;
    }

    /**
     * (Re)builds an existing dhcp configuration
     *
     * @param string $iface
     * @param StatusMessage $status
     * @param array $post_data
     * @return string $dhcp_cfg
     */
    public function buildConfigEx(string $iface, array $post_data, StatusMessage $status): string
    {
        $cfg[] = '# RaspAP '.$iface.' configuration';
        $cfg[] = 'interface '.$iface;
        if (isset($post_data['StaticIP']) && $post_data['StaticIP'] !== '') {
            $mask = ($post_data['SubnetMask'] !== '' && $post_data['SubnetMask'] !== '0.0.0.0') ? '/'.mask2cidr($post_data['SubnetMask']) : null;
            $cfg[] = 'static ip_address='.$post_data['StaticIP'].$mask;
        }
        if (isset($post_data['DefaultGateway']) && $post_data['DefaultGateway'] !== '') {
            $cfg[] = 'static routers='.$post_data['DefaultGateway'];
        }
        if ($post_data['DNS1'] !== '' || $post_data['DNS2'] !== '') {
            $cfg[] = 'static domain_name_server='.$post_data['DNS1'].' '.$post_data['DNS2'];
        }
        if ($post_data['Metric'] !== '') {
            $cfg[] = 'metric '.$post_data['Metric'];
        }
        if (($post_data['Fallback'] ?? 0) == 1) {
            $cfg[] = 'profile static_'.$iface;
            $cfg[] = 'fallback static_'.$iface;
        }
        $cfg[] = ($post_data['DefaultRoute'] ?? '') == '1' ? 'gateway' : 'nogateway';
        if (substr($iface, 0, 2) === "wl" && ($post_data['NoHookWPASupplicant'] ?? '') == '1') {
            $cfg[] = 'nohook wpa_supplicant';
        }
        $dhcp_cfg = file_get_contents(RASPI_DHCPCD_CONFIG);
        if (!preg_match('/^interface\s'.$iface.'$/m', $dhcp_cfg)) {
            $cfg[] = PHP_EOL;
            $cfg = join(PHP_EOL, $cfg);
            $dhcp_cfg .= $cfg;
            $status->addMessage('DHCP configuration for '.$iface.' added.', 'success');
        } else {
            $cfg = join(PHP_EOL, $cfg);
            $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$iface.'\s.*?(?=\s*^\s*$)/ms', $cfg, $dhcp_cfg, 1);
        }

        return $dhcp_cfg;
    }

    /**
     * Validates DHCP user input from $_POST data
     *
     * @param array $post_data
     * @return array $errors
     */
    public function validate(array $post_data): array
    {
        $errors = [];
        define('IFNAMSIZ', 16);
        $iface = $post_data['interface'];
        if (!preg_match('/^[^\s\/\\0]+$/', $iface)
            || strlen($iface) >= IFNAMSIZ
        ) {
            $errors[] = _('Invalid interface name.');
        }
        if (!filter_var($post_data['StaticIP'], FILTER_VALIDATE_IP) && !empty($post_data['StaticIP'])) {
            $errors[] = _('Invalid static IP address.');
        }
        if (!filter_var($post_data['SubnetMask'], FILTER_VALIDATE_IP) && !empty($post_data['SubnetMask'])) {
            $errors[] = _('Invalid subnet mask.');
        }
        if (!filter_var($post_data['DefaultGateway'], FILTER_VALIDATE_IP) && !empty($post_data['DefaultGateway'])) {
            $errors[] = _('Invalid default gateway.');
        }
        if (($post_data['dhcp-iface'] == "1")) {
            if (!filter_var($post_data['RangeStart'], FILTER_VALIDATE_IP) && !empty($post_data['RangeStart'])) {
                $errors[] = _('Invalid DHCP range start.');
            }
            if (!filter_var($post_data['RangeEnd'], FILTER_VALIDATE_IP) && !empty($post_data['RangeEnd'])) {
                $errors[] = _('Invalid DHCP range end.');
            }
            if (!ctype_digit($post_data['RangeLeaseTime']) && $post_data['RangeLeaseTimeUnits'] !== 'i') {
                $errors[] = _('Invalid DHCP lease time, not a number.');
            }
            if (!in_array($post_data['RangeLeaseTimeUnits'], array('m', 'h', 'd', 'i'))) {
                $errors[] = _('Unknown DHCP lease time unit.');
            }
            if ($post_data['Metric'] !== '' && !ctype_digit($post_data['Metric'])) {
                $errors[] = _('Invalid metric value, not a number.');
            }
        }
        return $errors;
    }

    /**
     * Saves a dhcpcd configuration
     *
     * @param string $config
     * @param StatusMessage $status
     * @return bool
     * @throws \RuntimeException
     */
    public function saveConfig(string $config, string $iface, StatusMessage $status): bool
    {
        if (file_put_contents(self::CONF_TMP, $config) === false) {
            throw new \RuntimeException("Failed to write temporary dhcpcd config");
        }

        exec(sprintf('sudo cp %s %s', escapeshellarg(self::CONF_TMP), escapeshellarg(self::CONF_DEFAULT)), $o, $rc);
        if ($rc !== 0) {
            $status->addMessage('Unable to save DHCP configuration.', 'danger');
            return false;
        }
        $status->addMessage(sprintf(_('DHCP configuration for %s updated.'), $iface), 'success');
        return true;
    }

    /**
     * Removes a dhcp configuration block for the specified interface
     *
     * @param string $iface
     * @param StatusMessage $status
     * @return bool $result
     */
    public function remove(string $iface, StatusMessage $status): bool
    {
        $configFile = SELF::CONF_DEFAULT; 
        $tempFile = SELF::CONF_TMP; 

        $dhcp_cfg = file_get_contents($configFile);
        $modified_cfg = preg_replace('/^#\sRaspAP\s'.$iface.'\s.*?(?=\s*^\s*$)([\s]+)/ms', '', $dhcp_cfg, 1);
        if ($modified_cfg !== $dhcp_cfg) {
            file_put_contents($tempFile, $modified_cfg);

            $cmd = sprintf('sudo cp %s %s', escapeshellarg($tempFile), escapeshellarg($configFile));
            exec($cmd, $output, $result);
     
            if ($result == 0) {
                $status->addMessage('DHCP configuration for '.$iface.'  removed', 'success');
                return true;
            } else {
                $status->addMessage('Failed to remove DHCP configuration for '.$iface, 'danger');
                return false;
            }
        }
    }

    /**
     * Removes a dhcp configuration block for the specified interface
     *
     * @param string $dhcp_cfg
     * @param string $iface
     * @return string $dhcp_cfg
     */
    public function removeIface(string $dhcp_cfg, string $iface): string
    {
        $dhcp_cfg = preg_replace('/^#\sRaspAP\s'.$iface.'\s.*?(?=\s*^\s*$)([\s]+)/ms', '', $dhcp_cfg, 1);
        return $dhcp_cfg;
    }

    /**
    * Updates the dhcpcd configuration for a given interface, preserving existing settings
    *
    * @param string $ap_iface
    * @param array $jsonData
    * @param string $ip_address
    * @param string $routers
    * @param string $domain_name_server
    * @return array updated configuration
    */
    private function updateDhcpcdConfig(
        string $ap_iface,
        array $jsonData,
        string $ip_address,
        string $routers,
        string $domain_name_server): array
    {
        $dhcp_cfg = file_get_contents(self::CONF_DEFAULT);
        $existing_config = [];
        $section_regex = '/^#\sRaspAP\s'.preg_quote($ap_iface, '/').'\s.*?(?=\s*^\s*$)/ms';

        // extract existing interface configuration
        if (preg_match($section_regex, $dhcp_cfg, $matches)) {
            $lines = explode(PHP_EOL, $matches[0]);
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^(interface|static|metric|nogateway|nohook)/', $line)) {
                    $existing_config[] = $line;
                }
            }
        }

        // initialize with comment
        $config = [ '# RaspAP '.$ap_iface.' configuration' ];
        $config[] = 'interface '.$ap_iface;
        $static_settings = [
            'static ip_address' => $ip_address,
            'static routers' => $routers,
            'static domain_name_server' => $domain_name_server
        ];

        // merge existing settings with updates
        foreach ($existing_config as $line) {
            $matched = false;
            foreach ($static_settings as $key => $value) {
                if (strpos($line, $key) === 0) {
                    $config[] = "$key=$value";
                    $matched = true;
                    unset($static_settings[$key]);
                    break;
                }
            }
            if (!$matched && !preg_match('/^interface/', $line)) {
                $config[] = $line;
            }
        }

        // add any new static settings
        foreach ($static_settings as $key => $value) {
            $config[] = "$key=$value";
        }

        // add metric if provided
        if (!empty($jsonData['Metric']) && !in_array('metric '.$jsonData['Metric'], $config)) {
            $config[] = 'metric '.$jsonData['Metric'];
        }
        return $config;
    }

    /**
     * Retrieves the metric value for a given interface
     *
     * @param string $iface
     * @return int $metric| bool false on failure
     */
    public function getIfaceMetric($iface)
    {
        $metric = shell_exec("ip -o -4 route show dev ".$iface." | awk '/metric/ {print \$NF; exit}'");
        if (isset($metric)) {
            $metric = (int)$metric;
            return $metric;
        } else {
            return false;
        }
    }

    /**
     * Gets current dhcpcd info for an interface
     *
     * @param string $iface
     * @return array
     */
    public function getInterfaceConfig(string $iface): array
    {
        $result = [
            'DHCPEnabled'         => false,
            'RangeStart'          => null,
            'RangeEnd'            => null,
            'RangeMask'           => null,
            'leaseTime'           => null,
            'leaseTimeInterval'   => null,
            'dhcpHost'            => [],
            'upstreamServersEnabled' => false,
            'upstreamServers'     => [],
            'DNS1'                => null,
            'DNS2'                => null,
            'Metric'              => null,
            'StaticIP'            => null,
            'SubnetMask'          => null,
            'StaticRouters'       => null,
            'StaticDNS'           => null,
            'FallbackEnabled'     => false,
            'DefaultRoute'        => false,
            'NoHookWPASupplicant' => false,
        ];

        // dnsmasq
        $dnsmasqFile = RASPI_DNSMASQ_PREFIX . $iface . '.conf';
        if (file_exists($dnsmasqFile) && is_readable($dnsmasqFile)) {
            $lines = [];
            exec('cat ' . escapeshellarg($dnsmasqFile), $lines);
            if (!function_exists('ParseConfig')) {
                require_once 'includes/functions.php';
            }
            $conf = ParseConfig($lines);

            if (!empty($conf)) {
                $result['DHCPEnabled'] = true;

                // dhcp-range may be multi-value
                $rangeRaw = $conf['dhcp-range'] ?? null;
                if (is_array($rangeRaw)) {
                    $rangeRaw = $rangeRaw[0] ?? null;
                }
                if (is_string($rangeRaw)) {
                    $rangeParts = explode(',', $rangeRaw);
                    $result['RangeStart'] = $rangeParts[0] ?? null;
                    $result['RangeEnd']   = $rangeParts[1] ?? null;
                    $result['RangeMask']  = $rangeParts[2] ?? null;
                    $leaseSpec            = $rangeParts[3] ?? null;
                    if ($leaseSpec) {
                        if (preg_match('/^(\d+)([smhd])?$/i', $leaseSpec, $m)) {
                            $result['leaseTime']         = $m[1];
                            $result['leaseTimeInterval'] = $m[2] ?? 'h'; // default to hours if missing
                        } else {
                            $result['leaseTime']         = $leaseSpec;
                            $result['leaseTimeInterval'] = null;
                        }
                    }
                }

                // dhcp-host entries (array or scalar)
                $hosts = $conf['dhcp-host'] ?? [];
                if (!is_array($hosts) && $hosts !== null) {
                    $hosts = [$hosts];
                }
                $result['dhcpHost'] = array_values(array_filter($hosts));

                // upstream DNS servers (server= lines)
                $servers = $conf['server'] ?? [];
                if (!is_array($servers) && !empty($servers)) {
                    $servers = [$servers];
                }
                $servers = array_filter($servers);
                if (!empty($servers)) {
                    $result['upstreamServersEnabled'] = true;
                    $result['upstreamServers'] = $servers;
                }

                // dhcp-option=6,<dns1>[,<dns2>]
                if (isset($conf['dhcp-option'])) {
                    $optsRaw = $conf['dhcp-option'];
                    // may be multiple dhcp-option lines; coalesce
                    $optLines = is_array($optsRaw) ? $optsRaw : [$optsRaw];
                    foreach ($optLines as $optLine) {
                        $parts = explode(',', $optLine);
                        if ($parts[0] === '6') {
                            $result['DNS1'] = $parts[1] ?? null;
                            $result['DNS2'] = $parts[2] ?? null;
                            break;
                        }
                    }
                }
            }
        } else {
            // fallback to defaults
            $rangeRaw = getDefaultNetValue('dnsmasq', $iface, 'dhcp-range');
            if ($rangeRaw) {
                $result['DHCPEnabled'] = true;
                $rangeParts = explode(',', $rangeRaw);
                $result['RangeStart'] = $rangeParts[0] ?? null;
                $result['RangeEnd']   = $rangeParts[1] ?? null;
                $result['RangeMask']  = $rangeParts[2] ?? null;
                $leaseSpec            = $rangeParts[3] ?? null;
                if ($leaseSpec && preg_match('/^(\d+)([smhd])?$/i', $leaseSpec, $m)) {
                    $result['leaseTime']         = $m[1];
                    $result['leaseTimeInterval'] = $m[2] ?? 'h';
                }
            }
        }

        // dhcpcd
        if (file_exists(self::CONF_DEFAULT) && is_readable(self::CONF_DEFAULT)) {
            $dhcpcd = file_get_contents(self::CONF_DEFAULT);

            // match interface block starting with '# RaspAP <iface> configuration'
            $sectionPattern = '/^#\sRaspAP\s' . preg_quote($iface, '/') . '\sconfiguration.*?(?=^(?:#\sRaspAP\s|\s*$))/ms';
            if (preg_match($sectionPattern, $dhcpcd, $match)) {
                $block = $match[0];

                $result['Metric'] = $this->matchFirst('/\bmetric\s+(\d+)/i', $block);
                $staticIPLine     = $this->matchFirst('/static\s+ip_address=([^\r\n]+)/i', $block);
                $staticRouters    = $this->matchFirst('/static\s+routers=([^\r\n]+)/i', $block);
                $staticDNS        = $this->matchFirst('/static\s+domain_name_server=([^\r\n]+)/i', $block);

                $result['StaticIP']       = $staticIPLine ? (strpos($staticIPLine,'/') !== false
                    ? substr($staticIPLine, 0, strpos($staticIPLine,'/'))
                    : $staticIPLine) : null;
                $result['SubnetMask']     = $staticIPLine && function_exists('cidr2mask') && strpos($staticIPLine,'/')
                    ? cidr2mask($staticIPLine)
                    : ($result['SubnetMask'] ?? null);
                $result['StaticRouters']  = $staticRouters;
                $result['StaticDNS']      = $staticDNS;

                $result['FallbackEnabled']     = (bool) preg_match('/fallback\s+static_' . preg_quote($iface, '/') . '/i', $block);
                $result['DefaultRoute']        = (bool) preg_match('/\bgateway\b/', $block);
                $result['NoHookWPASupplicant'] = (bool) preg_match('/nohook\s+wpa_supplicant/i', $block);
            } else {
                $result['StaticIP']      = getDefaultNetValue('dhcp', $iface, 'static ip_address');
                $result['SubnetMask']    = getDefaultNetValue('dhcp', $iface, 'subnetmask');
                $result['StaticRouters'] = getDefaultNetValue('dhcp', $iface, 'static routers');
                $result['StaticDNS']     = getDefaultNetValue('dhcp', $iface, 'static domain_name_server');
            }
        }
        return $result;
    }

    private function matchFirst(string $pattern, string $subject): ?string
    {
        return preg_match($pattern, $subject, $m) ? trim($m[1]) : null;
    }

}

