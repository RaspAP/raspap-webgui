import subprocess
import json

def get_active_clients_amount(interface):
    arp_output = subprocess.run(['arp', '-i', interface], capture_output=True, text=True)
    mac_addresses = arp_output.stdout.splitlines()

    if mac_addresses:
        grep_pattern = '|'.join(mac_addresses)
        output = subprocess.run(['grep', '-iwE', grep_pattern, '/var/lib/misc/dnsmasq.leases'], capture_output=True, text=True)
        return len(output.stdout.splitlines())
    else:
        return 0

def get_active_clients(interface):
    arp_output = subprocess.run(['arp', '-i', interface], capture_output=True, text=True)
    arp_mac_addresses = set(line.split()[2] for line in arp_output.stdout.splitlines()[1:])

    dnsmasq_output = subprocess.run(['cat', '/var/lib/misc/dnsmasq.leases'], capture_output=True, text=True)
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

