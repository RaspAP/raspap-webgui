<?php

/**
 * NetworkManager WiFi client class
 *
 * @description Manages the WiFi client connections through NetworkManager
 *              when NetworkManager manages the client interface (for example
 *              Raspberry Pi OS Bookworm and Trixie). RaspAP keeps its list of
 *              networks in wpa_supplicant.conf, and this class makes the
 *              NetworkManager connections match it. All changes go through
 *              the root helper raspap-nm-wifi.sh, which validates them.
 *              When NetworkManager does not manage the interface (for
 *              example after a change back to dhcpcd), isManaged() returns
 *              false and RaspAP uses wpa_supplicant as before.
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Networking;

class NetworkManagerClient
{
    public const HELPER = '/usr/local/sbin/raspap-nm-wifi.sh';
    private const SECURITY_OPEN = 'OPEN';

    /** @var array<string, bool> */
    private static array $managed = [];

    /**
     * Returns true when NetworkManager runs and manages the interface.
     * The result is kept for the current request only, so a change back
     * to dhcpcd takes effect on the next page load.
     *
     * @param string|null $iface
     * @return bool
     */
    public function isManaged(?string $iface): bool
    {
        if ($iface === null || !self::validInterface($iface) || !is_file(self::HELPER)) {
            return false;
        }
        if (!array_key_exists($iface, self::$managed)) {
            exec('sudo ' . self::HELPER . ' status ' . escapeshellarg($iface) . ' 2>/dev/null', $output, $return);
            self::$managed[$iface] = ($return === 0 && trim(implode('', $output)) === 'managed');
        }
        return self::$managed[$iface];
    }

    /**
     * Returns the WiFi client connections of NetworkManager that can use
     * the interface
     *
     * @param string $iface
     * @return array list of uuid, raspap, ssid, active, autoconnect, priority
     */
    public function listProfiles(string $iface): array
    {
        if (!$this->isManaged($iface)) {
            return [];
        }
        exec('sudo ' . self::HELPER . ' list ' . escapeshellarg($iface) . ' 2>/dev/null', $output, $return);
        if ($return !== 0) {
            return [];
        }
        $profiles = [];
        foreach ($output as $line) {
            $fields = explode("\t", $line);
            if (count($fields) !== 6 || !preg_match('/^[0-9a-f-]{36}$/', $fields[0])
                || !preg_match('/^([0-9a-f]{2})+$/', $fields[2])) {
                continue;
            }
            $profiles[] = [
                'uuid' => $fields[0],
                'raspap' => $fields[1] === '1',
                'ssid' => self::bytesToSsid(hex2bin($fields[2])),
                'active' => $fields[3] === '1',
                'autoconnect' => $fields[4] === '1',
                'priority' => (int)$fields[5],
            ];
        }
        return $profiles;
    }

    /**
     * Makes the RaspAP connections of NetworkManager match the configured
     * networks. Other connections do not change.
     *
     * @param string $iface
     * @param array $networks networks as read by WiFiManager::knownWifiStations()
     * @return bool
     */
    public function syncNetworks(string $iface, array $networks): bool
    {
        if (!$this->isManaged($iface)) {
            return false;
        }
        $lines = [];
        foreach ($networks as $ssid => $network) {
            if (empty($network['configured'])) {
                continue;
            }
            $bytes = self::ssidToBytes((string)$ssid);
            if ($bytes === '' || strlen($bytes) > 32) {
                continue;
            }
            $priority = max(-999, min(999, (int)($network['priority'] ?? 0)));
            if (($network['protocol'] ?? '') === self::SECURITY_OPEN) {
                $lines[] = implode("\t", [bin2hex($bytes), 'open', '-', $priority]);
            } else {
                $psk = strtolower((string)($network['passkey'] ?? ''));
                if (!preg_match('/^[0-9a-f]{64}$/', $psk)) {
                    continue;
                }
                $lines[] = implode("\t", [bin2hex($bytes), 'psk', $psk, $priority]);
            }
        }
        return $this->runHelper(['sync', $iface], implode("\n", $lines) . "\n") === 0;
    }

    /**
     * Activates a WiFi client connection
     *
     * @param string $iface
     * @param string $uuid
     * @return bool
     */
    public function connect(string $iface, string $uuid): bool
    {
        if (!$this->isManaged($iface) || !preg_match('/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/', $uuid)) {
            return false;
        }
        return $this->runHelper(['up', $iface, $uuid]) === 0;
    }

    /**
     * Activates the RaspAP connection of a configured network
     *
     * @param string $iface
     * @param string $ssid SSID as used by WiFiManager (\xHH for non-ASCII bytes)
     * @return bool
     */
    public function connectBySsid(string $iface, string $ssid): bool
    {
        foreach ($this->listProfiles($iface) as $profile) {
            if ($profile['raspap'] && $profile['ssid'] === $ssid) {
                return $this->connect($iface, $profile['uuid']);
            }
        }
        return false;
    }

    /**
     * Disconnects the interface. NetworkManager does not connect it again
     * automatically until a connection is activated.
     *
     * @param string $iface
     * @return bool
     */
    public function disconnect(string $iface): bool
    {
        if (!$this->isManaged($iface)) {
            return false;
        }
        return $this->runHelper(['disconnect', $iface]) === 0;
    }

    /**
     * Runs the helper with sudo, without a shell, and returns its exit code
     *
     * @param array $args
     * @param string|null $stdin
     * @return int
     */
    private function runHelper(array $args, ?string $stdin = null): int
    {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open(array_merge(['sudo', self::HELPER], $args), $descriptors, $pipes);
        if (!is_resource($proc)) {
            return -1;
        }
        if ($stdin !== null) {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $return = proc_close($proc);
        if ($return !== 0 && $error !== '') {
            error_log('NetworkManagerClient: ' . trim($error));
        }
        return $return;
    }

    /**
     * A valid interface name
     *
     * @param string $iface
     * @return bool
     */
    private static function validInterface(string $iface): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9_.-]{1,15}$/', $iface);
    }

    /**
     * Converts an SSID as used by WiFiManager (\xHH for non-ASCII bytes)
     * to its bytes
     *
     * @param string $ssid
     * @return string
     */
    public static function ssidToBytes(string $ssid): string
    {
        return preg_replace_callback('/\\\\x([0-9a-fA-F]{2})/', function ($m) {
            return chr(hexdec($m[1]));
        }, $ssid);
    }

    /**
     * Converts the bytes of an SSID to the notation of WiFiManager
     * (\xHH for non-ASCII bytes)
     *
     * @param string $bytes
     * @return string
     */
    public static function bytesToSsid(string $bytes): string
    {
        return preg_replace_callback('/[\x80-\xff]/', function ($m) {
            return '\\x' . strtolower(bin2hex($m[0]));
        }, $bytes);
    }
}
