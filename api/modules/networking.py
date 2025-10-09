import psutil
import json

def throughput():
    interface_info = {}

    # Get network interfaces
    interfaces = psutil.net_if_stats()

    for interface, stats in interfaces.items():
        if interface.startswith("lo") or interface.startswith("docker"):
            # Skip loopback and docker interface
            continue

        try:
            # Get network traffic statistics
            traffic_stats = psutil.net_io_counters(pernic=True)[interface]
            rx_packets = traffic_stats[1]
            rx_bytes = traffic_stats[0]
            tx_packets = traffic_stats[3]
            tx_bytes = traffic_stats[4]

            interface_info[interface] = {
                "RX_packets": rx_packets,
                "RX_bytes": rx_bytes,
                "TX_packets": tx_packets,
                "TX_bytes": tx_bytes
            }
        except KeyError:
            # Handle the case where network interface statistics are not available
            pass

    return json.dumps(interface_info, indent=2)

def interfaces():
    interface_info = {}

    # Get network interfaces
    interfaces = psutil.net_if_addrs()

    for interface, addrs in interfaces.items():
        if interface.startswith("lo") or interface.startswith("docker"):
            # Skip loopback and docker interface
            continue

        ip_address = None
        netmask = None
        mac_address = None

        for addr in addrs:
            if addr.family == 2:  # AF_INET corresponds to the integer value 2
                # IPv4 address
                ip_address = addr.address
                netmask = addr.netmask

        # Get MAC address
        for addr in psutil.net_if_addrs().get(interface, []):
            if addr.family == psutil.AF_LINK:
                mac_address = addr.address

        interface_info[interface] = {
            "IP_address": ip_address,
            "Netmask": netmask,
            "MAC_address": mac_address
        }
    return json.dumps(interface_info, indent=2)

#TODO: migrate to vnstat, to lose psutil dependency