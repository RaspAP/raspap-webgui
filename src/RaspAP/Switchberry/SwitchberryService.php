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
     * Detect a Switchberry from positive KSZ9567 hardware identification.
     */
    public static function isSupported(string $root = ''): bool
    {
        $root = rtrim($root, '/');
        static $supported = null;
        if ($root === '' && is_bool($supported)) {
            return $supported;
        }

        foreach (glob($root . '/sys/bus/spi/devices/*') ?: [] as $device) {
            $compatible = @file_get_contents($device . '/of_node/compatible');
            $driverBound = is_link($device . '/driver') || is_dir($device . '/driver');
            if ($driverBound && is_string($compatible)
                && in_array('microchip,ksz9567', explode("\0", $compatible), true)
            ) {
                if ($root === '') {
                    $supported = true;
                }
                return true;
            }
        }

        // A fixture root cannot safely perform a live SPI transaction.
        if ($root !== '') {
            return false;
        }

        $result = (new self())->execute(['detect']);
        $supported = ($result['detected'] ?? false) === true;
        return $supported;
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
        if (($arguments[0] ?? '') !== 'detect' && !self::isSupported()) {
            return [
                'ok' => false,
                'error' => 'Switchberry controls require a detected KSZ9567 Ethernet switch'
            ];
        }

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
