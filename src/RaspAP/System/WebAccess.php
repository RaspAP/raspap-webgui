<?php

/**
 * Web interface access class
 *
 * @description Restricts access to the web interface to a list of client
 *              networks. lighttpd answers requests from other addresses with
 *              "403 Forbidden", so the web interface is not reachable from
 *              the WAN. The rules are kept in 51-raspap-access.conf of
 *              lighttpd, which the root helper raspap-webaccess.sh writes. This class
 *              does all the validation of the networks.
 *              Loopback addresses always have access.
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\System;

class WebAccess
{
    public const HELPER = '/usr/local/sbin/raspap-webaccess.sh';
    public const ACCESS_CONF = '/etc/lighttpd/conf-enabled/51-raspap-access.conf';
    public const MAX_NETWORKS = 16;

    /**
     * Networks that always have access. apply() adds them to the list.
     */
    public const LOOPBACK = ['127.0.0.0/8', '::1'];

    /**
     * Parses a list of networks, separated by commas or whitespace. Each
     * network is an IPv4 or IPv6 address, with an optional prefix length.
     * Returns the networks in canonical form (network address of a CIDR,
     * compressed IPv6) without duplicates, or null if a network is invalid
     * or the list has more than MAX_NETWORKS networks.
     *
     * @param string $input
     * @return array|null
     */
    public static function parseNetworks(string $input): ?array
    {
        $networks = [];
        foreach (preg_split('/[\s,]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY) as $item) {
            $network = self::normalizeNetwork($item);
            if ($network === null) {
                return null;
            }
            $networks[$network] = true;
        }
        if (count($networks) > self::MAX_NETWORKS) {
            return null;
        }
        return array_keys($networks);
    }

    /**
     * Returns true when a client address has access with the networks
     *
     * @param array $networks as returned by parseNetworks()
     * @param string $ip client address, for example REMOTE_ADDR
     * @return bool
     */
    public static function allows(array $networks, string $ip): bool
    {
        $addr = @inet_pton($ip);
        if ($addr === false) {
            return false;
        }
        // an IPv4 client on a dual-stack socket has an IPv4-mapped address
        if (strlen($addr) === 16 && strncmp($addr, str_repeat("\0", 10) . "\xff\xff", 12) === 0) {
            $addr = substr($addr, 12);
        }
        foreach (array_merge(self::LOOPBACK, $networks) as $network) {
            [$net, $bits] = self::splitNetwork($network);
            if ($net === null || strlen($net) !== strlen($addr)) {
                continue;
            }
            if (self::mask($addr, $bits) === self::mask($net, $bits)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the allowed networks of the lighttpd configuration, without
     * the loopback networks. An empty list means access from all networks.
     *
     * @param string $file
     * @return array
     */
    public static function readNetworks(string $file = self::ACCESS_CONF): array
    {
        if (!is_readable($file)) {
            return [];
        }
        preg_match_all('/^\s*\$HTTP\["remoteip"\]\s*!=\s*"([^"]+)"/m', (string)file_get_contents($file), $matches);
        return array_values(array_diff($matches[1], self::LOOPBACK));
    }

    /**
     * Writes the allowed networks to the lighttpd configuration. An empty
     * list removes the restriction. lighttpd uses the new settings after a
     * restart.
     *
     * @param array $networks as returned by parseNetworks()
     * @param array|null $output gets the messages of the helper
     * @return bool
     */
    public static function apply(array $networks, ?array &$output = null): bool
    {
        $output = [];
        if (empty($networks)) {
            $args = 'clear';
        } else {
            $args = 'allow ' . implode(' ', array_map('escapeshellarg', array_merge(self::LOOPBACK, $networks)));
        }
        exec('sudo ' . self::HELPER . ' ' . $args . ' 2>&1', $output, $return);
        return $return === 0;
    }

    /**
     * Returns a network in canonical form, or null if it is invalid
     *
     * @param string $network
     * @return string|null
     */
    private static function normalizeNetwork(string $network): ?string
    {
        [$addr, $bits] = self::splitNetwork($network);
        if ($addr === null) {
            return null;
        }
        $max = strlen($addr) * 8;
        if ($bits === null || $bits === $max) {
            return inet_ntop($addr);
        }
        return inet_ntop(self::mask($addr, $bits)) . '/' . $bits;
    }

    /**
     * Splits a network into the packed address and the prefix length (null
     * for a single address)
     *
     * @param string $network
     * @return array [string|null, int|null]
     */
    private static function splitNetwork(string $network): array
    {
        $parts = explode('/', $network);
        if (count($parts) > 2 || !filter_var($parts[0], FILTER_VALIDATE_IP)) {
            return [null, null];
        }
        $addr = inet_pton($parts[0]);
        if (count($parts) === 1) {
            return [$addr, null];
        }
        if (!preg_match('/^[0-9]{1,3}$/D', $parts[1]) || (int)$parts[1] > strlen($addr) * 8) {
            return [null, null];
        }
        return [$addr, (int)$parts[1]];
    }

    /**
     * Clears the host bits of a packed address
     *
     * @param string $addr
     * @param int|null $bits prefix length, null for all bits
     * @return string
     */
    private static function mask(string $addr, ?int $bits): string
    {
        if ($bits === null) {
            return $addr;
        }
        $masked = '';
        for ($i = 0; $i < strlen($addr); $i++) {
            $keep = max(0, min(8, $bits - $i * 8));
            $masked .= chr(ord($addr[$i]) & ((0xff << (8 - $keep)) & 0xff));
        }
        return $masked;
    }
}
