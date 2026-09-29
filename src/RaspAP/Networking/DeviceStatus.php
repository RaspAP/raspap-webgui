<?php

/**
 * NetDeviceStatus class
 *
 * @description Utility class that returns the status of various wired and
 *              wireless interfaces. This is currently a work in progress. 
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Networking;

class DeviceStatus
{
    public function getStatus(array $device): array
    {
        $type = $device['type'] ?? '';
        $name = $device['name'] ?? '';
        $ip = $device['ipaddress'] ?? '';

        switch ($type) {
            case 'eth':
                return $this->ethernetStatus($name, $ip);
            case 'wlan':
                return $this->wifiStatus($name);
            default:
                return $this->unknownStatus();
        }
    }

    private function ethernetStatus(string $dev, string $ip): array
    {
        $isUp = $this->interfaceIsUp($dev);

        return [
            'connected' => ($isUp || !empty($ip)) ? 'y' : 'n',
            'icon' => 'network-wired',
            'signal' => '0 dB (100%)'
        ];
    }

    private function wifiStatus(string $dev): array
    {
        $result = [
            'connected' => 'n',
            'isAP' => false,
            'icon' => 'wifi',
            'signal' => '-100 dB (0%)'
        ];

        exec("iwconfig $dev 2>/dev/null | grep -i mode", $modeCheck);
        if (!empty($modeCheck) && stripos($modeCheck[0], 'master') !== false) {
            $result['isAP'] = true;
        }

        exec("iw dev $dev link 2>/dev/null", $iwOutput);
        if (empty($iwOutput)) {
            return $result;
        }

        $ssid = $this->pregMatch("/SSID:\s*(.*)/", $iwOutput);
        $signal = $this->pregMatch("/signal:\s*(-?\d+)\s*dBm/", $iwOutput);

        if ($ssid) {
            $result['connected'] = 'y';
            $result['ssid'] = $ssid;
            $result['ap-mac'] = $this->pregMatch("/Connected to\s+([0-9a-f:]+)/i", $iwOutput);

            $qual = 0;
            $val = (int)$signal;
            if ($val >= -50) {
                $qual = 100;
            } elseif ($val <= -100) {
                $qual = 0;
            } else {
                $qual = round(($val + 100) * 2);
            }

            $result['signal'] = "{$signal} dB ({$qual}%)";
            $result['bitrate'] = $this->pregMatch("/tx bitrate:\s*(.*)/", $iwOutput);
            $result['freq'] = $this->pregMatch("/freq:\s*(.*)/", $iwOutput);
        }

        return $result;
    }

    private function unknownStatus(): array
    {
        return [
            'connected' => 'n',
            'icon' => 'question',
            'signal' => '-100 dB (0%)'
        ];
    }

    private function interfaceIsUp(string $dev): bool
    {
        exec("ip link show $dev 2>/dev/null | grep -q 'state UP'", $out, $code);
        return $code === 0;
    }

    private function pregMatch(string $pattern, array $lines): ?string
    {
        foreach ($lines as $line) {
            if (preg_match($pattern, $line, $matches)) {
                return trim($matches[1]);
            }
        }
        return null;
    }
}

