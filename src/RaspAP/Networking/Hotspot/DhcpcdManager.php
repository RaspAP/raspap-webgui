<?php

namespace RaspAP\Networking\Hotspot;

/**
 * Handles dhcpcd.conf interface configuration.
 */
class DhcpcdManager
{
    /**
     * Get dhcpcd settings for an interface.
     *
     * @param string $iface
     * @return array
     */
    public function getInterfaceSection(string $iface): array
    {
        // TODO: parse dhcpcd.conf
        return [];
    }

    /**
     * Write or update interface section.
     *
     * @param string $iface
     * @param array $kv
     * @return bool
     */
    public function writeInterfaceSection(string $iface, array $kv): bool
    {
        // TODO: update config
        return false;
    }

    /**
     * Remove interface section from dhcpcd.conf.
     *
     * @param string $iface
     * @return bool
     */
    public function removeInterface(string $iface): bool
    {
        // TODO: delete section
        return false;
    }

    /**
     * Retrieves the metric value for a given interface
     *
     * @param string $iface
     * @return int $metric| bool false on failure
     */
    public function getIfaceMetric($iface)
    {
        $metric = shell_exec("ip -o -4 route show dev ".$iface." | awk '/metric/ {print \$NF; exit}'");
        if (isset($metric)) {
            $metric = (int)$metric;
            return $metric;
        } else {
            return false;
        }
    }


}

