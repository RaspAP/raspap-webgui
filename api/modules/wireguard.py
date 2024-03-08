import subprocess
import re

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

#TODO: where is the logfile??
#TODO: is service connected?
