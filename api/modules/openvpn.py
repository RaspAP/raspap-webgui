import subprocess

def client_configs():
    return subprocess.run("find /etc/openvpn/client/ -type f | wc -l", shell=True, capture_output=True, text=True).stdout.strip()

def client_config_names():
    config_names_list = []
    output = subprocess.run('''ls /etc/openvpn/client/ | grep -v "^client.conf$"''', shell=True, capture_output=True, text=True).stdout.strip()
    lines = output.split("\n")
    for client in lines:
        if "_client" in client:
            config_names_dict ={'config':client}
            config_names_list.append(config_names_dict)
    return config_names_list

def client_login_names():
    config_names_list = []
    output = subprocess.run('''ls /etc/openvpn/client/ | grep -v "^client.conf$"''', shell=True, capture_output=True, text=True).stdout.strip()
    lines = output.split("\n")
    for client in lines:
        if "_login" in client:
            config_names_dict ={'login':client}
            config_names_list.append(config_names_dict)
    return config_names_list

def client_config_active():
    output = subprocess.run('''ls -al  /etc/openvpn/client/ | grep "client.conf -"''', shell=True, capture_output=True, text=True).stdout.strip()
    active_config = output.split("/etc/openvpn/client/")
    # empty without an active config
    return active_config[1] if len(active_config) > 1 else ""

def client_login_active():
    output = subprocess.run('''ls -al  /etc/openvpn/client/ | grep "login.conf -"''', shell=True, capture_output=True, text=True).stdout.strip()
    active_config = output.split("/etc/openvpn/client/")
    # empty without an active login
    return active_config[1] if len(active_config) > 1 else ""

def client_config_list(client_config):
    output = subprocess.run(["cat", f"/etc/openvpn/client/{client_config}"], capture_output=True, text=True).stdout.strip()
    # empty when the service user can not read the file (mode 600)
    return output.split('\n') if output else []

#TODO: where is the logfile??
#TODO: is service connected?
