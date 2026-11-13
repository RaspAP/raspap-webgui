<?php

/**
 * NetworkHandler class
 *
 * @description Service class used to enumerate devices and their UDEV properties 
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Networking;

use RaspAP\Networking\UdevRuleManager;

class NetDeviceService
{

    public function getDeviceTableData(array $devices, array $deviceTypes, string $udevFile): array
    {
        $ruleManager = new UdevRuleManager($udevFile);
        $savedRules = $ruleManager->loadRules();

        if (empty($devices)) {
            return [];
        }

        $result = [];

        foreach ($devices as $dev) {
            $mac = strtolower(trim($dev['mac']));
            $vid = $dev['vid'] ?? '';
            $pid = $dev['pid'] ?? '';
            $name = $dev['name'] ?? '';
            $type = $dev['type'] ?? '';

            $matchedRule = $this->findMatchingRule($savedRules, $mac, $vid, $pid);
            if ($matchedRule && !empty($matchedRule['raspapType'])) {
                $type = $matchedRule['raspapType'];
            }
            $isStatic = $this->isDeviceStatic($mac, $vid, $pid, $udevFile, $type);
            $udevName = $this->getDeviceNameFromUdev($mac, $vid, $pid, $udevFile);
            $deviceTypeOptions = $this->buildDeviceTypeOptions($deviceTypes, $type);

            $result[] = [
                'vendor' => $dev['vendor'] ?? '',
                'model' => $dev['model'] ?? '',
                'name' => $name,
                'mac' => $mac,
                'vid' => $vid,
                'pid' => $pid,
                'type' => $type,
                'is_ap' => $dev['isAP'] ?? false,
                'is_static' => $isStatic,
                'udev_name' => $udevName,
                'device_type_options' => $deviceTypeOptions
            ];
        }

        return $result;
    }

    private function isDeviceStatic(string $mac, string $vid, string $pid, string $udevFile, string $type): bool
    {
        // Check other udev rules
        $escapedFile = escapeshellarg(basename($udevFile));
        $rules = [];

        // Match on MAC
        if (!empty($mac)) {
            $escapedMac = escapeshellarg($mac);
            exec("find /etc/udev/rules.d/ -type f \\( -iname '*.rules' ! -iname {$escapedFile} \\) -exec grep -i {$escapedMac} {} \;", $rules);
        }

        // Fallback to VID/PID match
        if (empty($rules) && !empty($vid) && !empty($pid)) {
            $escapedVid = escapeshellarg($vid);
            $escapedPid = escapeshellarg($pid);
            exec("find /etc/udev/rules.d/ -type f \\( -iname '*.rules' ! -iname {$escapedFile} \\) -exec grep -i {$escapedVid} {} \; | grep -i {$escapedPid}", $rules);
        }

        return !empty($rules) || in_array($type, ['ppp', 'tun']);
    }

    private function getDeviceNameFromUdev(string $mac, string $vid, string $pid, string $udevFile): string
    {
        $matches = [];
        $escapedFile = escapeshellarg($udevFile);

        if (!empty($vid) && !empty($pid)) {
            $escapedVid = escapeshellarg($vid);
            $escapedPid = escapeshellarg($pid);
            exec("grep -i {$escapedVid} {$escapedFile} | grep -i {$escapedPid} | sed -rn 's/.*name=\"(\\w*)\".*/\\1/ip'", $matches);
        }

        if (empty($matches) && !empty($mac)) {
            $escapedMac = escapeshellarg($mac);
            exec("grep -i {$escapedMac} {$escapedFile} | sed -rn 's/.*name=\"(\\w*)\".*/\\1/ip'", $matches);
        }

        return $matches[0] ?? '';
    }

    /**
     * Builds a list of device type options for a select input
     */
    private function buildDeviceTypeOptions(array $deviceTypes, string $currentType): array
    {
        $options = [];

        foreach ($deviceTypes as $opt) {
            // $disabled = in_array($opt['type'], ['ppp', 'tun']) ? 'disabled' : '';
            $selected = (strncmp($currentType, $opt['name_prefix'], strlen($opt['name_prefix'])) === 0) ? 'selected' : '';
            $options[] = [
                'value' => $opt['type'],
                'label' => $opt['type_info'],
                'selected' => $selected
                //'disabled' => $disabled
            ];
        }

        return $options;
    }

    private function findMatchingRule(array $rules, string $mac, string $vid, string $pid): ?array
    {
        foreach ($rules as $rule) {
            $macMatch = isset($rule['mac']) && strtolower($rule['mac']) === $mac;
            $vidMatch = isset($rule['vid']) && $rule['vid'] === $vid;
            $pidMatch = isset($rule['pid']) && $rule['pid'] === $pid;

            if ($macMatch || ($vidMatch && $pidMatch)) {
                return $rule;
            }
        }
        return null;
    }

}

