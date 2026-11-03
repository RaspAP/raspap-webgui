<?php

/**
 * Configuration files of the hotspot services
 *
 * @description Installs and removes the configuration files that the web
 *              interface changes, through the root helper
 *              raspap-config-file.sh. The content goes to the helper on stdin,
 *              so there is no temporary file in /tmp.
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\System;

class ConfigFile
{
    private const HELPER = '/usr/local/sbin/raspap-config-file.sh';

    /**
     * The last error message of the helper, empty if there was none
     * @var string
     */
    public static string $lastError = '';

    /**
     * Installs a configuration file
     *
     * @param string $kind hostapd, dhcpcd, dnsmasq or adblock-custom
     * @param string|null $name the interface or dnsmasq name, if the kind needs one
     * @param string $content the new content
     * @return bool true on success
     */
    public static function install(string $kind, ?string $name, string $content): bool
    {
        $args = ['install', $kind];
        if ($name !== null && $name !== '') {
            $args[] = $name;
        }
        return self::run($args, $content) === 0;
    }

    /**
     * Removes a configuration file
     *
     * @param string $kind dnsmasq
     * @param string $name the interface or dnsmasq name
     * @return bool true on success
     */
    public static function remove(string $kind, string $name): bool
    {
        return self::run(['remove', $kind, $name], null) === 0;
    }

    private static function run(array $args, ?string $stdin): int
    {
        self::$lastError = '';
        $descriptors = [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open(array_merge(['sudo', self::HELPER], $args), $descriptors, $pipes);
        if (!is_resource($proc)) {
            self::$lastError = 'Unable to run ' . self::HELPER;
            return -1;
        }
        if ($stdin !== null) {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);
        $error = (string)stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $return = proc_close($proc);
        if ($return !== 0) {
            self::$lastError = trim($error);
            error_log('ConfigFile: ' . self::$lastError);
        }
        return $return;
    }
}
