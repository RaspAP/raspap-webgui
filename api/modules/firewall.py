import subprocess

def firewall_rules():
    return subprocess.run("cat /etc/raspap/networking/firewall/iptables_rules.json", shell=True, capture_output=True, text=True).stdout.strip()