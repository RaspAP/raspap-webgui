<?php

/**
 * UdevRuleManager class
 *
 * @description A class that handles building, loading and deleting UDEV rules 
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Networking;

class UdevRuleManager
{
    private string $udevFile;

    public function __construct(string $udevFile)
    {
        $this->udevFile = $udevFile;
    }

    /**
     * Replaces placeholders in a prototype rule with actual values
     */
    public function buildRule(array $proto, string $mac, ?string $vid, ?string $pid, string $name, string $type): string
    {
        $template = $proto['udev_rule'] ?? '';
        if (empty($template)) {
            throw new \RuntimeException("Prototype rule is missing");
        }

        return str_replace(
            ['$MAC$', '$VID$', '$PID$', '$DEVNAME$', '$TYPE$'],
            [$mac, $vid, $pid, $name, $type],
            $template
        );

    }

    /**
     * Initializes a new udev rule file with a descriptive header
     */
    public function initializeRuleFile(): void
    {
        if (file_exists($this->udevFile)) {
            return;
        }

        $tmpFile = '/tmp/raspap-net-device.rules';
        file_put_contents($tmpFile, $this->getHeader() . PHP_EOL);
        exec("sudo /bin/cp " . escapeshellarg($tmpFile) . " " . escapeshellarg($this->udevFile));
        unlink($tmpFile);
    }

    public function save(string $rule): void
    {
        // read existing rules
        $existingRules = file_exists($this->udevFile)
            ? file($this->udevFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            : [];

        // extract rules, skipping comments
        $filteredRules = array_filter($existingRules, function ($line) {
            return strncmp(ltrim($line), '#', 1) !== 0;
        });

        // check if rule already exists
        if (in_array(trim($rule), $filteredRules, true)) {
            return;
        }

        $filteredRules[] = trim($rule);

        // rebuild file with header
        $newContents = $this->getHeader() . PHP_EOL . implode(PHP_EOL, $filteredRules) . PHP_EOL;

        file_put_contents($this->udevFile, $newContents);
    }

    public function deleteExistingRules(string $mac, string $vid = '', string $pid = ''): void
    {
        // Restrict to hex characters before embedding in shell/sed patterns
        $mac = preg_replace('/[^0-9a-f:]/i', '', $mac);
        $vid = preg_replace('/[^0-9a-f]/i', '', $vid);
        $pid = preg_replace('/[^0-9a-f]/i', '', $pid);

        // Delete by VID/PID
        if (!empty($vid) && !empty($pid)) {
            $pattern1 = sprintf('^.*ATTRS{idVendor}==\"%s\".*ATTRS{idProduct}==\"%s\".*$', $vid, $pid);
            $pattern2 = sprintf('^.*ATTRS{idProduct}==\"%s\".*ATTRS{idVendor}==\"%s\".*$', $pid, $vid);

            exec("sudo sed -i '/$pattern1/Id' " . escapeshellarg($this->udevFile));
            exec("sudo sed -i '/$pattern2/Id' " . escapeshellarg($this->udevFile));
        }

        // delete by MAC address
        if (!empty($mac)) {
            $pattern = sprintf('^.*%s.*$', preg_quote($mac, '/'));
            exec("sudo sed -i '/$pattern/d' " . escapeshellarg($this->udevFile));
        }
    }

    public function loadRules(): array
    {
        if (!file_exists($this->udevFile)) {
            return [];
        }

        $lines = file($this->udevFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $rules = [];

        foreach ($lines as $line) {
            if (strpos($line, 'SUBSYSTEM==') !== false) {
                $rule = [];

                if (preg_match('/ATTR\{address\}=="([^"]+)"/', $line, $macMatch)) {
                    $rule['mac'] = strtolower($macMatch[1]);
                }
                if (preg_match('/ATTRS\{idVendor\}=="([^"]+)"/', $line, $vidMatch)) {
                    $rule['vid'] = $vidMatch[1];
                }
                if (preg_match('/ATTRS\{idProduct\}=="([^"]+)"/', $line, $pidMatch)) {
                    $rule['pid'] = $pidMatch[1];
                }
                if (preg_match('/NAME="([^"]+)"/', $line, $nameMatch)) {
                    $rule['name'] = $nameMatch[1];
                }
                if (preg_match('/ENV\{raspapType\}="([^"]+)"/', $line, $typeMatch)) {
                    $rule['raspapType'] = $typeMatch[1];
                }
                if (!empty($rule)) {
                    $rules[] = $rule;
                }
            }
        }
        return $rules;
    }
 
    /**
     * Returns the standardized file header for udev rules
     */
    private function getHeader(): string
    {
        return <<<EOL
# UDEV Rules managed by RaspAP
# This file contains persistent naming rules for network interfaces.
# Automatically generated. Do not edit manually.
EOL;
    }
}

