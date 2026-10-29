<?php

/**
 * MobileDataHandler class
 *
 * @description Class supporting configuration of mobile netwokring devices 
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking;

use RaspAP\Networking\NetworkHandler;
use RaspAP\Utils\JsonResponse;

class MobileDataHandler implements NetworkHandler
{
    private string $cfgFile;

    public function __construct()
    {
        $this->cfgFile = RASPI_WVDIAL_CONFIG;
    }

    public function handle(array $post): JsonResponse
    {
        $cfg = [
            'pin' => $post["pin-mobile"] ?? '',
            'apn' => $post["apn-mobile"] ?? '',
            'apn_user' => $post["apn-user-mobile"] ?? '',
            'apn_pw' => $post["apn-pw-mobile"] ?? '',
            'router_user' => $post["apn-user-mobile"] ?? '',
            'router_pw' => $post["apn-pw-mobile"] ?? ''
        ];

        if (!file_exists($this->cfgFile)) {
            return new JsonResponse(1, ['Missing config file: ' . $this->cfgFile]);
        }

        $this->updateDialerConfig($cfg);
        $success = write_php_ini($cfg, RASPI_MOBILEDATA_CONFIG);

        return $success
            ? new JsonResponse(0, ['Successfully saved mobile data settings'])
            : new JsonResponse(1, ['Error saving mobile data settings']);
    }

    /**
     * Updates specific fields in the wvdial configuration file
     *
     * @param array $cfg      Associative array with keys:
     *                        - 'pin'       => SIM PIN code
     *                        - 'apn'       => Access Point Name (APN)
     *                        - 'apn_user'  => APN username
     *                        - 'apn_pw'    => APN password
     * @return void
     */
    private function updateDialerConfig(array $cfg): void
    {
        $replacements = [
            'Init1 = AT\+CPIN=".*"' => 'Init1 = AT+CPIN="' . $cfg['pin'] . '"',
            'Init3 = AT\+CGDCONT=1,"IP",".*"' => 'Init3 = AT+CGDCONT=1,"IP","' . $cfg['apn'] . '"',
            '^Username = .*' => 'Username = ' . $cfg['apn_user'],
            '^Password = .*' => 'Password = ' . $cfg['apn_pw']
        ];

        foreach ($replacements as $pattern => $replacement) {
            if (trim($replacement) !== '') {
                $cmd = sprintf(
                    'sudo /bin/sed -i -E %s %s',
                    escapeshellarg("s|$pattern|$replacement|"),
                    escapeshellarg($this->cfgFile)
                );
                exec($cmd, $output, $return);
                if ($return !== 0) {
                    error_log("Failed to execute: $cmd\nOutput: " . implode("\n", $output));
                }
            }
        }
    }
}


