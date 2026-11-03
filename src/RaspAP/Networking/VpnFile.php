<?php

/**
 * WireGuard and OpenVPN client files
 *
 * @description Installs, reads and removes the VPN files of the web interface
 *              through the root helper raspap-vpn-file.sh. The content goes to
 *              the helper on stdin, so there is no temporary file in /tmp.
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking;

class VpnFile
{
    private const HELPER = '/usr/local/sbin/raspap-vpn-file.sh';

    /**
     * The last error message of the helper, empty if there was none
     * @var string
     */
    public static string $lastError = '';

    /**
     * Runs the helper
     *
     * @param array $args the arguments, for example ['wireguard', 'read', 'wg0.conf']
     * @param string|null $stdin the content of a file to install
     * @return string|null the output of the helper, or null if it failed
     */
    public static function run(array $args, ?string $stdin = null): ?string
    {
        self::$lastError = '';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open(array_merge(['sudo', self::HELPER], $args), $descriptors, $pipes);
        if (!is_resource($proc)) {
            self::$lastError = 'Unable to run ' . self::HELPER;
            return null;
        }
        if ($stdin !== null) {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);
        $output = (string)stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $error = (string)stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        if (proc_close($proc) !== 0) {
            self::$lastError = trim($error);
            error_log('VpnFile: ' . self::$lastError);
            return null;
        }
        return $output;
    }

    /**
     * Returns the lines of a file, as exec() does, or an empty array
     *
     * @param string $vpn wireguard or openvpn
     * @param string $file the file name for wireguard, client or login for openvpn
     * @return array
     */
    public static function readLines(string $vpn, string $file): array
    {
        $output = self::run([$vpn, 'read', $file]);
        if ($output === null || $output === '') {
            return [];
        }
        return explode("\n", rtrim($output, "\n"));
    }
}
