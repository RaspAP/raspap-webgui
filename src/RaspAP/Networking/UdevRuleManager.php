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
    // the root helper that changes the rules file, see installers/udev-rules.sh
    public const HELPER = '/usr/local/sbin/raspap-udev-rules.sh';

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
     * Makes the rule file with its header, when it does not exist
     */
    public function initializeRuleFile(): void
    {
        if (file_exists($this->udevFile)) {
            return;
        }
        $this->runHelper(['init']);
    }

    /**
     * Replaces the rules for a MAC address, and the rules for a USB vendor
     * and product ID, with a new rule. The helper accepts only the keys and
     * values of the prototype rules. When it refuses the rule, the file
     * does not change.
     *
     * @param string $mac
     * @param string $vid
     * @param string $pid
     * @param string $rule
     * @return bool
     */
    public function replaceRules(string $mac, string $vid, string $pid, string $rule): bool
    {
        $mac = strtolower($mac);
        $vid = strtolower($vid);
        $pid = strtolower($pid);
        return $this->runHelper([
            'replace',
            preg_match('/^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/', $mac) ? $mac : '-',
            preg_match('/^[0-9a-f]{4}$/', $vid) ? $vid : '-',
            preg_match('/^[0-9a-f]{4}$/', $pid) ? $pid : '-',
        ], trim($rule) . "\n") === 0;
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
     * Runs the helper with sudo, without a shell, and returns its exit code
     *
     * @param array $args
     * @param string|null $stdin
     * @return int
     */
    private function runHelper(array $args, ?string $stdin = null): int
    {
        $descriptors = [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open(array_merge(['sudo', self::HELPER], $args), $descriptors, $pipes);
        if (!is_resource($proc)) {
            return -1;
        }
        if ($stdin !== null) {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $return = proc_close($proc);
        if ($return !== 0 && $error !== '') {
            error_log('UdevRuleManager: ' . trim($error));
        }
        return $return;
    }
}

