<?php

/**
 * UdevRulePrototypes class
 *
 * @description Handles loading and rendering of prototype UDEV rules from a JSON config. 
 *              These are canonical prototype definitions (read-only). 
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

namespace RaspAP\Networking;

class UdevRulePrototypes
{
    private array $prototypes;

    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Missing udev rule prototypes file at $path");
        }

        $data = json_decode(file_get_contents($path), true);

        if (!is_array($data['network_devices'] ?? null)) { 
            throw new \RuntimeException("Invalid format in udev rule prototypes");
        }

        $devices = $data['network_devices'];

        usort($devices, function ($a, $b) {
            return strnatcasecmp($a['type_info'] ?? '', $b['type_info'] ?? '');
        });

        $this->prototypes = $devices;
    }

    public function getPrototypes(): array
    {
        $types = [];

        foreach ($this->prototypes as $type) {
            $types[] = [
                'type' => $type['type'] ?? 'Unknown',
                'name_prefix' => $type['name_prefix'] ?? '',
                'type_info' => $type['type_info'] ?? 'Unknown'
            ];
        }

        // append "none" type only if needed
        $types[] = [
            'type' => 'Unknown',
            'name_prefix' => 'unknown',
            'type_info' => 'Unknown'
        ];

        return $types;
    }

    /**
     * Return the prototype by type
     */
    public function findByType(string $type): ?array
    {
        foreach ($this->prototypes as $proto) {
            if (($proto['type'] ?? null) === $type) {
                return $proto;
            }
        }
        return null;
    }

    /**
     * Generate a udev rule from a prototype and substitutions
     */
    public function generateRule(array $proto, array $vars): string
    {
        $rule = $proto['udev_rule'] ?? '';
        foreach ($vars as $key => $value) {
            $rule = preg_replace('/\\$' . $key . '\\$/i', $value, $rule);
        }
        return $rule;
    }

    /**
     * Return all prototypes
     */
    public function all(): array
    {
        return $this->prototypes;
    }
}

