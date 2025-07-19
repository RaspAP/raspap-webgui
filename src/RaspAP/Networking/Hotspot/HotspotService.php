<?php

namespace RaspAP\Networking\Hotspot;

use RaspAP\Networking\Hotspot\Validators\HostapdValidator;

/**
 * Coordinates hotspot configuration and lifecycle
 *
 * Handles:
 * - Hostapd & dnsmasq config updates
 * - dhcpcd interface adjustments
 * - Service control (start/stop/restart)
 */
class HotspotService
{
    protected HostapdManager $hostapdManager;
    protected DnsmasqManager $dnsmasqManager;
    protected DhcpcdManager $dhcpcdManager;

    public function __construct()
    {
        $this->hostapdManager = new HostapdManager();
        $this->dnsmasqManager = new DnsmasqManager();
        $this->dhcpcdManager  = new DhcpcdManager();
    }

    /**
     * Apply configuration changes for hotspot.
     *
     * @param array $params
     * @return bool
     */
    public function configureHotspot(array $params): bool
    {
        // TODO: validate params, orchestrate managers
        return false;
    }

    /**
     * Start hotspot services for given interface.
     *
     * @param string $iface
     * @return bool
     */
    public function start(string $iface): bool
    {
        // TODO: implement systemctl or service logic
        return false;
    }

    /**
     * Stop hotspot services.
     *
     * @return bool
     */
    public function stop(): bool
    {
        // TODO: implement
        return false;
    }

    /**
     * Restart hotspot services for given interface.
     *
     * @param string $iface
     * @return bool
     */
    public function restart(string $iface): bool
    {
        // TODO: implement
        return false;
    }

    /**
     * Get current hotspot status.
     *
     * @return array
     */
    public function getStatus(): array
    {
        // TODO: query service state + configs
        return [];
    }
}

