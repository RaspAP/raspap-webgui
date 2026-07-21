<?php

/**
 * Switchberry hardware service
 *
 * @description Provides a safe bridge to the root-owned Switchberry controller.
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Switchberry;

class SwitchberryService
{
    private const CONTROL = '/usr/local/sbin/raspap-switchberryctl';

    /**
     * Detect a Switchberry by official software markers or device-tree nodes.
     */
    public static function isSupported(string $root = ''): bool
    {
        $root = rtrim($root, '/');
        $path = static fn(string $value): string => $root . $value;

        if (is_dir($path('/etc/switchberry'))) {
            return true;
        }

        $softwareMarkers = [
            $path('/etc/startup-dpll.json'),
            $path('/usr/local/sbin/apply_timing.py')
        ];
        if (count(array_filter($softwareMarkers, 'is_file')) === count($softwareMarkers)) {
            return true;
        }

        $deviceTreeNodes = [
            $path('/proc/device-tree/mdio/ethernet-phy@0'),
            $path('/proc/device-tree/i2c@9/gpio@23'),
            $path('/proc/device-tree/spi_bitbang/spidev@0')
        ];

        return count(array_filter($deviceTreeNodes, 'file_exists')) === count($deviceTreeNodes);
    }

    /**
     * Return the complete Switchberry status payload.
     */
    public function status(): array
    {
        $result = $this->execute(['status']);
        if (!isset($result['system'])) {
            $result['system'] = [];
        }
        if (!isset($result['config'])) {
            $result['config'] = [];
        }
        return $result;
    }

    /**
     * Save and asynchronously apply a complete Switchberry configuration.
     */
    public function configure(array $config): array
    {
        return $this->execute(['configure'], $config);
    }

    /**
     * Enable or disable a physical front-panel Ethernet port.
     */
    public function setPort(int $port, string $action): array
    {
        if ($port < 1 || $port > 5 || !in_array($action, ['enable', 'disable'], true)) {
            return ['ok' => false, 'error' => 'Invalid Switchberry port action'];
        }
        return $this->execute(['port', $action, (string) $port]);
    }

    /**
     * Apply a guarded operating-state action to ClockMatrix channel 5 or 6.
     */
    public function clockmatrixAction(int $channel, string $action): array
    {
        if (!in_array($channel, [5, 6], true) || !in_array($action, ['normal', 'reacquire', 'holdover', 'freerun'], true)) {
            return ['ok' => false, 'error' => 'Invalid ClockMatrix channel action'];
        }
        return $this->execute(['clockmatrix', (string) $channel, $action]);
    }

    /**
     * Restart GNSS services or request a controlled u-blox acquisition reset.
     */
    public function gnssAction(string $action): array
    {
        if (!in_array($action, ['restart', 'hotstart', 'warmstart', 'coldstart'], true)) {
            return ['ok' => false, 'error' => 'Invalid GNSS receiver action'];
        }
        return $this->execute(['gnss', $action]);
    }

    /**
     * Queue an ordered restart of the board timing and PTP stack.
     */
    public function restart(): array
    {
        return $this->execute(['restart']);
    }

    /**
     * Queue a reboot after a clock-plane change.
     */
    public function reboot(): array
    {
        return $this->execute(['reboot']);
    }

    /**
     * Execute the fixed controller argv, optionally sending JSON through stdin.
     */
    private function execute(array $arguments, ?array $input = null): array
    {
        if (!is_executable(self::CONTROL)) {
            return [
                'ok' => false,
                'error' => sprintf('Switchberry controller is not installed at %s', self::CONTROL)
            ];
        }

        $command = array_merge(['/usr/bin/sudo', self::CONTROL], $arguments);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            return ['ok' => false, 'error' => 'Unable to start the Switchberry controller'];
        }

        if ($input !== null) {
            $encoded = json_encode($input, JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_terminate($process);
                proc_close($process);
                return ['ok' => false, 'error' => 'Unable to encode Switchberry configuration'];
            }
            fwrite($pipes[0], $encoded);
        }
        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $decoded = json_decode(trim((string) $stdout), true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'error' => trim((string) $stderr) ?: 'The Switchberry controller returned invalid data',
                'exit_code' => $exitCode
            ];
        }
        if ($exitCode !== 0 && empty($decoded['error'])) {
            $decoded['ok'] = false;
            $decoded['error'] = trim((string) $stderr) ?: 'Switchberry controller command failed';
        }
        return $decoded;
    }
}
