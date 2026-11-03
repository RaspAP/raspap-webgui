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
        // the helper changes the PIN, the APN and the login lines of
        // wvdial.conf. The values go on stdin, one per line, so the
        // password is not on the command line.
        $values = [$cfg['pin'], $cfg['apn'], $cfg['apn_user'], $cfg['apn_pw']];
        $descriptors = [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open(['sudo', '/usr/local/sbin/raspap-net.sh', 'wvdial'], $descriptors, $pipes);
        if (!is_resource($proc)) {
            error_log('Unable to run raspap-net.sh wvdial');
            return;
        }
        fwrite($pipes[0], implode("\n", array_map('strval', $values)) . "\n");
        fclose($pipes[0]);
        $error = (string)stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        if (proc_close($proc) !== 0) {
            error_log('Failed to update ' . $this->cfgFile . ': ' . trim($error));
        }
    }
}


