<?php

/**
 * DeviceScanner class
 *
 * @description A class for enumerating available devices and their details
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Networking;

class DeviceScanner
{
    public function listDevices(): array
    {
        $devices = [];

        foreach (glob('/sys/class/net/*') as $ifacePath) {
            $iface = basename($ifacePath);
            if ($iface === 'lo') {
                continue; // skip loopback
            }

            $device = [
                'name' => $iface,
                'mac' => $this->readFile("$ifacePath/address"),
                'ipaddress' => $this->getIPAddress($iface),
                'vendor' => '',
                'model' => '',
                'vid' => '',
                'pid' => '',
                'driver' => '',
                'type' => $this->getInterfaceType($iface),
                'isAP' => false,
                'connected' => 'y', // placeholder
                'signal' => '0 dB (100%)' // placeholder
            ];

            $udev = $this->getUdevAttributes($iface);
            $device['vendor'] = $this->getVendorName($udev);
            $device['model'] = $udev['ID_MODEL_FROM_DATABASE'] ?? $udev['ID_MODEL'] ?? '';
            $device['vid'] = $udev['ID_VENDOR_ID'] ?? '';
            $device['pid'] = $udev['ID_MODEL_ID'] ?? '';
            $device['driver'] = $udev['ID_NET_DRIVER'] ?? '';

            $devices[] = $device;
        }

        return $devices;
    }

    private function readFile(string $path): string
    {
        return is_readable($path) ? trim(file_get_contents($path)) : '';
    }

    private function getIPAddress(string $iface): string
    {
        $cmd = "ip -4 -o addr show dev " . escapeshellarg($iface) . " | awk '{print $4}' | cut -d/ -f1";
        $result = [];
        exec($cmd, $result);
        return $result[0] ?? '';
    }

    private function getInterfaceType(string $iface): string
    {
        $wirelessPath = "/sys/class/net/{$iface}/wireless";
        if (is_dir($wirelessPath)) {
            return 'wlan';
        }

        $typeFile = "/sys/class/net/{$iface}/type";
        $type = $this->readFile($typeFile);

        return match ($type) {
            '1' => 'eth',       // ARPHRD_ETHER
            '772' => 'loopback',
            '512' => 'ppp',
            default => 'unknown'
        };
    }

    private function getUdevAttributes(string $iface): array
    {
        $attributes = [];
        $output = [];
        $path = escapeshellarg("/sys/class/net/{$iface}");

        exec("udevadm info {$path}", $output);

        foreach ($output as $line) {
            if (preg_match('/E: (\w+)=([^\n]+)/', $line, $matches)) {
                $attributes[$matches[1]] = $matches[2];
            }
        }

        return $attributes;
    }

    private function getVendorName(array $udev): ?string
    {
        return $udev['ID_VENDOR_FROM_DATABASE']
            ?? $udev['ID_VENDOR']
            ?? $udev['ID_OUI_FROM_DATABASE']
            ?? null;
    }
}

