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
        $vid = $form["int-vid-$dev"] ?? '';
        $pid = $form["int-pid-$dev"] ?? '';
        $mac = trim(strtolower($form["int-mac-$dev"] ?? ''));
        $newMac = trim(strtolower($form["int-new-mac-$dev"] ?? ''));
        $name = trim(strtolower(preg_replace("/[^a-z0-9]/", '', $form["int-name-$dev"] ?? '')));
        $name = substr($name, 0, 20);
        $type = $form["int-type-$dev"] ?? '';
        $newType = $form["int-new-type-$dev"] ?? '';

        $macAddrChanged = false;
        if (!empty($newMac) && $mac !== $newMac) {
            exec("sudo ip link set " . escapeshellarg($dev) . " down");
            sleep(1);
            exec("sudo ip link set " . escapeshellarg($dev) . " address " . escapeshellarg($newMac));
            exec("sudo ip link set " . escapeshellarg($dev) . " up");
            $mac = $newMac;
            $macAddrChanged = true;
        }

        $this->ruleManager->deleteExistingRules($mac, $vid, $pid);
        $proto = $this->rulePrototypes->findByType($newType);

        if ($proto === null) {
            return JsonResponse::error("No matching prototype rule for type $newType");
        }

        if (empty($name)) {
            $name = $proto['name_prefix'] . '%n';
        }
        $rule = $this->ruleManager->buildRule(
            $proto,
            $newMac,
            $vid,
            $pid,
            $name,
            $newType
        );
        $this->ruleManager->save($rule);

        $msg = $macAddrChanged
            ? "Address changed for device $dev. New MAC address: $newMac"
            : "Settings changed for device $dev. Changes will take effect after reconnecting the device.";

        return JsonResponse::success($msg);
    }
}

