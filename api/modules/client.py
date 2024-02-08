import subprocess
import json

def get_active_clients_amount(interface):
    output =  subprocess.run(f'''cat '/var/lib/misc/dnsmasq.leases' | grep -iwE "$(arp -i '{interface}' | grep -oE "(([0-9]|[a-f]|[A-F]){{{2}}}:){{{5}}}([0-9]|[a-f]|[A-F]){{{2}}}")"''', shell=True, capture_output=True, text=True)
    return(len(output.stdout.splitlines()))

def get_active_clients(interface):
    #does not run like intended, but it works....
    output =  subprocess.run(f'''cat '/var/lib/misc/dnsmasq.leases' | grep -iwE "$(arp -i '{interface}' | grep -oE "(([0-9]|[a-f]|[A-F]){{{2}}}:){{{5}}}([0-9]|[a-f]|[A-F]){{{2}}}")"''', shell=True, capture_output=True, text=True)
    clients_list = []

    for line in output.stdout.splitlines():
        fields = line.split()

        client_data = {
            "timestamp": int(fields[0]),
            "mac_address": fields[1],
            "ip_address": fields[2],
            "hostname": fields[3],
            "client_id": fields[4],
        }

        clients_list.append(client_data)

    json_output = json.dumps(clients_list, indent=2)

    return json_output