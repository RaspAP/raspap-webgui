<?php

/**
 * NetDeviceHandler class
 *
 * @description A class for handling network devices and UDEV rules
 * @author      Bill Zimmerman <billzimmerman@gmail.com>
 * @license     https://github.com/raspap/raspap-webgui/blob/master/LICENSE
 */

declare(strict_types=1);

namespace RaspAP\Networking;

use RaspAP\Utils\JsonResponse;

class NetDeviceHandler
{
    protected $udevConfig;
    protected $rulePrototypes;
    protected $ruleManager;

    public function __construct()
    {
        $this->rulePrototypes = new UdevRulePrototypes(RASPI_CLIENT_CONFIG_PATH);
        $this->ruleManager = new UdevRuleManager(RASPI_USER_UDEV_RULES);
    }

    public function handle(array $form): JsonResponse
    {
        
        $this->ruleManager->initializeRuleFile(); 
        $opts = isset($form['opts']) ? explode(' ', $form['opts']) : [];
        $dev = $opts[0] ?? null;

        if (!$dev) {
            return JsonResponse::error('Missing device identifier');
        }
        // the form does not send the current address, so read it
        $addressFile = "/sys/class/net/$dev/address";
        $current = preg_match('/^[A-Za-z0-9_.-]{1,15}$/', $dev) && is_readable($addressFile)
            ? file_get_contents($addressFile) : false;
        if ($current === false) {
            return JsonResponse::error(_('Unknown device'));
        }
        $mac = strtolower(trim($current));
        $vid = strtolower(trim($form["int-vid-$dev"] ?? ''));
        $pid = strtolower(trim($form["int-pid-$dev"] ?? ''));
        // the values go into a udev rule
        if (!preg_match('/^([0-9a-f]{4})?$/', $vid) || !preg_match('/^([0-9a-f]{4})?$/', $pid)) {
            return JsonResponse::error(_('Invalid USB vendor or product ID'));
        }
        $newMac = trim(strtolower($form["int-new-mac-$dev"] ?? ''));
        if ($newMac === '') {
            $newMac = $mac;
        } elseif (!self::validMac($newMac)) {
            return JsonResponse::error(_('Invalid MAC address'));
        }
        $name = trim(strtolower(preg_replace("/[^a-z0-9]/", '', $form["int-name-$dev"] ?? '')));
        $name = substr($name, 0, 20);
        $newType = $form["int-new-type-$dev"] ?? '';

        // udev applies the rule when the device appears, for example at boot.
        // Then the device has its permanent address again, because a change
        // here lasts until the next reboot. Thus the rule uses this address.
        exec('ip -j -d link show dev ' . escapeshellarg($dev) . ' 2>/dev/null', $ipOutput);
        $ruleMac = self::permanentMac(implode('', $ipOutput), $mac);

        $proto = $this->rulePrototypes->findByType($newType);
        if ($proto === null) {
            return JsonResponse::error("No matching prototype rule for type $newType");
        }
        if (empty($name)) {
            $name = $proto['name_prefix'] . '%n';
        }
        $rule = $this->ruleManager->buildRule(
            $proto,
            $ruleMac,
            $vid,
            $pid,
            $name,
            $newType
        );
        if (!$this->ruleManager->replaceRules($ruleMac, $vid, $pid, $rule)) {
            return JsonResponse::error(sprintf(_('Unable to save the udev rule for device %s'), $dev));
        }

        $macAddrChanged = false;
        if ($mac !== $newMac) {
            exec("sudo ip link set " . escapeshellarg($dev) . " down");
            sleep(1);
            exec("sudo ip link set " . escapeshellarg($dev) . " address " . escapeshellarg($newMac));
            exec("sudo ip link set " . escapeshellarg($dev) . " up");
            $macAddrChanged = true;
        }

        $msg = $macAddrChanged
            ? sprintf(_('Address changed for device %s. New MAC address: %s'), $dev, $newMac)
            : sprintf(_('Settings changed for device %s. Changes will take effect after reconnecting the device.'), $dev);

        return JsonResponse::success($msg);
    }

    /**
     * Returns the permanent address of a device from the output of
     * `ip -j -d link show`. `ip` shows "permaddr" only when the current
     * address is different. Else the current address is the permanent one.
     *
     * @param string $ipJson
     * @param string $current the current address
     * @return string
     */
    public static function permanentMac(string $ipJson, string $current): string
    {
        $link = json_decode($ipJson, true)[0] ?? null;
        $perm = is_array($link) && is_string($link['permaddr'] ?? null) ? strtolower($link['permaddr']) : '';
        return self::validMac($perm) ? $perm : $current;
    }

    /**
     * A unicast MAC address in lowercase, for example 02:11:22:33:44:55
     *
     * @param string $mac
     * @return bool
     */
    private static function validMac(string $mac): bool
    {
        return preg_match('/^[0-9a-f]{2}(:[0-9a-f]{2}){5}$/', $mac) === 1
            && (hexdec(substr($mac, 0, 2)) & 1) === 0
            && $mac !== '00:00:00:00:00:00';
    }
}

