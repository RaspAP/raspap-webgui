import subprocess
import re
import os
import json

import modules.ap as ap
import config

def get_active_wireless_clients_mac(interface):
    station_dump = subprocess.run(["iw", "dev", interface, "station", "dump"], capture_output=True, text=True)

    macs = []
    for line in station_dump.stdout.splitlines():
        if line.startswith("Station "):
            # Typical format: Station aa:bb:cc:dd:ee:ff (on wlan0)
            parts = line.split()
            if len(parts) >= 2:
                mac = parts[1].strip()
                # Optional: basic validation
                if len(mac) == 17 and mac.count(":") == 5:
                    macs.append(mac.upper())

    return macs

def get_active_wireless_clients_amount():
    ap_interface = ap.interface()
    macs = get_active_wireless_clients_mac(ap_interface)

    return len(macs)

def get_active_ethernet_clients_mac():
    arp_macs = []

    arp_output = subprocess.run(['ip', 'neigh', 'show'], capture_output=True, text=True)
    if arp_output.stdout:
        for line in arp_output.stdout.splitlines():
            line = line.strip()
            if not line:
                continue

            # Matches lines like:
            # 192.168.100.45 dev enp3s0 lladdr 3c:97:0e:12:34:56 REACHABLE
            # 192.168.1.120 dev eth0 lladdr 00:1a:2b:3c:4d:5e DELAY
            match = re.match(
                r'^(\S+)\s+dev\s+(eth[0-9]+|en\w+)\s+lladdr\s+(\S+)\s+(REACHABLE|DELAY|PROBE)',
                line
            )
            if match:
                mac = match.group(3).upper()
                arp_macs.append(mac)

    lease_macs = []

    if os.path.isfile(config.DNSMASQ_LEASES):
        try:
            with open(config.DNSMASQ_LEASES, encoding="utf-8", errors="ignore") as f:
                for line in f:
                    line = line.strip()
                    if not line or line.startswith("#"):
                        continue
                    fields = line.split()
                    if len(fields) >= 3:
                        # format: expiry MAC IP hostname ...
                        mac = fields[1].upper()
                        lease_macs.append(mac)
        except Exception:
            pass

    active_ethernet_macs = []
    for mac in arp_macs:
        if mac in lease_macs and mac not in active_ethernet_macs:
            active_ethernet_macs.append(mac)


    return active_ethernet_macs

def get_active_ethernet_clients_amount():
    eth_macs = get_active_ethernet_clients_mac()
    return len(eth_macs)

def get_active_clients_amount():
    wireless_clients_count = get_active_wireless_clients_amount()
    ethernet_clients_count = get_active_ethernet_clients_amount()

    return wireless_clients_count + ethernet_clients_count

def get_active_clients():
    ap_interface = ap.interface()
    wireless_macs = get_active_wireless_clients_mac(ap_interface)
    ethernet_macs = get_active_ethernet_clients_mac()

    arp_output = subprocess.run(['arp', '-i', ap_interface], capture_output=True, text=True)
    arp_mac_addresses = set(line.split()[2] for line in arp_output.stdout.splitlines()[1:])

    dnsmasq_output = subprocess.run(['cat', config.DNSMASQ_LEASES], capture_output=True, text=True)
    active_clients = []

    for line in dnsmasq_output.stdout.splitlines():
        fields = line.split()
        mac_address = fields[1]

        if mac_address in arp_mac_addresses:
            normalized_mac = mac_address.upper()
            is_wireless = True if normalized_mac in wireless_macs else False
            is_ethernet = True if normalized_mac in ethernet_macs else False

            client_data = {
                "timestamp": int(fields[0]),
                "mac_address": fields[1],
                "ip_address": fields[2],
                "hostname": fields[3],
                "client_id": fields[4],
                "connection_type": 'wireless' if is_wireless else ('ethernet' if is_ethernet else 'unknown')
            }
            active_clients.append(client_data)

    json_output = json.dumps(active_clients, indent=2)
    return json_output

def get_active_clients_amount_by_interface(interface):
    arp_output = subprocess.run(['arp', '-i', interface], capture_output=True, text=True)
    mac_addresses = set(line.split()[2] for line in arp_output.stdout.splitlines()[1:])

    if mac_addresses:
        grep_pattern = '|'.join(mac_addresses)
        output = subprocess.run(['grep', '-iwE', grep_pattern, config.DNSMASQ_LEASES], capture_output=True, text=True)
        return len(output.stdout.splitlines())
    else:
        return 0

def get_active_clients_by_interface(interface):
    arp_output = subprocess.run(['arp', '-i', interface], capture_output=True, text=True)
    arp_mac_addresses = set(line.split()[2] for line in arp_output.stdout.splitlines()[1:])

    dnsmasq_output = subprocess.run(['cat', config.DNSMASQ_LEASES], capture_output=True, text=True)
    active_clients = []

    for line in dnsmasq_output.stdout.splitlines():
        fields = line.split()
        mac_address = fields[1]

        if mac_address in arp_mac_addresses:
            client_data = {
                "timestamp": int(fields[0]),
                "mac_address": fields[1],
                "ip_address": fields[2],
                "hostname": fields[3],
                "client_id": fields[4],
            }
            active_clients.append(client_data)

    json_output = json.dumps(active_clients, indent=2)
    return json_output

