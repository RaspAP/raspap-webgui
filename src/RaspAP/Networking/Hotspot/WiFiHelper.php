<?php

/**
 * WiFi helper
 *
 * @description Runs wpa_cli, wpa_supplicant and iw through the root helper
 *              raspap-wifi.sh, and installs wpa_supplicant.conf. The arguments
 *              go to the helper without a shell, and the new configuration on
 *              stdin, so there is no temporary file in /tmp.
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking\Hotspot;

class WiFiHelper
{
    private const HELPER = '/usr/local/sbin/raspap-wifi.sh';

    /**
     * Runs the helper
     *
     * @param array $args the arguments, for example ['wpa', 'wlan0', 'status']
     * @param string|null $stdin the content of wpa_supplicant.conf for "config install"
     * @param int|null $code receives the exit code
     * @return string the output and the error output, as "2>&1" gives them
     */
    public static function run(array $args, ?string $stdin = null, ?int &$code = null): string
    {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]];
        $args = array_map('strval', $args);
        $proc = proc_open(array_merge(['sudo', self::HELPER], $args), $descriptors, $pipes);
        if (!is_resource($proc)) {
            $code = -1;
            return '';
        }
        if ($stdin !== null) {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);
        $output = (string)stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $code = proc_close($proc);
        return $output;
    }

    /**
     * Runs a wpa_cli command for an interface
     *
     * @param string|null $iface the helper refuses an empty name
     * @param string ...$args the wpa_cli command and its arguments
     * @return string the output
     */
    public static function wpa(?string $iface, string ...$args): string
    {
        return self::run(array_merge(['wpa', (string)$iface], $args));
    }
}
