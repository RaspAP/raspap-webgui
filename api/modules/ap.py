import subprocess
import json

def driver():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep driver= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_driver(driver):
    return subprocess.run(f"sudo sed -i 's/^driver=.*/driver={driver}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def ctrl_interface():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ctrl_interface= | cut -d'=' -f2 | head -1", shell=True, capture_output=True, text=True).stdout.strip()

def set_ctrl_interface(ctrl_interface):
    return subprocess.run(f"sudo sed -i 's/^ctrl_interface=.*/ctrl_interface={ctrl_interface}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def ctrl_interface_group():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ctrl_interface_group= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_ctrl_interface_group(ctrl_interface_group):
    return subprocess.run(f"sudo sed -i 's/^ctrl_interface_group=.*/ctrl_interface_group={ctrl_interface_group}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def auth_algs():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep auth_algs= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_auth_algs(auth_algs):
    return subprocess.run(f"sudo sed -i 's/^auth_algs=.*/auth_algs={auth_algs}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def wpa_key_mgmt():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep wpa_key_mgmt= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_wpa_key_mgmt(wpa_key_mgmt):
    return subprocess.run(f"sudo sed -i 's/^wpa_key_mgmt=.*/wpa_key_mgmt={wpa_key_mgmt}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def beacon_int():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep beacon_int= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_beacon_int(beacon_int):
    return subprocess.run(f"sudo sed -i 's/^beacon_int=.*/beacon_int={beacon_int}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def ssid():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ssid= | cut -d'=' -f2 | head -1", shell=True, capture_output=True, text=True).stdout.strip()

def set_ssid(ssid):
    return subprocess.run(f"sudo sed -i 's/^ssid=.*/ssid={ssid}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def channel():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep channel= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_channel(channel):
    return subprocess.run(f"sudo sed -i 's/^channel=.*/channel={channel}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def hw_mode():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep hw_mode= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_hw_mode(hw_mode):
    return subprocess.run(f"sudo sed -i 's/^hw_mode=.*/hw_mode={hw_mode}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def ieee80211n():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ieee80211n= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_ieee80211n(ieee80211n):
    return subprocess.run(f"sudo sed -i 's/^ieee80211n=.*/ieee80211n={ieee80211n}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def wpa_passphrase():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep wpa_passphrase= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_wpa_passphrase(wpa_passphrase):
    return subprocess.run(f"sudo sed -i 's/^wpa_passphrase=.*/wpa_passphrase={wpa_passphrase}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def interface():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep interface= | cut -d'=' -f2 | head -1", shell=True, capture_output=True, text=True).stdout.strip()

def set_interface(interface):
    return subprocess.run(f"sudo sed -i 's/^interface=.*/interface={interface}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def wpa():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep wpa= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_wpa(wpa):
    return subprocess.run(f"sudo sed -i 's/^wpa=.*/wpa={wpa}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def wpa_pairwise():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep wpa_pairwise= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_wpa_pairwise(wpa_pairwise):
    return subprocess.run(f"sudo sed -i 's/^wpa_pairwise=.*/wpa_pairwise={wpa_pairwise}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def country_code():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep country_code= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_country_code(country_code):
    return subprocess.run(f"sudo sed -i 's/^country_code=.*/country_code={country_code}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

def ignore_broadcast_ssid():
    return subprocess.run("cat /etc/hostapd/hostapd.conf | grep ignore_broadcast_ssid= | cut -d'=' -f2", shell=True, capture_output=True, text=True).stdout.strip()

def set_ignore_broadcast_ssid(ignore_broadcast_ssid):
    return subprocess.run(f"sudo sed -i 's/^ignore_broadcast_ssid=.*/ignore_broadcast_ssid={ignore_broadcast_ssid}/' /etc/hostapd/hostapd.conf", shell=True, capture_output=True, text=True).stdout.strip()

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