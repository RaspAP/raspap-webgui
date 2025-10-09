import subprocess
import json

def range_start():
    return subprocess.run("cat /etc/dnsmasq.d/090_wlan0.conf |grep dhcp-range= |cut -d'=' -f2| cut -d',' -f1", shell=True, capture_output=True, text=True).stdout.strip()

def range_end():
    return subprocess.run("cat /etc/dnsmasq.d/090_wlan0.conf |grep dhcp-range= |cut -d'=' -f2| cut -d',' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def range_subnet_mask():
    return subprocess.run("cat /etc/dnsmasq.d/090_wlan0.conf |grep dhcp-range= |cut -d'=' -f2| cut -d',' -f3", shell=True, capture_output=True, text=True).stdout.strip()

def range_lease_time():
    return subprocess.run("cat /etc/dnsmasq.d/090_wlan0.conf |grep dhcp-range= |cut -d'=' -f2| cut -d',' -f4", shell=True, capture_output=True, text=True).stdout.strip()

def range_gateway():
    return subprocess.run("cat /etc/dhcpcd.conf | grep routers | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def range_nameservers():
    output = subprocess.run("cat /etc/dhcpcd.conf", shell=True, capture_output=True, text=True).stdout.strip()

    nameservers = []

    lines = output.split('\n')
    for line in lines:
        if "static domain_name_server" in line:
            servers = line.split('=')[1].strip().split()
            nameservers.extend(servers)

    return nameservers