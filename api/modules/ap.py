import subprocess
import re
import json

def driver():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep driver= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def ctrl_interface():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ctrl_interface= | cut -d'=' -f2 | head -1", shell=True, capture_output=True, text=True).stdout.strip()

def ctrl_interface_group():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ctrl_interface_group= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def auth_algs():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep auth_algs= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def wpa_key_mgmt():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep wpa_key_mgmt= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def beacon_int():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep beacon_int= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def ssid():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ssid= | cut -d'=' -f2 | head -1", shell=True, capture_output=True, text=True).stdout.strip()

def channel():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep channel= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def hw_mode():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep hw_mode= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def ieee80211n():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ieee80211n= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def frequency_band():
    ap_interface = interface()
    result = subprocess.run(["iw", "dev", ap_interface, "info"], capture_output=True, text=True)
    match = re.search(r"channel\s+\d+\s+\((\d+)\s+MHz\)", result.stdout)

    if match:
            frequency = int(match.group(1))

            if 2400 <= frequency < 2500:
                return "2.4"
            elif 5000 <= frequency < 6000:
                return "5"

    return None

def wpa_passphrase():
    return subprocess.run("sed -En 's/wpa_passphrase=(.*)/\\1/p' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def interface():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep '^interface=' | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def wpa():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep wpa= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def wpa_pairwise():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep wpa_pairwise= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def country_code():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep country_code= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def ignore_broadcast_ssid():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ignore_broadcast_ssid= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def logging():
    log_output = subprocess.run(f"cat /tmp/hostapd.log", shell=True, capture_output=True, text=True).stdout.strip()
    logs = {}

    for line in log_output.split('\n'):
        parts = line.split(': ')
        if len(parts) >= 2:
            interface, message = parts[0], parts[1]
            if interface not in logs:
                logs[interface] = []
            logs[interface].append(message)

    return json.dumps(logs, indent=2)
