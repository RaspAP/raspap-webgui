import subprocess
import json

def adblockdomains():
    output = subprocess.run("cat /etc/raspap/adblock/domains.txt", shell=True, capture_output=True, text=True).stdout.strip()
    domains =output.split('\n')
    domainlist=[]
    for domain in domains:
        if domain.startswith('#') or domain=="":
            continue
        domainlist.append(domain.split('=/')[1])
    return domainlist

def adblockhostnames():
    output = subprocess.run("cat /etc/raspap/adblock/hostnames.txt", shell=True, capture_output=True, text=True).stdout.strip()
    hostnames = output.split('\n')
    hostnamelist=[]
    for hostname in hostnames:
        if hostname.startswith('#') or hostname=="":
            continue
        hostnamelist.append(hostname.replace('0.0.0.0 ',''))
    return hostnamelist

def upstream_nameserver():
    return subprocess.run("awk '/nameserver/ {print $2}' /run/dnsmasq/resolv.conf", shell=True, capture_output=True, text=True).stdout.strip()

def dnsmasq_logs():
    output = subprocess.run("cat /var/log/dnsmasq.log", shell=True, capture_output=True, text=True).stdout.strip()
    log_entries = []
    for line in output.split("\n"):
        fields = line.split(" ")
        log_dict = {
                'timestamp': ' '.join(fields[:3]),
                'process': fields[3][:-1],  # Remove the trailing colon
                'message': ' '.join(fields[4:]),
            }
        log_entries.append(log_dict)
    return log_entries