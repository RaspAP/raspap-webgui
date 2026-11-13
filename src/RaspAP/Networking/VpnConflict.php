<?php

/**
 * VPN conflict class
 *
 * @description Detects WireGuard and OpenVPN client tunnels that both route all
 *              traffic. Two full tunnels at the same time break the routing:
 *              the packets of one tunnel go into the other tunnel. When both
 *              tunnels connect to the same server, the second tunnel runs
 *              through the first one, and both stop.
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Networking;

class VpnConflict
{
    // seconds for all endpoint lookups together, so that a DNS server that
    // does not reply (likely when the two tunnels break the routing) does
    // not delay the page for long
    private const RESOLVE_TIMEOUT = 2;

    /**
     * Returns a warning when WireGuard and OpenVPN are both in use as
     * full tunnels, or null when there is no conflict
     *
     * @return string|null
     */
    public function getWarning(): ?string
    {
        $wireguard = $this->getWireGuard();
        $openvpn = $this->getOpenVpn();
        if ($wireguard === null || $openvpn === null) {
            return null;
        }

        $warning = _('WireGuard and OpenVPN are both set to send all traffic through their tunnel. Use only one of them at a time.');
        $shared = $this->sharedEndpoints(
            $wireguard['endpoints'],
            $openvpn['endpoints'],
            microtime(true) + self::RESOLVE_TIMEOUT
        );
        if (!empty($shared)) {
            $warning .= ' ' . sprintf(
                _('Both connect to the same server (%s), so one tunnel runs through the other.'),
                implode(', ', $shared)
            );
        }
        return $warning;
    }

    /**
     * Returns the endpoints of the WireGuard client if it is in use and
     * routes all traffic, or null
     *
     * @return array|null
     */
    public function getWireGuard(): ?array
    {
        if (!$this->serviceInUse('wg-quick@wg0')) {
            return null;
        }
        exec('sudo cat ' . RASPI_WIREGUARD_CONFIG . ' 2>/dev/null', $lines);
        $fullTunnel = false;
        $endpoints = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*AllowedIPs\s*=\s*(.+)$/i', $line, $m)) {
                foreach (preg_split('/\s*,\s*/', trim($m[1])) as $net) {
                    if ($net === '0.0.0.0/0' || $net === '::/0') {
                        $fullTunnel = true;
                    }
                }
            } elseif (preg_match('/^\s*Endpoint\s*=\s*(\S+)/i', $line, $m)) {
                $endpoints[] = self::hostFromEndpoint($m[1]);
            }
        }
        return $fullTunnel ? ['endpoints' => array_filter($endpoints)] : null;
    }

    /**
     * Returns the remote hosts of the OpenVPN client if it is in use and
     * routes all traffic, or null. A server can also push redirect-gateway,
     * so an active default route through a tun interface also counts.
     *
     * @return array|null
     */
    public function getOpenVpn(): ?array
    {
        if (!$this->serviceInUse('openvpn-client@client')) {
            return null;
        }
        $lines = is_readable(RASPI_OPENVPN_CLIENT_CONFIG)
            ? file(RASPI_OPENVPN_CLIENT_CONFIG, FILE_IGNORE_NEW_LINES)
            : [];
        $fullTunnel = false;
        $endpoints = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*redirect-gateway\b/', $line)) {
                $fullTunnel = true;
            } elseif (preg_match('/^\s*remote\s+(\S+)/', $line, $m)) {
                $endpoints[] = $m[1];
            }
        }
        if (!$fullTunnel) {
            $routes = (string)shell_exec('ip -4 route show 0.0.0.0/1 2>/dev/null');
            $fullTunnel = (bool)preg_match('/\bdev\s+tun\d+/', $routes);
        }
        return $fullTunnel ? ['endpoints' => $endpoints] : null;
    }

    /**
     * Returns the host part of a WireGuard endpoint (host:port, [IPv6]:port)
     *
     * @param string $endpoint
     * @return string
     */
    public static function hostFromEndpoint(string $endpoint): string
    {
        if (preg_match('/^\[([^\]]+)\](:\d+)?$/', $endpoint, $m)) {
            return $m[1];
        }
        if (substr_count($endpoint, ':') > 1) {
            return $endpoint; // IPv6 address without a port
        }
        return preg_replace('/:\d+$/', '', $endpoint);
    }

    /**
     * Returns the endpoints that both tunnels use. The same hostname or IP
     * address counts at once, without DNS. Otherwise the hostnames are
     * resolved, and a shared IP address counts.
     *
     * @param array $first
     * @param array $second
     * @param float $deadline time (microtime) at which the lookups stop
     * @return array
     */
    public function sharedEndpoints(array $first, array $second, float $deadline): array
    {
        $names = array_values(array_intersect(self::normalize($first), self::normalize($second)));
        if (!empty($names)) {
            return array_values(array_unique($names));
        }
        return array_values(array_intersect(
            $this->resolveAll($first, $deadline),
            $this->resolveAll($second, $deadline)
        ));
    }

    /**
     * Returns the IP addresses of a list of hosts. An IP address stays as it
     * is. All hostnames are resolved with one getent call, which stops at the
     * deadline.
     *
     * @param array $hosts
     * @param float $deadline time (microtime) at which the lookup stops
     * @return array
     */
    public function resolveAll(array $hosts, float $deadline): array
    {
        $ips = [];
        $names = [];
        foreach (self::normalize($hosts) as $host) {
            if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                $ips[] = $host;
            } else {
                $names[] = escapeshellarg($host);
            }
        }
        $remaining = $deadline - microtime(true);
        if (!empty($names) && $remaining > 0.1) {
            exec(sprintf('timeout %.1F getent ahosts %s 2>/dev/null', $remaining, implode(' ', $names)), $lines);
            foreach ($lines as $line) {
                $ip = strtok($line, " \t");
                if ($ip !== false && filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    $ips[] = inet_ntop(inet_pton($ip));
                }
            }
        }
        return array_values(array_unique($ips));
    }

    /**
     * Returns the hosts in lowercase, without brackets and empty entries.
     * IP addresses get their short form, so that equal addresses compare
     * equal.
     *
     * @param array $hosts
     * @return array
     */
    private static function normalize(array $hosts): array
    {
        $result = [];
        foreach ($hosts as $host) {
            $host = strtolower(trim((string)$host, "[] \t."));
            if ($host === '') {
                continue;
            }
            if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                $host = inet_ntop(inet_pton($host));
            }
            $result[] = $host;
        }
        return array_values(array_unique($result));
    }

    /**
     * A service is in use when it is active, or enabled to start at boot
     *
     * @param string $service
     * @return bool
     */
    protected function serviceInUse(string $service): bool
    {
        $unit = escapeshellarg($service);
        exec("systemctl is-active --quiet $unit", $out, $active);
        exec("systemctl is-enabled --quiet $unit", $out, $enabled);
        return $active === 0 || $enabled === 0;
    }
}
