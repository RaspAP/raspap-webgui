import subprocess
import re
import os

def configs():
    #ignore symlinks, because wg0.conf is in production the main config, but in insiders it is a symlink
    return subprocess.run("find /etc/wireguard/ -type f | wc -l", shell=True, capture_output=True, text=True).stdout.strip()

def client_config_names():
    config_names_list = []
    output = subprocess.run('''ls /etc/wireguard/ | grep -v "^wg0.conf$"''', shell=True, capture_output=True, text=True).stdout.strip()
    lines = output.split("\n")
    for client in lines:
        config_names_dict ={'config':client}
        config_names_list.append(config_names_dict)
    return config_names_list

def client_config_active():
    output = subprocess.run('''ls -al  /etc/wireguard/ | grep "wg0.conf -"''', shell=True, capture_output=True, text=True).stdout.strip()
    active_config = output.split("/etc/wireguard/")
    return(active_config[1])

def client_config_list(client_config):
    pattern = r'^[a-zA-Z0-9_-]+$'
    if not re.match(pattern, client_config):
        raise ValueError("Invalid client_config")

    # sanitize path to prevent directory traversal
    client_config = os.path.basename(client_config)

    config_path = os.path.join("/etc/wireguard/", client_config)
    if not os.path.exists(config_path):
        raise FileNotFoundError("Client configuration file not found")

    with open(config_path, 'r') as f:
        output = f.read().strip()
        return output.split('\n')

#TODO: where is the logfile??
#TODO: is service connected?
